<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\RutinaDia;
use App\Models\RutinaEjercicio;
use App\Models\SesionEntrenamiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RutinaController extends Controller
{
    /**
     * Listado de rutinas del usuario actual.
     */
    public function index()
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $rutinas = Rutina::deUsuario($guard, $user->id)
            ->with(['dias' => fn ($q) => $q->withCount('ejercicios')])
            ->latest()
            ->paginate(18);

        $clientes = $guard === 'web' && Gate::allows('asignar_rutinas')
            ? Cliente::where('activo', true)->orderBy('nombre')->get(['id', 'nombre'])
            : collect();

        return view('entrenamientos.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'rutinas' => $rutinas,
            'clientes' => $clientes,
        ]);
    }

    /**
     * Formulario "Crear nueva rutina" (el builder de la maqueta).
     */
    public function crear()
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $rutina = DB::transaction(function () use ($guard, $user) {
            $rutina = Rutina::create([
                'user_id' => $user->id,
                'user_type' => $guard,
                'nombre' => 'Nueva rutina',
                'objetivo' => 'Ganar masa muscular',
                'nivel' => 'Intermedio',
                'dias_por_semana' => 1,
            ]);

            $rutina->dias()->create([
                'orden' => 1,
                'titulo' => 'Día 1',
                'duracion_estimada_min' => 45,
                'duracion_estimada_max' => 60,
            ]);

            return $rutina;
        });

        return redirect()->route('entrenamientos.editar', $rutina);
    }

    /**
     * Builder de una rutina existente (misma vista que "crear", ya con datos).
     */
    public function editar(Rutina $rutina)
    {
        $this->autorizarPropietario($rutina);

        ['guard' => $guard, 'user' => $user] = $this->actual();

        $rutina->load(['dias.ejercicios.ejercicio']);

        $ejercicios = Ejercicio::where('activo', true)->orderBy('nombre')->get();

        // Serializar aqui para no usar arrow-functions dentro de @json en Blade
        $diasJson = $rutina->dias->map(function ($d) {
            return [
                'id' => $d->id,
                'titulo' => $d->titulo,
                'ejercicios' => $d->ejercicios->map(function ($re) {
                    $ej = $re->ejercicio;

                    return [
                        'id' => $re->id,
                        'series' => $re->series,
                        'repeticiones' => $re->repeticiones,
                        'peso' => $re->peso,
                        'descanso_segundos' => $re->descanso_segundos,
                        'ejercicio' => [
                            'id' => $ej->id,
                            'nombre' => $ej->nombre,
                            'grupo_muscular' => $ej->grupo_muscular,
                            'imagen_url' => $ej->imagen_url,
                            'imagen_musculos_url' => $ej->imagen_musculos_url,
                            'tiene_imagen' => $ej->tiene_imagen,
                            'tiene_imagen_musculos' => $ej->tiene_imagen_musculos,
                        ],
                    ];
                })->values(),
            ];
        })->values();

        return view('entrenamientos.crear', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'rutina' => $rutina,
            'ejercicios' => $ejercicios,
            'diasJson' => $diasJson,
            'gruposMusculares' => ['Pecho', 'Espalda', 'Piernas', 'Hombros', 'Biceps', 'Triceps', 'Abdomen'],
        ]);
    }

    /**
     * Verifica que la rutina pertenezca al usuario autenticado.
     */
    private function autorizarPropietario(Rutina $rutina): void
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        abort_unless($rutina->user_type === $guard && $rutina->user_id === $user->id, 403);
    }

    /**
     * Guarda los datos generales de la rutina (nombre, objetivo, nivel, días/semana).
     */
    public function actualizar(Request $request, Rutina $rutina): JsonResponse|RedirectResponse
    {
        $this->autorizarPropietario($rutina);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'objetivo' => ['required', 'string', 'max:60'],
            'nivel' => ['required', Rule::in(['Principiante', 'Intermedio', 'Avanzado'])],
            'dias_por_semana' => ['required', 'integer', 'min:1', 'max:7'],
        ]);

        $rutina->update($data);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'rutina' => $rutina]);
        }

        return back()->with('status', 'Rutina actualizada.');
    }

    public function eliminar(Rutina $rutina): RedirectResponse
    {
        $this->autorizarPropietario($rutina);

        $rutina->delete();

        return redirect()->route('entrenamientos.index')->with('status', 'Rutina eliminada.');
    }

    // =========================================================
    //  DÍAS
    // =========================================================

    public function agregarDia(Rutina $rutina): JsonResponse
    {
        $this->autorizarPropietario($rutina);

        $dia = DB::transaction(function () use ($rutina) {
            $rutina = Rutina::whereKey($rutina->id)->lockForUpdate()->firstOrFail();
            $orden = $rutina->dias()->max('orden') + 1;

            $dia = $rutina->dias()->create([
                'orden' => $orden,
                'titulo' => 'Día '.$orden,
                'duracion_estimada_min' => 45,
                'duracion_estimada_max' => 60,
            ]);

            return $dia;
        });

        return response()->json(['dia' => $dia]);
    }

    public function renombrarDia(Request $request, RutinaDia $dia): JsonResponse
    {
        $this->autorizarPropietario($dia->rutina);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:100'],
        ]);

        $dia->update($data);

        return response()->json(['dia' => $dia]);
    }

    public function eliminarDia(RutinaDia $dia): JsonResponse
    {
        $this->autorizarPropietario($dia->rutina);

        DB::transaction(function () use ($dia) {
            $rutina = Rutina::whereKey($dia->rutina_id)->lockForUpdate()->firstOrFail();
            $dia->delete();

            // Reordena los días restantes para que no queden huecos (Día 1, Día 2...)
            $rutina->dias()->orderBy('orden')->get()->values()->each(function ($d, $i) {
                $d->update(['orden' => $i + 1]);
            });

        });

        return response()->json(['ok' => true]);
    }

    // =========================================================
    //  EJERCICIOS DENTRO DE UN DÍA
    // =========================================================

    /**
     * Buscador del catálogo (panel derecho), con filtro por texto y grupo muscular.
     */
    public function buscarEjercicios(Request $request): JsonResponse
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'grupo' => ['nullable', 'string', 'max:100']]);
        $q = $filters['q'] ?? '';
        $grupo = $filters['grupo'] ?? '';

        $ejercicios = Ejercicio::where('activo', true)
            ->when($q, fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->when($grupo && $grupo !== 'Todos', fn ($query) => $query->where('grupo_muscular', $grupo))
            ->orderBy('nombre')
            ->get()
            ->map(fn (Ejercicio $e) => [
                'id' => $e->id,
                'nombre' => $e->nombre,
                'grupo_muscular' => $e->grupo_muscular,
                'subgrupo' => $e->subgrupo,
                'imagen_url' => $e->imagen_url,
                'imagen_musculos_url' => $e->imagen_musculos_url,
                'tiene_imagen' => $e->tiene_imagen,
                'tiene_imagen_musculos' => $e->tiene_imagen_musculos,
            ]);

        return response()->json(['ejercicios' => $ejercicios]);
    }

    public function agregarEjercicio(Request $request, RutinaDia $dia): JsonResponse
    {
        $this->autorizarPropietario($dia->rutina);

        $data = $request->validate([
            'ejercicio_id' => ['required', 'integer', Rule::exists('ejercicios', 'id')->where('activo', true)],
        ]);

        $rutinaEjercicio = DB::transaction(function () use ($dia, $data) {
            $dia = RutinaDia::whereKey($dia->id)->lockForUpdate()->firstOrFail();
            $orden = $dia->ejercicios()->max('orden') + 1;

            $rutinaEjercicio = $dia->ejercicios()->create([
                'ejercicio_id' => $data['ejercicio_id'],
                'orden' => $orden,
                'series' => 3,
                'repeticiones' => '8-10',
                'peso' => null,
                'descanso_segundos' => 60,
            ]);

            return $rutinaEjercicio;
        });
        $rutinaEjercicio->load('ejercicio');

        return response()->json(['rutina_ejercicio' => $rutinaEjercicio]);
    }

    public function actualizarEjercicio(Request $request, RutinaEjercicio $rutinaEjercicio): JsonResponse
    {
        $this->autorizarPropietario($rutinaEjercicio->dia->rutina);

        $data = $request->validate([
            'series' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'repeticiones' => ['sometimes', 'string', 'max:20'],
            'peso' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999'],
            'descanso_segundos' => ['sometimes', 'integer', 'min:0', 'max:600'],
        ]);

        $rutinaEjercicio->update($data);

        return response()->json(['rutina_ejercicio' => $rutinaEjercicio]);
    }

    public function eliminarEjercicio(RutinaEjercicio $rutinaEjercicio): JsonResponse
    {
        $this->autorizarPropietario($rutinaEjercicio->dia->rutina);

        DB::transaction(function () use ($rutinaEjercicio) {
            $dia = RutinaDia::whereKey($rutinaEjercicio->rutina_dia_id)->lockForUpdate()->firstOrFail();
            $rutinaEjercicio->delete();

            $dia->ejercicios()->orderBy('orden')->get()->values()->each(function ($re, $i) {
                $re->update(['orden' => $i + 1]);
            });

        });

        return response()->json(['ok' => true]);
    }

    /**
     * Reordena los ejercicios de un día (drag & drop).
     */
    public function reordenarEjercicios(Request $request, RutinaDia $dia): JsonResponse
    {
        $this->autorizarPropietario($dia->rutina);

        $data = $request->validate([
            'orden' => ['required', 'array'],
            'orden.*' => ['integer', 'distinct', Rule::exists('rutina_ejercicios', 'id')->where('rutina_dia_id', $dia->id)],
        ]);

        DB::transaction(function () use ($data, $dia) {
            $dia = RutinaDia::whereKey($dia->id)->lockForUpdate()->firstOrFail();
            if (array_diff($data['orden'], $dia->ejercicios()->pluck('id')->all())) {
                abort(422);
            }
            if (count($data['orden']) !== $dia->ejercicios()->count()) {
                throw ValidationException::withMessages(['orden' => 'Incluye todos los ejercicios del día para guardar el orden.']);
            }
            foreach ($data['orden'] as $i => $id) {
                RutinaEjercicio::where('id', $id)
                    ->where('rutina_dia_id', $dia->id)
                    ->update(['orden' => $i + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    /**
     * Asigna una rutina a un cliente (clonando la rutina con sus días y ejercicios).
     */
    public function asignar(Request $request, Rutina $rutina): RedirectResponse
    {
        Gate::authorize('asignar_rutinas');
        $this->autorizarPropietario($rutina);
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web', 403);

        $data = $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
        ]);

        $cliente = Cliente::findOrFail($data['cliente_id']);
        if (! $cliente->activo) {
            throw ValidationException::withMessages([
                'cliente_id' => 'El cliente seleccionado se encuentra inactivo.',
            ]);
        }

        DB::transaction(function () use ($rutina, $cliente, $user) {
            $clonada = Rutina::create([
                'user_id' => $cliente->id,
                'user_type' => 'cliente',
                'nombre' => $rutina->nombre,
                'objetivo' => $rutina->objetivo,
                'nivel' => $rutina->nivel,
                'dias_por_semana' => $rutina->dias_por_semana,
                'activa' => true,
                'asignado_por' => $user->name,
                'asignado_por_id' => $user->id,
            ]);

            $rutina->load(['dias.ejercicios']);

            foreach ($rutina->dias as $dia) {
                $nuevoDia = $clonada->dias()->create([
                    'orden' => $dia->orden,
                    'titulo' => $dia->titulo,
                    'duracion_estimada_min' => $dia->duracion_estimada_min,
                    'duracion_estimada_max' => $dia->duracion_estimada_max,
                ]);

                foreach ($dia->ejercicios as $ej) {
                    $nuevoDia->ejercicios()->create([
                        'ejercicio_id' => $ej->ejercicio_id,
                        'orden' => $ej->orden,
                        'series' => $ej->series,
                        'repeticiones' => $ej->repeticiones,
                        'peso' => $ej->peso,
                        'descanso_segundos' => $ej->descanso_segundos,
                    ]);
                }
            }
        });

        return back()->with('status', "Rutina asignada exitosamente a {$cliente->nombre}.");
    }

    /**
     * Modo interactivo "Entrenar Ahora" con checklist de series y cronómetro de descanso.
     */
    public function entrenar(Rutina $rutina, ?RutinaDia $dia = null)
    {
        $this->autorizarPropietario($rutina);
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $rutina->load(['dias.ejercicios.ejercicio']);

        if (! $dia || $dia->rutina_id !== $rutina->id) {
            $diaSeleccionado = $rutina->dias->first();
        } else {
            $diaSeleccionado = $dia;
        }

        return view('entrenamientos.entrenar', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'rutina' => $rutina,
            'diaSeleccionado' => $diaSeleccionado,
        ]);
    }

    /**
     * Vista imprimible / PDF-friendly de la rutina.
     */
    public function imprimir(Rutina $rutina)
    {
        $this->autorizarPropietario($rutina);
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $rutina->load(['dias.ejercicios.ejercicio']);

        return view('entrenamientos.imprimir', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'rutina' => $rutina,
        ]);
    }

    /**
     * Registra la finalización de una sesión de entrenamiento interactiva.
     */
    public function finalizarSesion(Request $request, Rutina $rutina)
    {
        $this->autorizarPropietario($rutina);
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $data = $request->validate([
            'dia_id' => ['nullable', 'integer', Rule::exists('rutina_dias', 'id')->where('rutina_id', $rutina->id)],
            'duracion_segundos' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'series_completadas' => ['nullable', 'integer', 'min:0', 'max:500'],
            'total_series' => ['nullable', 'integer', 'min:0', 'max:500'],
            'notas' => ['nullable', 'string', 'max:500'],
            'sesion_uuid' => ['nullable', 'uuid'],
        ]);

        if (($data['series_completadas'] ?? 0) > ($data['total_series'] ?? 0)) {
            throw ValidationException::withMessages(['series_completadas' => 'Las series completadas no pueden superar el total de series.']);
        }

        $rutina->load(['dias']);

        $dia = null;
        if (! empty($data['dia_id'])) {
            $dia = $rutina->dias->firstWhere('id', (int) $data['dia_id']);
        }
        $diaTitulo = $dia ? $dia->titulo : ($rutina->dias->first()?->titulo ?? 'Entrenamiento');

        $duracion = (int) ($data['duracion_segundos'] ?? 0);
        $ahora = now();
        $iniciado = $duracion > 0 ? (clone $ahora)->subSeconds($duracion) : $ahora;

        $atributos = [
            'user_id' => $user->id,
            'user_type' => $guard,
            'rutina_id' => $rutina->id,
            'rutina_nombre' => $rutina->nombre,
            'dia_id' => $dia?->id,
            'dia_titulo' => $diaTitulo,
            'iniciado_en' => $iniciado,
            'finalizado_en' => $ahora,
            'duracion_segundos' => $duracion,
            'series_completadas' => (int) ($data['series_completadas'] ?? 0),
            'total_series' => (int) ($data['total_series'] ?? 0),
            'estado' => 'completado',
            'notas' => $data['notas'] ?? null,
        ];

        if (! empty($data['sesion_uuid'])) {
            $data['sesion_uuid'] = strtolower($data['sesion_uuid']);
            $sesion = SesionEntrenamiento::firstOrCreate(['sesion_uuid' => $data['sesion_uuid']], $atributos);
            abort_unless($sesion->user_type === $guard && $sesion->user_id === $user->id && $sesion->rutina_id === $rutina->id, 403);
        } else {
            $sesion = SesionEntrenamiento::create($atributos);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'mensaje' => '¡Entrenamiento registrado con éxito!',
                'sesion' => $sesion,
            ]);
        }

        return redirect()
            ->route('entrenamientos.historial')
            ->with('status', '¡Entrenamiento guardado en tu historial!');
    }

    /**
     * Historial de sesiones de entrenamiento completadas por el usuario.
     */
    public function historial(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $query = SesionEntrenamiento::deUsuario($guard, $user->id)->latest('finalizado_en');

        $sesiones = (clone $query)->paginate(15);
        $totalSesiones = SesionEntrenamiento::deUsuario($guard, $user->id)->count();
        $totalMinutos = (int) round(SesionEntrenamiento::deUsuario($guard, $user->id)->sum('duracion_segundos') / 60);
        $totalSeries = (int) SesionEntrenamiento::deUsuario($guard, $user->id)->sum('series_completadas');

        return view('entrenamientos.historial', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'sesiones' => $sesiones,
            'totalSesiones' => $totalSesiones,
            'totalMinutos' => $totalMinutos,
            'totalSeries' => $totalSeries,
        ]);
    }
}
