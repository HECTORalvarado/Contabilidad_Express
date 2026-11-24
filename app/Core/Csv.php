<?php
namespace App\Core;

class Csv {
    /** Descarga un CSV con BOM UTF-8; las celdas de texto peligrosas se neutralizan. */
    public static function descargar(string $nombre, array $encabezados, array $filas): void {
        $nombre = preg_replace('/[^A-Za-z0-9_.-]/', '_', $nombre) ?: 'reporte.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');

        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF");
        fputcsv($salida, self::celdas($encabezados));
        foreach ($filas as $fila) {
            fputcsv($salida, self::celdas(array_values($fila)));
        }
        fclose($salida);
        exit;
    }

    private static function celdas(array $fila): array {
        return array_map(static function (mixed $valor): string {
            $texto = (string) $valor;
            if (is_string($valor) && preg_match('/^[=+\-@]/', $texto)) {
                return "'" . $texto;
            }
            return $texto;
        }, $fila);
    }
}
