<?php

namespace App\Http\Controllers;

use App\Models\PlanMembresia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PlanMembresiaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('administrar');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('planes_membresia', 'nombre')],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999.99'],
            'duracion_dias' => ['required', 'integer', 'min:1', 'max:3650'],
            'condiciones' => ['nullable', 'string', 'max:255'],
        ]);

        PlanMembresia::create($data + ['activo' => true]);

        return back()->with('status', "Plan '{$data['nombre']}' creado exitosamente.");
    }

    public function update(Request $request, PlanMembresia $plan): RedirectResponse
    {
        Gate::authorize('administrar');

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('planes_membresia', 'nombre')->ignore($plan->id)],
            'precio' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999.99'],
            'duracion_dias' => ['required', 'integer', 'min:1', 'max:3650'],
            'condiciones' => ['nullable', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $plan->update($data);

        return back()->with('status', "Plan '{$plan->nombre}' actualizado exitosamente.");
    }

    public function toggle(PlanMembresia $plan): RedirectResponse
    {
        Gate::authorize('administrar');

        $plan->update(['activo' => ! $plan->activo]);
        $estado = $plan->activo ? 'activado' : 'desactivado';

        return back()->with('status', "Plan '{$plan->nombre}' {$estado}.");
    }
}
