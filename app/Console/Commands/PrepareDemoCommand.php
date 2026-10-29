<?php

namespace App\Console\Commands;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\Ejercicio;
use App\Models\Membresia;
use App\Models\PagoMembresia;
use App\Models\PersonalRecord;
use App\Models\PlanMembresia;
use App\Models\Producto;
use App\Models\Rutina;
use App\Models\SesionEntrenamiento;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PrepareDemoCommand extends Command
{
    protected $signature = 'demo:prepare {--force : Permite preparar la demostración fuera del entorno local}';

    protected $description = 'Prepara cuentas y registros ficticios de exposición sin alterar las cuentas existentes';

    private const ACCESS_FILE = 'demo-access.json';

    private const STAFF = [
        'secretaria.demo@alpha-fitness.example' => ['Secretaria', 'Secretaría · Demostración'],
        'entrenador.demo@alpha-fitness.example' => ['Entrenador', 'Entrenador · Demostración'],
    ];

    private const CLIENT_EMAIL = 'cliente.demo@alpha-fitness.example';

    public function handle(): int
    {
        if (! config('privacy.demo') || (! app()->environment(['local', 'testing']) && ! $this->option('force'))) {
            $this->error('La preparación requiere el modo de demostración. Fuera de local, confirma con --force.');

            return self::FAILURE;
        }

        $disk = Storage::disk('local');
        if ($disk->exists(self::ACCESS_FILE)) {
            $access = json_decode($disk->get(self::ACCESS_FILE), true);
            if (! $this->manifestIsValid($access)) {
                $this->error('El archivo privado no coincide con las cuentas. No se modificó ninguna contraseña.');

                return self::FAILURE;
            }
            $this->info('La demostración ya está preparada. No se duplicaron registros ni se cambiaron contraseñas.');
            $this->line('Accesos privados: '.$disk->path(self::ACCESS_FILE));

            return self::SUCCESS;
        }

        if (! User::where('rol', 'Administrador')->exists()) {
            $this->error('Crea primero el administrador con admin:create. Este comando nunca cambia sus credenciales.');

            return self::FAILURE;
        }
        if (User::whereIn('email', array_keys(self::STAFF))->exists() || Cliente::where('correo', self::CLIENT_EMAIL)->exists()) {
            $this->error('Ya existe una cuenta con un correo reservado de demostración. No se modificó ni se reutilizó.');

            return self::FAILURE;
        }

        $ownsAccessFile = false;
        try {
            $this->backupLocalSqlite();
            DB::transaction(function () use ($disk, &$ownsAccessFile) {
                $accounts = [];
                foreach (self::STAFF as $email => [$role, $name]) {
                    $password = Str::password(24);
                    $user = User::create(['name' => $name, 'email' => $email, 'password' => $password,
                        'rol' => $role, 'activo' => true, 'password_establecida' => true]);
                    $staff[$role] = $user;
                    $accounts[] = ['tipo' => 'personal', 'rol' => $role, 'id' => $user->id, 'correo' => $email, 'password' => $password];
                }
                $password = Str::password(24);
                $client = Cliente::create(['nombre' => 'Cliente · Demostración', 'correo' => self::CLIENT_EMAIL,
                    'password' => $password, 'activo' => true]);
                $accounts[] = ['tipo' => 'cliente', 'rol' => 'Cliente', 'id' => $client->id, 'correo' => $client->correo, 'password' => $password];
                $this->createDemonstration($client, $staff['Entrenador'], $staff['Secretaria']);
                $ownsAccessFile = true;
                $saved = $disk->put(self::ACCESS_FILE, json_encode([
                    'version' => 1, 'creado_en' => now()->toIso8601String(),
                    'instrucciones' => 'Archivo privado: no publicar ni versionar. Usa Personal para Secretaría y Entrenador; Clientes para el cliente. Confirma primero la política de privacidad. El administrador conserva sus credenciales anteriores. Si cambias una contraseña desde la aplicación, este archivo no se actualiza. Los datos son ficticios; no se enviaron correos reales.',
                    'cuentas' => $accounts,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), ['visibility' => 'private']);
                if (! $saved) {
                    throw new RuntimeException('No se pudo guardar el archivo de accesos privados.');
                }
            });
        } catch (\Throwable $error) {
            // Do not log exception payloads: they can include credentials or queries.
            if ($ownsAccessFile) {
                $disk->delete(self::ACCESS_FILE);
            }
            $this->error('No se completó la preparación. Se revirtieron sus registros; revisa la conexión y el almacenamiento privado.');

            return self::FAILURE;
        }

        $this->info('Demostración preparada: Secretaría, Entrenador y Cliente, con actividad ficticia.');
        $this->line('El administrador y las cuentas anteriores conservan sus datos y contraseñas.');
        $this->line('Accesos privados: '.$disk->path(self::ACCESS_FILE));

        return self::SUCCESS;
    }

    private function manifestIsValid(mixed $access): bool
    {
        if (! is_array($access) || ($access['version'] ?? null) !== 1 || count($access['cuentas'] ?? []) !== 3) {
            return false;
        }
        foreach ($access['cuentas'] as $account) {
            $query = ($account['tipo'] ?? '') === 'cliente'
                ? Cliente::where('correo', self::CLIENT_EMAIL)
                : User::where('email', $account['correo'] ?? '')->where('rol', $account['rol'] ?? '');
            if (! $query->whereKey($account['id'] ?? 0)->exists()) {
                return false;
            }
        }

        return true;
    }

    private function backupLocalSqlite(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() === ':memory:') {
            return;
        }
        $database = realpath(DB::connection()->getDatabaseName());
        $root = realpath(base_path()).DIRECTORY_SEPARATOR;
        if (! $database || ! str_starts_with(strtolower($database), strtolower($root))) {
            throw new RuntimeException('La base SQLite local debe estar dentro del proyecto.');
        }
        $backup = Storage::disk('local')->path('demo-before-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.sqlite');
        Storage::disk('local')->makeDirectory('.');
        $pdo = DB::connection()->getPdo();
        $pdo->exec('VACUUM INTO '.$pdo->quote($backup));
        $this->info('Respaldo consistente creado en almacenamiento privado antes de preparar la demostración.');
    }

    private function createDemonstration(Cliente $client, User $trainer, User $secretary): void
    {
        $exercises = Ejercicio::where('activo', true)->orderBy('id')->limit(3)->get();
        if ($exercises->isEmpty()) {
            foreach (['Sentadilla', 'Flexiones', 'Plancha'] as $name) {
                $exercises->push(Ejercicio::create(['nombre' => $name.' · Demostración', 'grupo_muscular' => 'Full Body', 'activo' => true]));
            }
        }
        $routine = Rutina::create(['user_type' => 'cliente', 'user_id' => $client->id,
            'nombre' => 'Fuerza inicial · Demostración', 'objetivo' => 'Práctica académica de entrenamiento guiado',
            'nivel' => 'Principiante', 'dias_por_semana' => 3, 'activa' => true,
            'asignado_por_id' => $trainer->id, 'asignado_por' => $trainer->name]);
        $day = $routine->dias()->create(['orden' => 1, 'titulo' => 'Cuerpo completo', 'duracion_estimada_min' => 25, 'duracion_estimada_max' => 35]);
        foreach ($exercises as $index => $exercise) {
            $day->ejercicios()->create(['ejercicio_id' => $exercise->id, 'orden' => $index + 1,
                'series' => 3, 'repeticiones' => '10', 'peso' => null, 'descanso_segundos' => 60]);
        }
        PersonalRecord::create(['cliente_id' => $client->id, 'ejercicio_id' => $exercises->first()->id,
            'peso_kg' => '20.00', 'repeticiones' => 10, 'notas' => 'Registro ficticio para exposición académica.']);
        SesionEntrenamiento::create(['user_type' => 'cliente', 'user_id' => $client->id,
            'rutina_id' => $routine->id, 'rutina_nombre' => $routine->nombre, 'dia_id' => $day->id, 'dia_titulo' => $day->titulo,
            'iniciado_en' => now()->subDay()->subMinutes(30), 'finalizado_en' => now()->subDay(), 'duracion_segundos' => 1800,
            'series_completadas' => $exercises->count() * 3, 'total_series' => $exercises->count() * 3,
            'estado' => 'completado', 'sesion_uuid' => (string) Str::uuid(), 'notas' => 'Sesión ficticia de demostración.']);

        $plan = PlanMembresia::firstOrCreate(['nombre' => 'Mensual · Demostración'], ['precio' => '25.00', 'duracion_dias' => 30,
            'activo' => true, 'condiciones' => 'Datos ficticios para evaluación académica; no representa un cobro real.']);
        $request = SolicitudMembresia::create(['cliente_id' => $client->id, 'plan_id' => $plan->id, 'plan_nombre' => $plan->nombre,
            'precio_acordado' => '25.00', 'duracion_dias' => 30, 'condiciones' => $plan->condiciones,
            'estado' => 'activada', 'resuelta_en' => now(), 'resuelta_por' => $secretary->id]);
        $membership = Membresia::create(['cliente_id' => $client->id, 'solicitud_id' => $request->id, 'plan' => $plan->nombre,
            'importe' => '25.00', 'inicio' => today()->subDays(2), 'fin' => today()->addDays(27), 'activada_por' => $secretary->id, 'cancelada' => false]);
        PagoMembresia::create(['membresia_id' => $membership->id, 'solicitud_id' => $request->id, 'registrado_por' => $secretary->id,
            'importe' => '25.00', 'pagado_en' => now(), 'referencia' => 'DEMO-ACADEMICA-SIN-COBRO']);
        Asistencia::create(['cliente_id' => $client->id, 'registrado_por' => $secretary->id, 'salida_registrada_por' => $secretary->id,
            'fecha_hora' => now()->subDay()->subHour(), 'fecha_salida' => now()->subDay(), 'tipo_acceso' => 'entrada']);
        $product = Producto::create(['nombre' => 'Agua · Demostración', 'precio' => '1.50', 'categoria' => 'Demostración', 'stock' => 19, 'activo' => true, 'orden' => 100]);
        $sale = Venta::create(['venta_uuid' => (string) Str::uuid(), 'user_id' => $secretary->id, 'cliente_id' => $client->id,
            'total' => '1.50', 'metodo_pago' => 'Efectivo', 'notas' => 'Venta ficticia para exposición; sin cobro real.']);
        $sale->detalles()->create(['producto_id' => $product->id, 'cantidad' => 1, 'precio_unitario' => '1.50', 'subtotal' => '1.50']);
    }
}
