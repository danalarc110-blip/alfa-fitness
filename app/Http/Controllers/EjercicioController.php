<?php

namespace App\Http\Controllers;

use App\Models\Ejercicio;
use App\Models\EjercicioCalificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EjercicioController extends Controller
{
    /**
     * Identifica al usuario autenticado (sea de la guardia web o cliente).
     */

    /**
     * Muestra la vista principal de Ejercicios Populares de la Semana.
     */
    public function index(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();

        $filtros = $request->validate(['grupo' => ['nullable', 'string', 'max:100'], 'q' => ['nullable', 'string', 'max:100']]);
        $grupo = $filtros['grupo'] ?? '';
        $q = $filtros['q'] ?? '';

        $ejerciciosQuery = Ejercicio::where('activo', true)
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
}
