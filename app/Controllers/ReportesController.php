<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csv;
use App\Core\View;
use App\Models\Reporte;

class ReportesController {
    public function index(): void {
        Auth::require(['administrador', 'gerente', 'contador', 'tesorero', 'auditor']);
        View::render('reportes/index', ['title' => 'Reportes']);
    }

    public function antiguedad(): void {
        Auth::require(['administrador', 'gerente', 'contador', 'tesorero', 'auditor']);
        View::render('reportes/antiguedad', [
            'title' => 'CxC por antigüedad',
            'filas' => Reporte::antiguedadCxc(),
        ]);
    }

    public function cxp(): void {
        Auth::require(['administrador', 'gerente', 'contador', 'tesorero', 'auditor']);
        View::render('reportes/cxp', [
            'title' => 'CxP pendientes y vencidas',
            'filas' => Reporte::cxpPendientes(),
        ]);
    }

    public function exportarAntiguedad(): void {
        Auth::require(['administrador', 'gerente', 'contador', 'tesorero', 'auditor']);

        $encabezados = ['Cliente', 'Identificador fiscal', 'Corriente', '1-30', '31-60', '61-90', '+90', 'Total'];
        $filas = [];
        foreach (Reporte::antiguedadCxc() as $fila) {
            $filas[] = [
                (string) $fila['cliente'],
                (string) $fila['identificador_fiscal'],
                $this->monto($fila['corriente']),
                $this->monto($fila['a1_30']),
                $this->monto($fila['a31_60']),
                $this->monto($fila['a61_90']),
                $this->monto($fila['mas90']),
                $this->monto($fila['total']),
            ];
        }

        Csv::descargar('cartera_cxc_antiguedad.csv', $encabezados, $filas);
    }

    public function exportarCxp(): void {
        Auth::require(['administrador', 'gerente', 'contador', 'tesorero', 'auditor']);

        $encabezados = ['Proveedor', 'Identificador fiscal', 'Documento', 'Emisión', 'Vencimiento', 'Total', 'Saldo', 'Situación', 'Días atraso'];
        $filas = [];
        foreach (Reporte::cxpPendientes() as $fila) {
            $filas[] = [
                (string) $fila['proveedor'],
                (string) $fila['identificador_fiscal'],
                (string) $fila['numero_documento'],
                $this->fecha($fila['fecha_emision']),
                $this->fecha($fila['fecha_vencimiento']),
                $this->monto($fila['total']),
                $this->monto($fila['saldo']),
                (string) $fila['situacion'],
                (string) (int) $fila['dias_atraso'],
            ];
        }

        Csv::descargar('cxp_pendientes.csv', $encabezados, $filas);
    }

    private function monto(mixed $valor): string {
        return number_format((float) $valor, 2, '.', '');
    }

    private function fecha(mixed $valor): string {
        $valor = (string) $valor;
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) === 1) {
            return $partes[3] . '/' . $partes[2] . '/' . $partes[1];
        }
        return $valor;
    }
}
