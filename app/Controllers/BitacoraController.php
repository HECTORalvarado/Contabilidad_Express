<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class BitacoraController {
    public function index(): void {
        Auth::require(['administrador', 'auditor']);

        $filtros = [
            'usuario_id' => $this->digitos('usuario_id'),
            'accion' => $this->texto('accion', 60),
            'entidad' => $this->texto('entidad', 60),
            'desde' => $this->fechaGet('desde'),
            'hasta' => $this->fechaGet('hasta'),
        ];

        $where = [];
        $parametros = [];
        if ($filtros['usuario_id'] !== '') {
            $where[] = 'b.usuario_id = CAST(:usuario_id AS integer)';
            $parametros[':usuario_id'] = (int) $filtros['usuario_id'];
        }
        if ($filtros['accion'] !== '') {
            $where[] = 'b.accion ILIKE :accion';
            $parametros[':accion'] = '%' . $filtros['accion'] . '%';
        }
        if ($filtros['entidad'] !== '') {
            $where[] = 'b.entidad ILIKE :entidad';
            $parametros[':entidad'] = '%' . $filtros['entidad'] . '%';
        }
        if ($filtros['desde'] !== '') {
            $where[] = 'b.fecha >= CAST(:desde AS date)';
            $parametros[':desde'] = $filtros['desde'];
        }
        if ($filtros['hasta'] !== '') {
            $where[] = "b.fecha < CAST(:hasta AS date) + INTERVAL '1 day'";
            $parametros[':hasta'] = $filtros['hasta'];
        }
        $condicion = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

        $db = Database::getConnection();
        $conteo = $db->prepare('SELECT COUNT(*) FROM bitacora b ' . $condicion);
        $conteo->execute($parametros);
        $total = (int) $conteo->fetchColumn();

        $porPagina = 50;
        $totalPaginas = max(1, (int) ceil($total / $porPagina));
        $pagina = ctype_digit($this->texto('page', 10)) ? max(1, (int) $this->texto('page', 10)) : 1;
        $pagina = min($pagina, $totalPaginas);

        $consulta = $db->prepare(
            'SELECT b.id, b.usuario_id, b.fecha, b.accion, b.entidad, b.entidad_id,
                    b.valor_anterior, b.valor_nuevo, b.motivo, b.ip,
                    u.nombre AS usuario_nombre, u.email AS usuario_email
             FROM bitacora b
             LEFT JOIN usuarios u ON u.id = b.usuario_id
             ' . $condicion . '
             ORDER BY b.fecha DESC, b.id DESC
             LIMIT CAST(:limite AS integer) OFFSET CAST(:desplazamiento AS integer)'
        );
        $parametros[':limite'] = $porPagina;
        $parametros[':desplazamiento'] = ($pagina - 1) * $porPagina;
        $consulta->execute($parametros);
        $registros = $consulta->fetchAll();

        $listaUsuarios = $db->query('SELECT id, nombre, email FROM usuarios ORDER BY nombre')->fetchAll();

        $resumen = [];
        if ($filtros['usuario_id'] !== '') {
            foreach ($listaUsuarios as $u) {
                if ((int) $u['id'] === (int) $filtros['usuario_id']) {
                    $resumen[] = 'Usuario: ' . $u['nombre'];
                    break;
                }
            }
        }
        if ($filtros['accion'] !== '') {
            $resumen[] = 'Acción: ' . $filtros['accion'];
        }
        if ($filtros['entidad'] !== '') {
            $resumen[] = 'Entidad: ' . $filtros['entidad'];
        }
        if ($filtros['desde'] !== '') {
            $resumen[] = 'Desde: ' . $filtros['desde'];
        }
        if ($filtros['hasta'] !== '') {
            $resumen[] = 'Hasta: ' . $filtros['hasta'];
        }

        View::render('bitacora/index', [
            'title' => 'Bitácora',
            'registros' => $registros,
            'listaUsuarios' => $listaUsuarios,
            'filtros' => $filtros,
            'resumen' => $resumen,
            'consultaBase' => array_filter($filtros, static fn($valor) => $valor !== ''),
            'total' => $total,
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'porPagina' => $porPagina,
        ]);
    }

    private function fechaValida(string $valor): bool {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            return false;
        }
        $partes = array_map('intval', explode('-', $valor));
        return checkdate($partes[1], $partes[2], $partes[0]);
    }

    private function texto(string $clave, int $max): string {
        $valor = $_GET[$clave] ?? '';
        return is_string($valor) ? substr(trim($valor), 0, $max) : '';
    }

    private function digitos(string $clave): string {
        $valor = $this->texto($clave, 10);
        return ctype_digit($valor) ? $valor : '';
    }

    private function fechaGet(string $clave): string {
        $valor = $this->texto($clave, 10);
        return $this->fechaValida($valor) ? $valor : '';
    }
}
