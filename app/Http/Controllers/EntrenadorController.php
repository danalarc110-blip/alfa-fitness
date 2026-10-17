<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\EntrenadorCalificacion;
use App\Models\Rutina;
use App\Models\User;
use App\Services\ClientesEntrenador;
use App\Services\ImagenSegura;
use App\Services\Invitaciones;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EntrenadorController extends Controller
{
    public function index(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'rol' => ['nullable', 'string', Rule::in(['Todos', 'Entrenador', 'Secretaria'])],
        ]);
        $busqueda = trim($filtros['q'] ?? '');
        $filtroRol = $filtros['rol'] ?? 'Todos';
        $esAdmin = $guard === 'web' && $user->rol === 'Administrador';

        $query = User::query()->orderBy('name');
        if ($esAdmin) {
            if ($filtroRol && $filtroRol !== 'Todos') {
                $query->where('rol', $filtroRol);
            } else {
                $query->whereIn('rol', ['Entrenador', 'Secretaria']);
            }
        } else {
            $query->where('rol', 'Entrenador')->where('activo', true);
        }

        $query->when($busqueda !== '', fn ($builder) => $builder->where(function ($search) use ($busqueda, $esAdmin) {
            $search->where('name', 'like', "%{$busqueda}%");
            if ($esAdmin) {
                $search->orWhere('email', 'like', "%{$busqueda}%");
            }
        }));

        $entrenadores = $query
            ->withAvg('calificacionesEntrenador as promedio_estrellas', 'estrellas')
            ->withCount('calificacionesEntrenador as conteo_votos')
            ->paginate(9)->withQueryString();
        $misVotos = $guard === 'cliente'
            ? EntrenadorCalificacion::where('cliente_id', $user->id)
                ->whereIn('entrenador_id', $entrenadores->pluck('id'))->get()->keyBy('entrenador_id')
            : collect();
        $vinculados = $guard === 'cliente'
            ? app(ClientesEntrenador::class)->entrenadoresDelCliente($user->id)
            : [];
        // Esta sección es independiente de la búsqueda y de la página del directorio.
        $misEntrenadores = $guard === 'cliente'
            ? User::whereIn('id', $vinculados)->where('rol', 'Entrenador')->where('activo', true)
                ->orderBy('name')->get(['id', 'name', 'avatar'])
            : collect();

        return view('entrenadores.index', [
            'guard' => $guard,
            'entrenadores' => $entrenadores,
            'misVotos' => $misVotos,
            'vinculados' => $vinculados,
            'misEntrenadores' => $misEntrenadores,
            'busqueda' => $busqueda,
            'filtroRol' => $filtroRol,
            'esAdmin' => $esAdmin,
            'correoSinEntrega' => $esAdmin && $this->correoSinEntrega(),
        ]);
    }

    public function calificar(Request $request, User $entrenador): JsonResponse|RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'cliente' && $user instanceof Cliente, 403);
        abort_unless($entrenador->rol === 'Entrenador' && $entrenador->activo, 404);
        abort_unless(app(ClientesEntrenador::class)->vinculado($entrenador->id, $user->id), 403,
            'Solo puedes calificar a los entrenadores que te han asignado una rutina.');

        $data = $request->validate(['estrellas' => ['required', 'integer', 'min:1', 'max:5']]);

        // La clave única y el upsert hacen que los reintentos actualicen el mismo voto.
        EntrenadorCalificacion::upsert([
            ['entrenador_id' => $entrenador->id, 'cliente_id' => $user->id, 'estrellas' => $data['estrellas']],
        ], ['entrenador_id', 'cliente_id'], ['estrellas', 'updated_at']);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'estrellas' => (int) $data['estrellas'],
                'promedio' => round((float) $entrenador->calificacionesEntrenador()->avg('estrellas'), 1),
                'total_votos' => $entrenador->calificacionesEntrenador()->count(),
            ]);
        }

        return back()->with('status', 'Tu valoración del entrenador se guardó. Puedes actualizarla cuando quieras.');
    }

    public function misClientes(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web' && in_array($user->rol, ['Administrador', 'Entrenador'], true), 403);
        $esAdmin = $user->rol === 'Administrador';
        abort_if(! $esAdmin && $request->filled('entrenador_id'), 403);
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'entrenador_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('rol', 'Entrenador')],
        ]);
        $busqueda = trim($data['q'] ?? '');
        $entrenadorId = isset($data['entrenador_id']) ? (int) $data['entrenador_id'] : null;
        $query = $esAdmin ? Cliente::query() : app(ClientesEntrenador::class)->clientes($user->id);
        if ($esAdmin && $entrenadorId) {
            $query = app(ClientesEntrenador::class)->clientes($entrenadorId);
        }
        $clientes = $query->select(['id', 'nombre', 'avatar', 'activo'])
            ->when($busqueda !== '', fn ($q) => $q->where('nombre', 'like', "%{$busqueda}%"))
            ->with(['rutinas' => fn ($q) => $q
                ->when(! $esAdmin || $entrenadorId, fn ($q) => $q->where('asignado_por_id', $esAdmin ? $entrenadorId : $user->id))
                ->orderBy('nombre')])
            ->orderBy('nombre')->paginate(12)->withQueryString();
        $entrenadores = $esAdmin
            ? User::where('rol', 'Entrenador')->orderBy('name')->get(['id', 'name', 'activo'])
            : collect();
        $responsables = $esAdmin
            ? User::whereIn('id', $clientes->getCollection()->flatMap(fn ($cliente) => $cliente->rutinas->pluck('asignado_por_id')))
                ->pluck('name', 'id')
            : collect();

        return view('entrenador.clientes', compact('guard', 'clientes', 'busqueda', 'esAdmin', 'entrenadores', 'entrenadorId', 'responsables'));
    }

    public function cliente(Cliente $cliente)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web' && in_array($user->rol, ['Administrador', 'Entrenador'], true), 403);
        $esAdmin = $user->rol === 'Administrador';
        $vinculacion = app(ClientesEntrenador::class);
        abort_unless($esAdmin || $vinculacion->vinculado($user->id, $cliente->id), 403,
            'No tienes asignado a este cliente.');
        $rutinas = ($esAdmin ? Rutina::deUsuario('cliente', $cliente->id) : $vinculacion->rutinas($user->id)->where('user_id', $cliente->id))
            ->with('dias.ejercicios.ejercicio')->orderBy('nombre')->get();

        return view('entrenador.cliente', compact('guard', 'cliente', 'rutinas', 'esAdmin'));
    }

    public function store(Request $request)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web' && $user->rol === 'Administrador', 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'rol' => ['nullable', 'string', Rule::in(['Entrenador', 'Secretaria'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
        ]);
        $rolAsignado = $data['rol'] ?? 'Entrenador';
        $nuevo = null;
        $usuario = null;
        try {
            $personal = DB::transaction(function () use ($data, $request, $rolAsignado, &$nuevo, &$usuario) {
                $usuario = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Str::random(64),
                    'password_establecida' => false,
                    'rol' => $rolAsignado,
                    'activo' => true,
                ]);
                if ($request->hasFile('avatar')) {
                    $nuevo = app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$usuario->id);
                    $usuario->update(['avatar' => $nuevo]);
                }

                return $usuario;
            });
        } catch (\Throwable $error) {
            if ($nuevo) {
                app(ImagenSegura::class)->eliminar($nuevo, 'avatars', 'web_'.$usuario->id);
            }
            throw $error;
        }
        $resultado = app(Invitaciones::class)->enviar($personal);

        return back()->with('status', $resultado
            ? "Personal ({$rolAsignado}) creado exitosamente. ".($this->correoSinEntrega()
                ? 'La invitación se generó en modo de prueba y no se entregará por correo. Configura el envío para que el colaborador pueda crear su contraseña.'
                : 'Se envió el enlace privado para establecer la contraseña.')
            : "Personal ({$rolAsignado}) creado; revise el correo para poder enviar la invitación.");
    }

    private function correoSinEntrega(): bool
    {
        return in_array(config('mail.mailers.'.config('mail.default').'.transport'), ['log', 'array'], true);
    }

    public function invitar(User $entrenador): RedirectResponse
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web' && $user->rol === 'Administrador', 403);
        abort_unless(in_array($entrenador->rol, ['Entrenador', 'Secretaria'], true), 404);
        if (! $entrenador->activo || $entrenador->password_establecida) {
            return back()->with('error', 'Solo puedes invitar al personal activo que aún no ha establecido su contraseña.');
        }

        return app(Invitaciones::class)->enviar($entrenador)
            ? back()->with('status', $this->correoSinEntrega()
                ? 'La invitación se generó en modo de prueba y no se entregará por correo. Configura el envío para que el colaborador pueda crear su contraseña.'
                : 'Invitación enviada. El colaborador podrá crear su contraseña e ingresar desde Personal.')
            : back()->with('error', 'No se pudo reenviar la invitación. Espera un minuto e inténtalo de nuevo; si persiste, revisa la configuración del correo.');
    }

    public function update(Request $request, User $entrenador)
    {
        ['guard' => $guard, 'user' => $user] = $this->actual();
        abort_unless($guard === 'web' && $user->rol === 'Administrador' && in_array($entrenador->rol, ['Entrenador', 'Secretaria'], true), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($entrenador->id)],
            'rol' => ['nullable', 'string', Rule::in(['Entrenador', 'Secretaria'])],
            'activo' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=5000,max_height=5000'],
            'eliminar_avatar' => ['nullable', 'boolean'],
        ]);
        $rolNuevo = $data['rol'] ?? $entrenador->rol;
        $anterior = $entrenador->avatar;
        $nuevo = null;
        if ($request->hasFile('avatar')) {
            $nuevo = app(ImagenSegura::class)->guardar($request->file('avatar'), 'avatars', 'web_'.$entrenador->id);
        }
        try {
            DB::transaction(function () use ($entrenador, $data, $rolNuevo, $nuevo, $request, &$anterior) {
                $entrenador = User::whereKey($entrenador->id)->lockForUpdate()->firstOrFail();
                abort_unless(in_array($entrenador->rol, ['Entrenador', 'Secretaria'], true), 403);
                $anterior = $entrenador->avatar;
                $entrenador->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'rol' => $rolNuevo,
                    'activo' => $data['activo'],
                    'avatar' => $nuevo ?: ($request->boolean('eliminar_avatar') ? null : $anterior),
                ]);
            });
        } catch (\Throwable $e) {
            if ($nuevo) {
                app(ImagenSegura::class)->eliminar($nuevo, 'avatars', 'web_'.$entrenador->id);
            }
            throw $e;
        }
        if ($nuevo || $request->boolean('eliminar_avatar')) {
            app(ImagenSegura::class)->eliminar($anterior, 'avatars', 'web_'.$entrenador->id);
        }

        return back()->with('status', 'Personal actualizado.');
    }
}
