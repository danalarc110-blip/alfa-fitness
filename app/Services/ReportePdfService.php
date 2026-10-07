<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReportePdfService
{
    /**
     * Renderiza y genera la descarga del reporte PDF ejecutivo.
     */
    public function descargar(array $datos, string $nombreArchivo): Response
    {
        $pdf = Pdf::loadView('analitica.pdf', compact('datos'))
            ->setPaper('letter', 'portrait')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'Helvetica',
            ]);

        return $pdf->download($nombreArchivo);
    }
}
