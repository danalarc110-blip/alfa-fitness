<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\Rutina;
use App\Models\RutinaDia;
use App\Models\RutinaEjercicio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class RutinaController extends Controller
{
    /**
     * Devuelve ['guard' => 'web'|'cliente', 'user' => modelo autenticado].
     * Mismo patrón que ConfiguracionController.
     */
    private function actual(): array
    {
        if (Auth::guard('web')->check()) {
            return ['guard' => 'web', 'user' => Auth::guard('web')->user()];
        }

        return ['guard' => 'cliente', 'user' => Auth::guard('cliente')->user()];
    }

    private function nombreActual($guard, $user): string
    {
        return $guard === 'web' ? $user->name : $user->nombre;
    }

    /**
     * Listado de rutinas del usuario actual.
     */
    public function index()
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $rutinas = Rutina::deUsuario($guard, $user->id)
            ->with('dias.ejercicios')
            ->latest()
            ->get();

        return view('entrenamientos.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar ? asset('images/avatars/'.$user->avatar) : null,
            'rutinas' => $rutinas,
        ]);
    }

    /**
     * Formulario "Crear nueva rutina" (el builder de la maqueta).
     */
    public function crear()
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

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
                'id'         => $d->id,
                'titulo'     => $d->titulo,
                'ejercicios' => $d->ejercicios->map(function ($re) {
                    $ej = $re->ejercicio;
                    return [
                        'id'                => $re->id,
                        'series'            => $re->series,
                        'repeticiones'      => $re->repeticiones,
                        'peso'              => $re->peso,
                        'descanso_segundos' => $re->descanso_segundos,
                        'ejercicio' => [
                            'id'                    => $ej->id,
                            'nombre'                => $ej->nombre,
                            'grupo_muscular'        => $ej->grupo_muscular,
                            'imagen_url'            => $ej->imagen_url,
                            'imagen_musculos_url'   => $ej->imagen_musculos_url,
                            'tiene_imagen'          => $ej->tiene_imagen,
                            'tiene_imagen_musculos' => $ej->tiene_imagen_musculos,
                        ],
                    ];
                })->values(),
            ];
        })->values();

        return view('entrenamientos.crear', [
            'guard'            => $guard,
            'nombre'           => $this->nombreActual($guard, $user),
            'rolEtiqueta'      => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl'        => $user->avatar ? asset('images/avatars/'.$user->avatar) : null,
            'rutina'           => $rutina,
            'ejercicios'       => $ejercicios,
            'diasJson'         => $diasJson,
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
    public function actualizar(Request $request, Rutina $rutina): RedirectResponse
    {
        $this->autorizarPropietario($rutina);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'objetivo' => ['required', 'string', 'max:60'],
            'nivel' => ['required', Rule::in(['Principiante', 'Intermedio', 'Avanzado'])],
            'dias_por_semana' => ['required', 'integer', 'min:1', 'max:7'],
        ]);

        $rutina->update($data);

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

        $orden = $rutina->dias()->max('orden') + 1;

        $dia = $rutina->dias()->create([
            'orden' => $orden,
            'titulo' => 'Día '.$orden,
            'duracion_estimada_min' => 45,
            'duracion_estimada_max' => 60,
        ]);

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

        $rutina = $dia->rutina;
        $dia->delete();

        // Reordena los días restantes para que no queden huecos (Día 1, Día 2...)
        $rutina->dias()->orderBy('orden')->get()->values()->each(function ($d, $i) {
            $d->update(['orden' => $i + 1]);
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
        $q = $request->string('q')->toString();
        $grupo = $request->string('grupo')->toString();

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
            'ejercicio_id' => ['required', 'exists:ejercicios,id'],
        ]);

        $orden = $dia->ejercicios()->max('orden') + 1;

        $rutinaEjercicio = $dia->ejercicios()->create([
            'ejercicio_id' => $data['ejercicio_id'],
            'orden' => $orden,
            'series' => 3,
            'repeticiones' => '8-10',
            'peso' => null,
            'descanso_segundos' => 60,
        ]);

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

        $dia = $rutinaEjercicio->dia;
        $rutinaEjercicio->delete();

        $dia->ejercicios()->orderBy('orden')->get()->values()->each(function ($re, $i) {
            $re->update(['orden' => $i + 1]);
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
            'orden.*' => ['integer', 'exists:rutina_ejercicios,id'],
        ]);

        foreach ($data['orden'] as $i => $id) {
            RutinaEjercicio::where('id', $id)
                ->where('rutina_dia_id', $dia->id)
                ->update(['orden' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
