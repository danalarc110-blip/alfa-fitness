<?php

namespace App\Http\Controllers;

use App\Models\ReporteFinancieroLog;
use App\Services\AnaliticaFinancieraService;
use App\Services\ReporteExcelService;
use App\Services\ReportePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AnaliticaFinancieraController extends Controller
{
    public function __construct(
        protected AnaliticaFinancieraService $analiticaService,
        protected ReportePdfService $pdfService,
        protected ReporteExcelService $excelService,
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('analitica_financiera');
        $this->validarFiltros($request);

        $preset = $request->query('preset', 'este_mes');
        $desdeInput = $request->query('desde');
        $hastaInput = $request->query('hasta');

        $periodo = $this->analiticaService->resolverPeriodo($preset, $desdeInput, $hastaInput);
        $datos = $this->analiticaService->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);

        return view('analitica.index', [
            'datos' => $datos,
            'presetActual' => $periodo['preset'],
            'desdeInput' => $desdeInput ?: $periodo['desde']->format('Y-m-d'),
            'hastaInput' => $hastaInput ?: $periodo['hasta']->format('Y-m-d'),
        ]);
    }

    public function exportarPdf(Request $request)
    {
        Gate::authorize('analitica_financiera');
        $this->validarFiltros($request);

        $preset = $request->query('preset', 'este_mes');
        $desdeInput = $request->query('desde');
        $hastaInput = $request->query('hasta');

        $periodo = $this->analiticaService->resolverPeriodo($preset, $desdeInput, $hastaInput);
        $datos = $this->analiticaService->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);

        // Registrar auditoría de generación
        ReporteFinancieroLog::create([
            'user_id' => $request->user()->id,
            'tipo' => 'pdf',
            'fecha_inicio' => $periodo['desde']->format('Y-m-d'),
            'fecha_fin' => $periodo['hasta']->format('Y-m-d'),
        ]);

        $nombreArchivo = sprintf(
            'alpha-fitness-reporte-financiero-%s-a-%s.pdf',
            $periodo['desde']->format('Y-m-d'),
            $periodo['hasta']->format('Y-m-d')
        );

        return $this->pdfService->descargar($datos, $nombreArchivo);
    }

    public function exportarExcel(Request $request)
    {
        Gate::authorize('analitica_financiera');
        $this->validarFiltros($request);

        $preset = $request->query('preset', 'este_mes');
        $desdeInput = $request->query('desde');
        $hastaInput = $request->query('hasta');

        $periodo = $this->analiticaService->resolverPeriodo($preset, $desdeInput, $hastaInput);
        $datos = $this->analiticaService->obtenerAnalitica($periodo['desde'], $periodo['hasta'], $periodo['dias']);

        // Registrar auditoría de generación
        ReporteFinancieroLog::create([
            'user_id' => $request->user()->id,
            'tipo' => 'excel',
            'fecha_inicio' => $periodo['desde']->format('Y-m-d'),
            'fecha_fin' => $periodo['hasta']->format('Y-m-d'),
        ]);

        $nombreArchivo = sprintf(
            'alpha-fitness-reporte-financiero-%s-a-%s.xlsx',
            $periodo['desde']->format('Y-m-d'),
            $periodo['hasta']->format('Y-m-d')
        );

        return $this->excelService->descargar($datos, $nombreArchivo);
    }

    private function validarFiltros(Request $request): void
    {
        $request->validate([
            'preset' => ['nullable', 'string', 'in:hoy,ayer,esta_semana,semana_anterior,este_mes,mes_anterior,personalizado'],
            'desde' => ['nullable', 'string', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'string', 'date_format:Y-m-d'],
        ]);
    }
}
