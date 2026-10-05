<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\EjercicioCalificacion;
use App\Services\ImagenSegura;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EjercicioController extends Controller
{
    /**
     * Muestra la vista principal de Ejercicios Populares de la Semana.
     */
    public function index(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $filtros = $request->validate([
            'grupo' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $grupo = $filtros['grupo'] ?? '';
        $q = $filtros['q'] ?? '';

        $ejerciciosQuery = Ejercicio::query()
            ->when(! Gate::allows('administrar'), fn ($query) => $query->where('activo', true))
            ->withAvg('calificaciones as promedio_estrellas', 'estrellas')
            ->withCount('calificaciones as conteo_votos')
            ->when($q, fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->when($grupo && $grupo !== 'Todos', fn ($query) => $query->where('grupo_muscular', $grupo));

        $ejercicios = $ejerciciosQuery->orderByDesc('promedio_estrellas')->orderByDesc('conteo_votos')->orderBy('nombre')->paginate(18)->withQueryString();
        $misVotos = EjercicioCalificacion::where('user_type', $guard)
            ->where('user_id', $user->id)
            ->whereIn('ejercicio_id', $ejercicios->pluck('id'))
            ->get()
            ->keyBy('ejercicio_id');

        $ejercicios->through(function (Ejercicio $ej) use ($misVotos) {
            $miVoto = $misVotos->get($ej->id);

            $ej->mi_calificacion = $miVoto ? $miVoto->estrellas : 0;
            $ej->promedio_estrellas = $ej->promedio_estrellas ? round((float) $ej->promedio_estrellas, 1) : 0.0;
            $ej->conteo_votos = (int) $ej->conteo_votos;

            return $ej;
        });

        $gruposMusculares = Ejercicio::where('activo', true)->distinct()->orderBy('grupo_muscular')->pluck('grupo_muscular');

        return view('ejercicios.index', [
            'guard' => $guard,
            'nombre' => $this->nombreActual($guard, $user),
            'rolEtiqueta' => $guard === 'web' ? $user->rol : 'Miembro',
            'avatarUrl' => $user->avatar_url,
            'ejercicios' => $ejercicios,
            'gruposMusculares' => $gruposMusculares,
            'grupoSeleccionado' => $grupo ?: 'Todos',
            'busqueda' => $q,
        ]);
    }

    /**
     * Registra o actualiza la calificación en estrellas de un ejercicio.
     */
    public function calificar(Request $request, Ejercicio $ejercicio): JsonResponse
    {
        abort_unless($ejercicio->activo, 404);

        ['guard' => $guard, 'user' => $user] = $this->actual();

        $data = $request->validate([
            'estrellas' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        EjercicioCalificacion::updateOrCreate(
            [
                'ejercicio_id' => $ejercicio->id,
                'user_id' => $user->id,
                'user_type' => $guard,
            ],
            [
                'estrellas' => $data['estrellas'],
            ]
        );

        $nuevoPromedio = round((float) $ejercicio->calificaciones()->avg('estrellas'), 1);
        $totalVotos = $ejercicio->calificaciones()->count();

        return response()->json([
            'ok' => true,
            'mensaje' => "¡Calificaste {$ejercicio->nombre} con {$data['estrellas']} estrellas!",
            'estrellas' => (int) $data['estrellas'],
            'promedio' => $nuevoPromedio,
            'total_votos' => $totalVotos,
        ]);
    }

    /**
     * Da de alta un nuevo ejercicio en el catálogo (administrador).
     */
    public function store(Request $request)
    {
        Gate::authorize('administrar');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:ejercicios,nombre'],
            'grupo_muscular' => ['required', 'string', 'max:50'],
            'subgrupo' => ['nullable', 'string', 'max:50'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=5000,max_height=5000'],
            'imagen_musculos' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=5000,max_height=5000'],
        ]);

        $ejercicio = null;
        $imgArchivo = null;
        $musculosArchivo = null;

        try {
            DB::transaction(function () use ($data, $request, &$ejercicio, &$imgArchivo, &$musculosArchivo) {
                $ejercicio = Ejercicio::create([
                    'nombre' => trim($data['nombre']),
                    'grupo_muscular' => trim($data['grupo_muscular']),
                    'subgrupo' => ! empty($data['subgrupo']) ? trim($data['subgrupo']) : null,
                    'activo' => true,
                ]);

                $imagenSegura = app(ImagenSegura::class);

                if ($request->hasFile('imagen')) {
                    $imgArchivo = $imagenSegura->guardar($request->file('imagen'), 'ejercicios', 'ejercicio_'.$ejercicio->id);
                    $ejercicio->update(['imagen' => $imgArchivo]);
                }

                if ($request->hasFile('imagen_musculos')) {
                    $musculosArchivo = $imagenSegura->guardar($request->file('imagen_musculos'), 'ejercicios', 'musculos_'.$ejercicio->id);
                    $ejercicio->update(['imagen_musculos' => $musculosArchivo]);
                }
            });
        } catch (\Throwable $e) {
            $imagenSegura = app(ImagenSegura::class);
            if ($imgArchivo) {
                $imagenSegura->eliminar($imgArchivo, 'ejercicios', 'ejercicio_'.$ejercicio?->id);
            }
            if ($musculosArchivo) {
                $imagenSegura->eliminar($musculosArchivo, 'ejercicios', 'musculos_'.$ejercicio?->id);
            }
            throw $e;
        }

        return back()->with('status', 'Ejercicio agregado correctamente al catálogo.');
    }

    /**
     * Actualiza un ejercicio existente (administrador).
     */
    public function update(Request $request, Ejercicio $ejercicio)
    {
        Gate::authorize('administrar');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:ejercicios,nombre,'.$ejercicio->id],
            'grupo_muscular' => ['required', 'string', 'max:50'],
            'subgrupo' => ['nullable', 'string', 'max:50'],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=5000,max_height=5000'],
            'imagen_musculos' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:max_width=5000,max_height=5000'],
            'eliminar_imagen' => ['nullable', 'boolean'],
            'eliminar_imagen_musculos' => ['nullable', 'boolean'],
        ]);

        $imagenSegura = app(ImagenSegura::class);
        $anteriorImg = $ejercicio->imagen;
        $anteriorMusc = $ejercicio->imagen_musculos;
        $nuevoImg = null;
        $nuevoMusc = null;

        try {
            DB::transaction(function () use ($data, $request, $ejercicio, $imagenSegura, &$nuevoImg, &$nuevoMusc, &$anteriorImg, &$anteriorMusc) {
                $ejercicio = Ejercicio::whereKey($ejercicio->id)->lockForUpdate()->firstOrFail();
                $anteriorImg = $ejercicio->imagen;
                $anteriorMusc = $ejercicio->imagen_musculos;
                $actualizar = [
                    'nombre' => trim($data['nombre']),
                    'grupo_muscular' => trim($data['grupo_muscular']),
                    'subgrupo' => ! empty($data['subgrupo']) ? trim($data['subgrupo']) : null,
                ];

                if ($request->hasFile('imagen')) {
                    $nuevoImg = $imagenSegura->guardar($request->file('imagen'), 'ejercicios', 'ejercicio_'.$ejercicio->id);
                    $actualizar['imagen'] = $nuevoImg;
                } elseif (! empty($data['eliminar_imagen'])) {
                    $actualizar['imagen'] = null;
                }

                if ($request->hasFile('imagen_musculos')) {
                    $nuevoMusc = $imagenSegura->guardar($request->file('imagen_musculos'), 'ejercicios', 'musculos_'.$ejercicio->id);
                    $actualizar['imagen_musculos'] = $nuevoMusc;
                } elseif (! empty($data['eliminar_imagen_musculos'])) {
                    $actualizar['imagen_musculos'] = null;
                }

                $ejercicio->update($actualizar);
            });

            if ($nuevoImg || ! empty($data['eliminar_imagen'])) {
                $imagenSegura->eliminar($anteriorImg, 'ejercicios', 'ejercicio_'.$ejercicio->id);
            }
            if ($nuevoMusc || ! empty($data['eliminar_imagen_musculos'])) {
                $imagenSegura->eliminar($anteriorMusc, 'ejercicios', 'musculos_'.$ejercicio->id);
            }
        } catch (\Throwable $e) {
            if ($nuevoImg) {
                $imagenSegura->eliminar($nuevoImg, 'ejercicios', 'ejercicio_'.$ejercicio->id);
            }
            if ($nuevoMusc) {
                $imagenSegura->eliminar($nuevoMusc, 'ejercicios', 'musculos_'.$ejercicio->id);
            }
            throw $e;
        }

        return back()->with('status', 'Ejercicio actualizado correctamente.');
    }

    /**
     * Alterna el estado activo/inactivo del ejercicio.
     */
    public function toggle(Ejercicio $ejercicio)
    {
        Gate::authorize('administrar');

        $ejercicio->activo = ! $ejercicio->activo;
        $ejercicio->save();

        $estado = $ejercicio->activo ? 'activado' : 'desactivado';

        return back()->with('status', "El ejercicio \"{$ejercicio->nombre}\" fue {$estado}.");
    }
}
