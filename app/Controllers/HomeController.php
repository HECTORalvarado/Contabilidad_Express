<?php
namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

class HomeController {
    public function index(): void {
        $dbOk = null;
        $dbError = null;
        try {
            Database::getConnection()->query('SELECT 1');
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbError = $e->getMessage();
            error_log('Conexión BD: ' . $e->getMessage());
        }

        View::render('home/index', [
            'title' => 'Inicio',
            'dbOk' => $dbOk,
            'dbError' => $dbError,
        ]);
    }
}
