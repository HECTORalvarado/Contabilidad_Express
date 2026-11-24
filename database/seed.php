<?php
declare(strict_types=1);

require __DIR__ . '/../app/Core/Database.php';

$pdo = App\Core\Database::getConnection();

$pdo->beginTransaction();

try {
    $pdo->exec('TRUNCATE bitacora, pagos, documentos, facturas, terceros, usuarios, roles RESTART IDENTITY CASCADE;');

    $roles = [
        'administrador', 'gerente', 'contador', 'auxiliar',
        'encargado_cxp', 'encargado_cxc', 'tesorero', 'auditor',
    ];
    $stmtRol = $pdo->prepare('INSERT INTO roles (nombre) VALUES (:nombre)');
    foreach ($roles as $r) {
        $stmtRol->execute([':nombre' => $r]);
    }

    $rolId = [];
    foreach ($pdo->query('SELECT id, nombre FROM roles') as $row) {
        $rolId[$row['nombre']] = (int)$row['id'];
    }

    $passwordHash = password_hash('Demo1234!', PASSWORD_DEFAULT);

    $usuarios = [
        ['Administrador Demo', 'admin@demo.local', 'administrador'],
        ['Gerente Demo', 'gerente@demo.local', 'gerente'],
        ['Contador Demo', 'contador@demo.local', 'contador'],
        ['Auxiliar Demo', 'auxiliar@demo.local', 'auxiliar'],
        ['Encargado CxP Demo', 'cxp@demo.local', 'encargado_cxp'],
        ['Encargado CxC Demo', 'cxc@demo.local', 'encargado_cxc'],
        ['Tesorero Demo', 'tesorero@demo.local', 'tesorero'],
        ['Auditor Demo', 'auditor@demo.local', 'auditor'],
    ];
    $stmtUser = $pdo->prepare(
        'INSERT INTO usuarios (nombre, email, password_hash, rol_id, activo) VALUES (:nombre, :email, :hash, :rol_id, TRUE)'
    );
    $userId = [];
    foreach ($usuarios as [$nombre, $email, $rol]) {
        $stmtUser->execute([
            ':nombre' => $nombre,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':rol_id' => $rolId[$rol],
        ]);
        $userId[$rol] = (int)$pdo->lastInsertId('usuarios_id_seq');
    }

    $auxiliarId = $userId['auxiliar'];
    $gerenteId = $userId['gerente'];
    $contadorId = $userId['contador'];
    $cxpId = $userId['encargado_cxp'];
    $cxcId = $userId['encargado_cxc'];

    $tercerosData = [
        ['cliente', 'Empresa Alfa, S.A. de C.V.', '0614-100001-001-1', 'alfa@example.com', '2222-1001'],
        ['cliente', 'Comercial Beta, S.A. de C.V.', '0614-100002-002-2', 'beta@example.com', '2222-1002'],
        ['cliente', 'Servicios Gamma, S.A. de C.V.', '0614-100003-003-3', 'gamma@example.com', '2222-1003'],
        ['proveedor', 'Proveedor Delta, S.A. de C.V.', '0614-200001-001-4', 'delta@example.com', '2222-2001'],
        ['proveedor', 'Proveedor Epsilon, S.A. de C.V.', '0614-200002-002-5', 'epsilon@example.com', '2222-2002'],
        ['proveedor', 'Proveedor Zeta, S.A. de C.V.', '0614-200003-003-6', 'zeta@example.com', '2222-2003'],
    ];
    $stmtTer = $pdo->prepare(
        'INSERT INTO terceros (tipo, nombre, identificador_fiscal, email, telefono, activo) VALUES (:tipo, :nombre, :nit, :email, :tel, TRUE)'
    );
    $terceroId = [];
    foreach ($tercerosData as $i => [$tipo, $nombre, $nit, $email, $tel]) {
        $stmtTer->execute([
            ':tipo' => $tipo,
            ':nombre' => $nombre,
            ':nit' => $nit,
            ':email' => $email,
            ':tel' => $tel,
        ]);
        $terceroId[$i] = (int)$pdo->lastInsertId('terceros_id_seq');
    }
    // clientes: 0,1,2 ; proveedores: 3,4,5
    $clienteIds = [$terceroId[0], $terceroId[1], $terceroId[2]];
    $proveedorIds = [$terceroId[3], $terceroId[4], $terceroId[5]];

    $f = static function (int $dias): string {
        return (new DateTime('today'))->modify($dias . ' days')->format('Y-m-d');
    };
    $emision = static function (int $dias): string {
        return (new DateTime('today'))->modify($dias . ' days')->format('Y-m-d');
    };

    $ivaPct = 13;
    $calc = static function (float $subtotal) use ($ivaPct): array {
        $iva = round($subtotal * $ivaPct / 100, 2);
        $total = round($subtotal + $iva, 2);
        return [$subtotal, $iva, $total];
    };

    // Facturas: [tipo, tercero_id, numero, emision_offset, venc_offset, subtotal, estado, aprobador]
    $facturasDef = [
        ['CXP', $proveedorIds[0], 'FAC-CXP-001', -5, 15, 100.00, 'pendiente_aprobacion', null],
        ['CXP', $proveedorIds[1], 'FAC-CXP-002', -7, 3, 200.00, 'aprobada', $gerenteId],
        ['CXP', $proveedorIds[2], 'FAC-CXP-003', -40, -10, 150.00, 'parcial', $contadorId],
        ['CXP', $proveedorIds[0], 'FAC-CXP-004', -60, -20, 250.00, 'pagada', $gerenteId],
        ['CXC', $clienteIds[0], 'FAC-CXC-001', -5, 20, 120.00, 'pendiente_aprobacion', null],
        ['CXC', $clienteIds[1], 'FAC-CXC-002', -7, 2, 220.00, 'aprobada', $contadorId],
        ['CXC', $clienteIds[2], 'FAC-CXC-003', -50, -20, 180.00, 'parcial', $gerenteId],
        ['CXC', $clienteIds[0], 'FAC-CXC-004', -70, -30, 300.00, 'pagada', $contadorId],
    ];

    $stmtFac = $pdo->prepare(
        'INSERT INTO facturas (tipo, tercero_id, numero_documento, fecha_emision, fecha_vencimiento, subtotal, iva, total, estado, registrado_por, aprobado_por, fecha_aprobacion) VALUES (:tipo, :tercero_id, :numero, :emision, :venc, :subtotal, :iva, :total, :estado, :registrado_por, :aprobado_por, :fecha_aprobacion)'
    );

    $facturaIds = [];
    $facturaInfo = []; // index => [tipo, total, estado]
    foreach ($facturasDef as $i => $def) {
        [$tipo, $terceroIdVal, $numero, $emOff, $vencOff, $subtotal, $estado, $aprobador] = $def;
        [$sub, $iva, $total] = $calc((float)$subtotal);
        $fechaEmision = $emision($emOff);
        $fechaVenc = $f($vencOff);
        $fechaAprob = null;
        if ($aprobador !== null) {
            $fechaAprob = (new DateTime('today'))->modify(($emOff + 1) . ' days')->format('Y-m-d') . ' 10:00:00+00';
        }
        $stmtFac->execute([
            ':tipo' => $tipo,
            ':tercero_id' => $terceroIdVal,
            ':numero' => $numero,
            ':emision' => $fechaEmision,
            ':venc' => $fechaVenc,
            ':subtotal' => number_format($sub, 2, '.', ''),
            ':iva' => number_format($iva, 2, '.', ''),
            ':total' => number_format($total, 2, '.', ''),
            ':estado' => $estado,
            ':registrado_por' => $auxiliarId,
            ':aprobado_por' => $aprobador,
            ':fecha_aprobacion' => $fechaAprob,
        ]);
        $fid = (int)$pdo->lastInsertId('facturas_id_seq');
        $facturaIds[] = $fid;
        $facturaInfo[$fid] = ['tipo' => $tipo, 'total' => $total, 'estado' => $estado];
    }

    // Pagos: 4 (parcial y pagada de CXP y CXC)
    $stmtPago = $pdo->prepare(
        'INSERT INTO pagos (factura_id, fecha, monto, metodo, referencia, registrado_por) VALUES (:factura_id, :fecha, :monto, :metodo, :ref, :registrado_por)'
    );
    $hoyStr = (new DateTime('today'))->format('Y-m-d');
    $pagosDef = [
        // factura index 2 (CXP parcial): pago parcial
        [2, round($facturaInfo[$facturaIds[2]]['total'] * 0.4, 2), 'transferencia', 'TRX-CXP-003', $cxpId],
        // factura index 3 (CXP pagada): pago total
        [3, $facturaInfo[$facturaIds[3]]['total'], 'efectivo', 'EFE-CXP-004', $cxpId],
        // factura index 6 (CXC parcial): pago parcial
        [6, round($facturaInfo[$facturaIds[6]]['total'] * 0.5, 2), 'transferencia', 'TRX-CXC-003', $cxcId],
        // factura index 7 (CXC pagada): pago total
        [7, $facturaInfo[$facturaIds[7]]['total'], 'efectivo', 'EFE-CXC-004', $cxcId],
    ];
    foreach ($pagosDef as [$idx, $monto, $metodo, $ref, $regPor]) {
        $fid = $facturaIds[$idx];
        $stmtPago->execute([
            ':factura_id' => $fid,
            ':fecha' => $hoyStr,
            ':monto' => number_format((float)$monto, 2, '.', ''),
            ':metodo' => $metodo,
            ':ref' => $ref,
            ':registrado_por' => $regPor,
        ]);
    }

    // Documentos: 1 por factura
    $uploadDir = __DIR__ . '/../storage/uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $pdfContent = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\nxref\n0 4\n0000000000 65535 f \n0000000009 000000 n \n0000000058 000000 n \n0000000115 000000 n \ntrailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n190\n%%EOF\n";

    $stmtDoc = $pdo->prepare(
        'INSERT INTO documentos (factura_id, nombre_original, nombre_archivo, mime, tamano, subido_por) VALUES (:factura_id, :nombre_original, :nombre_archivo, :mime, :tamano, :subido_por)'
    );
    foreach ($facturaIds as $i => $fid) {
        $nombreArchivo = bin2hex(random_bytes(8)) . '.pdf';
        $ruta = $uploadDir . $nombreArchivo;
        file_put_contents($ruta, $pdfContent);
        $tamano = filesize($ruta);
        $stmtDoc->execute([
            ':factura_id' => $fid,
            ':nombre_original' => 'factura_' . ($i + 1) . '.pdf',
            ':nombre_archivo' => $nombreArchivo,
            ':mime' => 'application/pdf',
            ':tamano' => $tamano,
            ':subido_por' => $auxiliarId,
        ]);
    }

    // Parametros
    $pdo->exec(
        "INSERT INTO parametros (clave, valor) VALUES ('iva_porcentaje', '13'), ('dias_alerta_vencimiento', '5')
         ON CONFLICT (clave) DO UPDATE SET valor = EXCLUDED.valor;"
    );

    $pdo->commit();
} catch (\Throwable $e) {
    $pdo->rollBack();
    error_log('seed.php: ' . $e->getMessage());
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}

// Conteos
$counts = [];
foreach (['roles', 'usuarios', 'terceros', 'facturas', 'documentos', 'pagos'] as $t) {
    $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}
$counts['parametros'] = (int)$pdo->query('SELECT COUNT(*) FROM parametros')->fetchColumn();

echo "roles={$counts['roles']} usuarios={$counts['usuarios']} terceros={$counts['terceros']} facturas={$counts['facturas']} documentos={$counts['documentos']} pagos={$counts['pagos']} parametros={$counts['parametros']}\n";
