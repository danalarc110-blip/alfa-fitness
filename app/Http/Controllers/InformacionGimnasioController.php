<?php

namespace App\Http\Controllers;

use App\Support\Acceso;
use App\Support\InformacionGimnasio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InformacionGimnasioController extends Controller
{
    public function actualizar(Request $request): RedirectResponse
    {
        ['user' => $user] = $this->actual();
        abort_unless(Acceso::permite($user, 'administrar'), 403);

        $data = $request->validate([
            'gimnasio' => ['required', 'array:nombre,descripcion,capacidad,horarios,instalaciones,direccion,telefono,correo,atencion'],
            'gimnasio.nombre' => ['required', 'string', 'max:100'],
            'gimnasio.descripcion' => ['required', 'string', 'max:1200'],
            'gimnasio.capacidad' => ['required', 'integer', 'min:1', 'max:10000'],
            'gimnasio.horarios' => ['required', 'string', 'max:2000'],
            'gimnasio.instalaciones' => ['required', 'string', 'max:3000'],
            'gimnasio.direccion' => ['present', 'nullable', 'string', 'max:500'],
            'gimnasio.telefono' => ['present', 'nullable', 'string', 'max:40', 'regex:/^[0-9+().\s-]+$/u'],
            'gimnasio.correo' => ['present', 'nullable', 'email:rfc', 'max:254'],
            'gimnasio.atencion' => ['required', 'string', 'max:2000'],
        ], [
            'required' => 'Completa el campo :attribute.',
            'present' => 'Incluye el campo :attribute, aunque quede vacío.',
            'string' => 'El campo :attribute debe ser texto.',
            'max.string' => 'El campo :attribute admite como máximo :max caracteres.',
            'gimnasio.capacidad.integer' => 'El aforo máximo debe ser un número entero.',
            'gimnasio.capacidad.min' => 'El aforo máximo debe ser al menos 1 persona.',
            'gimnasio.capacidad.max' => 'El aforo máximo no puede superar 10000 personas.',
            'gimnasio.array' => 'La información del gimnasio contiene campos no permitidos.',
            'gimnasio.telefono.regex' => 'El teléfono solo admite números, espacios, +, guiones y paréntesis.',
            'gimnasio.correo.email' => 'Escribe un correo de contacto válido.',
        ], [
            'gimnasio.nombre' => 'nombre del gimnasio',
            'gimnasio.descripcion' => 'descripción',
            'gimnasio.capacidad' => 'aforo máximo',
            'gimnasio.horarios' => 'horarios',
            'gimnasio.instalaciones' => 'instalaciones y servicios',
            'gimnasio.direccion' => 'dirección',
            'gimnasio.telefono' => 'teléfono',
            'gimnasio.correo' => 'correo de contacto',
            'gimnasio.atencion' => 'atención en recepción',
        ]);

        $data['gimnasio']['capacidad'] = (int) $data['gimnasio']['capacidad'];
        InformacionGimnasio::guardar($data['gimnasio']);

        return redirect()->to(route('configuracion').'#gimnasio')
            ->with('status', 'Información del gimnasio guardada. Ya está disponible en la página pública.');
    }
}
