<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\User;
use App\Rules\PasswordSinTruncamiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ClientesController extends Controller
{
    private function acceso(): void
    {
        $empleado = auth('web')->user();
        abort_unless(! auth('cliente')->check() && $empleado?->activo && in_array($empleado->rol, ['Administrador', 'Secretaria'], true), 403);
    }

    public function index(Request $request)
    {
        $this->acceso();
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $filtros['q'] ?? '';
        $clientes = Cliente::query()->when($q, fn ($query) => $query->where(fn ($query) => $query->where('nombre', 'like', "%{$q}%")->orWhere('correo', 'like', "%{$q}%")))
            ->orderBy('nombre')->paginate(15)->withQueryString();
        $entrenadores = User::where('rol', 'Entrenador')->pluck('name', 'id');

        return view('gestion.clientes.index', compact('clientes', 'entrenadores', 'q'));
    }

    public function create()
    {
        $this->acceso();

        return $this->formulario(new Cliente);
    }

    public function store(Request $request)
    {
        $this->acceso();
        $data = $this->validar($request);
        $request->validate(['password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols(), new PasswordSinTruncamiento]]);
        DB::transaction(function () use ($data, $request) {
            $this->entrenadorValido($data['entrenador_id'] ?? null);
            $cliente = Cliente::create(['nombre' => $data['nombre'], 'correo' => $data['correo'], 'password' => $request->input('password'), 'activo' => true]);
            // Solo el dato validado; el empleado nunca acepta documentos por el cliente.
            $cliente->forceFill(['entrenador_id' => $data['entrenador_id'] ?? null, 'legal_requerido' => true])->save();
        });

        return redirect()->route('gestion.clientes.index')->with('status', 'Cliente creado. El titular debe revisar y aceptar los documentos legales personalmente.');
    }

    public function edit(Cliente $cliente)
    {
        $this->acceso();

        return $this->formulario($cliente);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $this->acceso();
        $data = $this->validar($request, $cliente);
        DB::transaction(function () use ($cliente, $data) {
            $this->entrenadorValido($data['entrenador_id'] ?? null);
            $bloqueado = Cliente::whereKey($cliente->id)->lockForUpdate()->firstOrFail();
            $bloqueado->forceFill(['nombre' => $data['nombre'], 'correo' => $data['correo'], 'entrenador_id' => $data['entrenador_id'] ?? null])->save();
        });

        return redirect()->route('gestion.clientes.index')->with('status', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $this->acceso();
        $cliente->forceFill(['activo' => false, 'baneado_en' => now(), 'baneado_por' => auth('web')->id()])->save();

        return back()->with('status', 'Cliente desactivado. Sus pagos, asistencias y rutinas se conservan.');
    }

    public function reactivar(Cliente $cliente)
    {
        $this->acceso();
        $cliente->forceFill(['activo' => true, 'baneado_en' => null, 'baneado_por' => null])->save();

        return back()->with('status', 'Cliente reactivado.');
    }

    private function formulario(Cliente $cliente)
    {
        $entrenadores = User::where('rol', 'Entrenador')->where('activo', true)->orderBy('name')->get(['id', 'name']);

        return view('gestion.clientes.form', compact('cliente', 'entrenadores'));
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'correo' => ['required', 'email', 'max:255', Rule::unique('clientes', 'correo')->ignore($cliente?->id)],
            'entrenador_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('rol', 'Entrenador')->where('activo', true)],
        ], ['correo.unique' => 'Ya existe un cliente con este correo.', 'entrenador_id.exists' => 'Selecciona un entrenador activo.']);
    }

    private function entrenadorValido(?int $id): void
    {
        if ($id !== null) {
            User::whereKey($id)->where('rol', 'Entrenador')->where('activo', true)->lockForUpdate()->firstOrFail();
        }
    }
}
