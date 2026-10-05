<?php

namespace App\Http\Controllers\Gestion;

use App\Http\Controllers\Controller;
use App\Models\PlanMembresia;
use App\Support\Acceso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanesController extends Controller
{
    private function acceso(): void
    {
        abort_unless(! auth('cliente')->check() && Acceso::permite(auth('web')->user(), 'administrar'), 403);
    }

    public function index(Request $request)
    {
        $this->acceso();
        $filtros = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $q = $filtros['q'] ?? '';
        $planes = PlanMembresia::query()->when($q, fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre')->paginate(15)->withQueryString();

        return view('gestion.planes.index', compact('planes', 'q'));
    }

    public function create()
    {
        $this->acceso();

        return view('gestion.planes.form', ['plan' => new PlanMembresia]);
    }

    public function store(Request $request)
    {
        $this->acceso();
        PlanMembresia::create($this->validar($request) + ['activo' => true]);

        return redirect()->route('gestion.planes.index')->with('status', 'Plan creado.');
    }

    public function edit(PlanMembresia $plan)
    {
        $this->acceso();

        return view('gestion.planes.form', compact('plan'));
    }

    public function update(Request $request, PlanMembresia $plan)
    {
        $this->acceso();
        $plan->update($this->validar($request, $plan));

        return redirect()->route('gestion.planes.index')->with('status', 'Plan actualizado. Las solicitudes existentes conservan su precio acordado.');
    }

    public function destroy(PlanMembresia $plan)
    {
        $this->acceso();
        $plan->update(['activo' => false]);

        return back()->with('status', 'Plan desactivado. Su historial se conserva.');
    }

    public function reactivar(PlanMembresia $plan)
    {
        $this->acceso();
        $plan->update(['activo' => true]);

        return back()->with('status', 'Plan reactivado.');
    }

    private function validar(Request $request, ?PlanMembresia $plan = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('planes_membresia', 'nombre')->ignore($plan?->id)],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999.99'],
            'duracion_dias' => ['required', 'integer', 'min:1', 'max:3650'],
            'condiciones' => ['nullable', 'string', 'max:255'],
        ], ['nombre.unique' => 'Ya existe un plan con este nombre.', 'precio.decimal' => 'El precio admite hasta dos decimales.']);
    }
}
