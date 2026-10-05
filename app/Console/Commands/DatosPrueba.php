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
use App\Models\RutinaDia;
use App\Models\RutinaEjercicio;
use App\Models\SolicitudMembresia;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatosPrueba extends Command
{
    protected $signature = 'alpha:datos-prueba {--con-venta : Crea una venta ficticia de una unidad de un producto exclusivo de demostración}';

    protected $description = 'Agrega datos ficticios sin borrar ni sustituir existentes, solo en local/testing.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Los datos de prueba solo se permiten en APP_ENV=local o testing. No se modificó la base.');

            return self::FAILURE;
        }

        $credenciales = [];
        try {
            DB::transaction(function () use (&$credenciales) {
                if (! User::where('rol', 'Administrador')->exists()) {
                    $this->cuentaPersonal('admin.demo@alpha.example.test', 'Administrador Demo Alpha', 'Administrador', $credenciales);
                }
                $secretaria = $this->cuentaPersonal('secretaria.demo@alpha.example.test', 'Secretaria Demo Alpha', 'Secretaria', $credenciales);
                $this->cuentaPersonal('entrenador.demo@alpha.example.test', 'Entrenador Demo Alpha', 'Entrenador', $credenciales);

                $correoCliente = 'cliente.demo@alpha.example.test';
                $cliente = Cliente::where('correo', $correoCliente)->first();
                if (! $cliente) {
                    $clave = 'Aa1!'.Str::random(28);
                    $cliente = Cliente::create(['nombre' => 'Cliente Demo Alpha', 'correo' => $correoCliente, 'password' => $clave, 'activo' => true]);
                    $credenciales[] = ['Clientes', $correoCliente, $clave];
                }
                if (! $cliente->activo) {
                    throw new \RuntimeException('La cuenta cliente de demostración está inactiva. No se reactivó ni se cambiaron sus datos.');
                }

                $ejercicio = Ejercicio::firstOrCreate(['nombre' => 'Ejercicio Demo Alpha en máquina'], ['grupo_muscular' => 'Pecho', 'subgrupo' => 'Demostración', 'activo' => true]);
                $plan = PlanMembresia::firstOrCreate(['nombre' => 'Plan Demo Alpha'], ['precio' => 1, 'duracion_dias' => 30, 'condiciones' => 'Solo datos ficticios de demostración.', 'activo' => true]);
                $solicitud = SolicitudMembresia::firstOrCreate(['cliente_id' => $cliente->id, 'plan_id' => $plan->id], [
                    'plan_nombre' => $plan->nombre, 'precio_acordado' => $plan->precio, 'duracion_dias' => $plan->duracion_dias,
                    'condiciones' => 'Solo datos ficticios de demostración.', 'estado' => 'activada', 'resuelta_en' => now(), 'resuelta_por' => $secretaria->id,
                ]);
                $membresia = Membresia::firstOrCreate(['solicitud_id' => $solicitud->id], [
                    'cliente_id' => $cliente->id, 'plan' => $solicitud->plan_nombre, 'importe' => $solicitud->precio_acordado,
                    'inicio' => today(), 'fin' => today()->addDays(29), 'cancelada' => false, 'activada_por' => $secretaria->id,
                ]);
                PagoMembresia::firstOrCreate(['solicitud_id' => $solicitud->id], [
                    'membresia_id' => $membresia->id, 'registrado_por' => $secretaria->id, 'importe' => $membresia->importe,
                    'pagado_en' => now(), 'referencia' => 'ALPHA-DEMO-V1', 'metodo_pago' => 'efectivo',
                ]);
                Asistencia::firstOrCreate(['cliente_id' => $cliente->id, 'tipo_acceso' => 'entrada'], [
                    'registrado_por' => $secretaria->id, 'fecha_hora' => now()->subHours(2),
                    'fecha_salida' => now()->subHour(), 'salida_registrada_por' => $secretaria->id,
                ]);
                $rutina = Rutina::firstOrCreate(['user_type' => 'cliente', 'user_id' => $cliente->id, 'nombre' => 'Rutina Demo Alpha'], [
                    'objetivo' => 'Ganar masa muscular', 'nivel' => 'Principiante', 'dias_por_semana' => 1, 'activa' => true,
                ]);
                $dia = RutinaDia::firstOrCreate(['rutina_id' => $rutina->id, 'orden' => 1], ['titulo' => 'Día de demostración']);
                RutinaEjercicio::firstOrCreate(['rutina_dia_id' => $dia->id, 'ejercicio_id' => $ejercicio->id], [
                    'orden' => 1, 'series' => 3, 'repeticiones' => '8', 'peso' => 20, 'descanso_segundos' => 60,
                ]);
                PersonalRecord::firstOrCreate(['cliente_id' => $cliente->id, 'ejercicio_id' => $ejercicio->id, 'notas' => 'Dato ficticio de demostración Alpha.'], ['peso_kg' => 20, 'repeticiones' => 8]);

                if ($this->option('con-venta')) {
                    $this->ventaDemo($cliente, $secretaria);
                }
            });
        } catch (QueryException $exception) {
            // Do not print bindings: account creation includes password hashes.
            $this->error('No se completaron los datos de prueba. La transacción se revirtió; comprueba las migraciones y las cuentas existentes.');

            return self::FAILURE;
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Datos ficticios listos. Los registros existentes y sus contraseñas no se sustituyeron.');
        if ($credenciales) {
            $this->warn('Contraseñas aleatorias de cuentas NUEVAS: se muestran una sola vez. Guárdalas en un gestor de contraseñas, no en documentos ni GitHub.');
            $this->table(['Pestaña', 'Correo ficticio', 'Contraseña nueva'], $credenciales);
        } else {
            $this->line('No se crearon cuentas nuevas; utiliza las contraseñas que guardaste la primera vez.');
        }

        return self::SUCCESS;
    }

    private function cuentaPersonal(string $correo, string $nombre, string $rol, array &$credenciales): User
    {
        $usuario = User::where('email', $correo)->first();
        if ($usuario) {
            if ($usuario->rol !== $rol || ! $usuario->activo) {
                throw new \RuntimeException('Una cuenta de demostración existente tiene otro rol o está inactiva. No se cambió su configuración.');
            }

            return $usuario;
        }
        $clave = 'Aa1!'.Str::random(28);
        $usuario = User::create(['name' => $nombre, 'email' => $correo, 'password' => $clave, 'rol' => $rol, 'activo' => true, 'password_establecida' => true]);
        $credenciales[] = ['Personal', $correo, $clave];

        return $usuario;
    }

    private function ventaDemo(Cliente $cliente, User $secretaria): void
    {
        if (Venta::where('cliente_id', $cliente->id)->where('notas', 'ALPHA-DEMO-V1')->exists()) {
            return;
        }
        if (! Producto::where('nombre', 'Producto Demo Alpha')->exists() && Producto::count() >= 5) {
            throw new \RuntimeException('El catálogo ya tiene cinco productos. La venta de prueba necesita una base aislada con espacio para su producto exclusivo; no se borró ningún producto ni se usó inventario real.');
        }
        $producto = Producto::firstOrCreate(['nombre' => 'Producto Demo Alpha'], ['precio' => 1, 'categoria' => 'Demostración', 'stock' => 5, 'activo' => true, 'orden' => 999]);
        $producto = Producto::whereKey($producto->id)->lockForUpdate()->firstOrFail();
        if (! $producto->activo || $producto->stock < 1) {
            throw new \RuntimeException('El producto de demostración no está disponible. No se modificó su inventario.');
        }
        $venta = Venta::create(['user_id' => $secretaria->id, 'cliente_id' => $cliente->id, 'total' => $producto->precio, 'metodo_pago' => 'Efectivo', 'notas' => 'ALPHA-DEMO-V1']);
        $venta->detalles()->create(['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => $producto->precio, 'subtotal' => $producto->precio]);
        $producto->decrement('stock');
    }
}
