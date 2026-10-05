<?php

namespace App\Http\Controllers;

use App\Models\SolicitudDatos;
use App\Services\ConsentimientoLegal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LegalController extends Controller
{
    public const TIPOS = ['acceso', 'rectificacion', 'supresion', 'oposicion', 'portabilidad', 'limitacion', 'retiro'];

    public function documento(Request $request, string $documento)
    {
        $documentos = ['privacidad' => 'PRIVACIDAD', 'terminos' => 'TERMINOS', 'lesiones' => 'LESIONES', 'derechos' => 'DERECHOS_DATOS'];
        abort_unless(isset($documentos[$documento]), 404);
        $data = $request->validate(['version' => ['nullable', 'string', 'max:32']]);
        $version = $data['version'] ?? null;
        $contenido = app(ConsentimientoLegal::class)->contenido($documento, $version);
        foreach ($documentos as $slug => $archivo) {
            $contenido = str_replace('('.$archivo.'.md)', '('.route('legal.documento', array_filter(['documento' => $slug, 'version' => $version])).')', $contenido);
        }
        $html = Str::markdown($contenido, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return response()->view('legal.documento', compact('documento', 'html'));
    }

    public function guardarSolicitud(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'correo' => ['required', 'email', 'max:255'],
            'tipo' => ['required', Rule::in(self::TIPOS)],
            'detalle' => ['required', 'string', 'max:3000'],
        ]);
        SolicitudDatos::create(collect($datos)->only(['nombre', 'correo', 'tipo', 'detalle'])->all());

        return redirect()->route('legal.documento', 'derechos')->with('status', 'Solicitud recibida. El responsable verificará tu identidad antes de responder; no envíes contraseñas ni documentos sensibles.');
    }

    public function mostrarAceptacion()
    {
        abort_unless(auth('cliente')->check(), 403);

        return response()->view('legal.aceptacion-cliente')->header('Cache-Control', 'private, no-store');
    }

    public function aceptarCliente(Request $request)
    {
        abort_unless(auth('cliente')->check(), 403);
        $request->validate(['aceptacion_legal' => ['required', 'accepted'], 'legal_version' => ['required', 'string', Rule::in([config('legal.version')])]], ['legal_version.in' => 'Los documentos cambiaron. Vuelve a leerlos antes de aceptar.']);
        app(ConsentimientoLegal::class)->aceptar(auth('cliente')->user());

        return redirect()->route('cliente.dashboard')->with('status', 'Aceptación registrada.');
    }

    public function solicitudes(Request $request)
    {
        Gate::authorize('administrar');
        $estado = $request->validate(['estado' => ['nullable', Rule::in(['pendiente', 'en_revision', 'resuelta'])]])['estado'] ?? null;
        $solicitudes = SolicitudDatos::query()->when($estado, fn ($q) => $q->where('estado', $estado))->latest()->paginate(15)->withQueryString();

        return view('legal.solicitudes', compact('solicitudes'));
    }

    public function resolver(Request $request, SolicitudDatos $solicitud)
    {
        Gate::authorize('administrar');
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'en_revision', 'resuelta'])],
            'notas_internas' => ['nullable', 'string', 'max:3000'],
        ]);
        $solicitud->forceFill($datos + ['resuelta_en' => $datos['estado'] === 'resuelta' ? now() : null])->save();

        return back()->with('status', 'Seguimiento actualizado. La respuesta al titular debe enviarse por un canal verificado.');
    }
}
