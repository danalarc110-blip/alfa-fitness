<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CrearAdministrador extends Command
{
    protected $signature = 'alpha:crear-admin';

    protected $description = 'Crea el único administrador con una contraseña privada introducida en la consola.';

    public function handle(): int
    {
        if (User::where('rol', 'Administrador')->exists()) {
            $this->error('Ya existe un administrador. No se modificó ninguna cuenta.');

            return self::FAILURE;
        }
        if (! $this->input->isInteractive()) {
            $this->error('Ejecuta este comando en una consola interactiva. No admite contraseñas por argumentos.');

            return self::FAILURE;
        }

        $datos = [
            'name' => trim((string) $this->ask('Nombre del administrador')),
            'email' => mb_strtolower(trim((string) $this->ask('Correo del administrador'))),
            'password' => $this->secret('Contraseña (mínimo 12 caracteres, mayúscula, minúscula, número y símbolo)'),
            'password_confirmation' => $this->secret('Repite la contraseña'),
        ];
        $validacion = Validator::make($datos, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'max:72', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols(), function ($atributo, $valor, $fallar) {
                if (strlen((string) $valor) > 72) {
                    $fallar('La contraseña es demasiado larga para guardarse completa; usa menos caracteres.');
                }
            }],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo debe ser válido.',
            'email.unique' => 'Ya existe una cuenta del personal con ese correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
            'password.max' => 'La contraseña no puede superar 72 caracteres.',
            'password.mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.',
            'password.numbers' => 'La contraseña debe incluir un número.',
            'password.symbols' => 'La contraseña debe incluir un símbolo.',
        ]);
        if ($validacion->fails()) {
            foreach ($validacion->errors()->all() as $mensaje) {
                $this->error($mensaje);
            }

            return self::FAILURE;
        }

        try {
            $creado = DB::transaction(function () use ($datos) {
                if (User::where('rol', 'Administrador')->exists()) {
                    return false;
                }
                User::create([
                    'name' => $datos['name'], 'email' => $datos['email'], 'password' => $datos['password'],
                    'rol' => 'Administrador', 'activo' => true, 'password_establecida' => true,
                ]);

                return true;
            });
        } catch (QueryException $exception) {
            // A concurrent creation is rejected by the unique admin/email indexes.
            // Query messages contain bindings, so do not display or log the exception.
            $this->error('No se pudo crear el administrador. Comprueba la base de datos y si la cuenta ya existe.');

            return self::FAILURE;
        }
        if (! $creado) {
            $this->error('Ya existe un administrador. No se modificó ninguna cuenta.');

            return self::FAILURE;
        }

        $this->info('Administrador creado. Inicia sesión desde la pestaña Personal con el correo y contraseña introducidos.');

        return self::SUCCESS;
    }
}
