<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password as PasswordRule;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--name= : Nombre completo del administrador}
                            {--email= : Correo electrónico del administrador}
                            {--password= : Contraseña segura (mínimo 12 caracteres, mayúsculas, números y símbolos)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea el usuario Administrador inicial del sistema respetando la restricción de administrador único';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Inicialización de Administrador - Alpha Fitness ===');

        if (User::where('rol', 'Administrador')->exists()) {
            $this->error('Ya existe un usuario Administrador en el sistema.');
            $this->warn('Por seguridad y diseño de Alpha Fitness, solo puede existir una cuenta de Administrador único.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: $this->ask('Nombre del administrador');
        $email = $this->option('email') ?: $this->ask('Correo electrónico');
        $password = $this->option('password') ?: $this->secret('Contraseña (mínimo 12 caracteres, mayúsculas, números y símbolos)');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', PasswordRule::min(12)->mixedCase()->numbers()->symbols()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El formato del correo electrónico no es válido.',
            'email.unique' => 'Ya existe un usuario registrado con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 12 caracteres.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $admin = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'password_establecida' => true,
                'rol' => 'Administrador',
                'activo' => true,
            ]);

            $this->info("Administrador creado exitosamente con el ID: {$admin->id}");
            $this->line("Nombre: {$admin->name}");
            $this->line("Correo: {$admin->email}");
            $this->line('Ya puedes iniciar sesión en /login');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear el administrador.', ['exception' => get_class($e)]);
            $this->error('No se pudo crear el administrador. Comprueba la conexión y la configuración de la base de datos.');

            return self::FAILURE;
        }
    }
}
