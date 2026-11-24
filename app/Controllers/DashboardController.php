<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Reporte;

class DashboardController {
    public function index(): void {
        Auth::require(['administrador', 'gerente', 'tesorero', 'auditor']);

        $resumen = Reporte::resumen();
        $dias = max(1, (int) ($resumen['dias_alerta'] ?? 5));
        $proximas = Reporte::proximas($dias);

        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'resumen' => $resumen,
            'proximas' => $proximas,
            'dias' => $dias,
        ]);
    }
}
