<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Cliente;
use App\Models\DetalleVenta;
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
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $timezone = config('app.timezone', 'America/El_Salvador');
        $hoy = Carbon::now($timezone);

        // 1. Administrador único
        $admin = User::firstOrCreate(
            ['email' => 'admin@alphafitness.test'],
            [
                'name' => 'Director Administrador',
                'password' => Hash::make('Admin!Alpha2026'),
                'password_establecida' => true,
                'rol' => 'Administrador',
                'activo' => true,
            ]
        );

        // 2. Personal (Secretaria y Entrenadores)
        $secretaria = User::firstOrCreate(
            ['email' => 'secretaria@alphafitness.test'],
            [
                'name' => 'Laura Martinez',
                'password' => Hash::make('Secretaria!Alpha2026'),
                'password_establecida' => true,
                'rol' => 'Secretaria',
                'activo' => true,
            ]
        );

        $coachCarlos = User::firstOrCreate(
            ['email' => 'carlos.coach@alphafitness.test'],
            [
                'name' => 'Carlos Mendoza',
                'password' => Hash::make('Entrenador!Alpha2026'),
                'password_establecida' => true,
                'rol' => 'Entrenador',
                'activo' => true,
            ]
        );

        $coachValeria = User::firstOrCreate(
            ['email' => 'valeria.coach@alphafitness.test'],
            [
                'name' => 'Valeria Ortiz',
                'password' => Hash::make('Entrenador!Alpha2026'),
                'password_establecida' => true,
                'rol' => 'Entrenador',
                'activo' => true,
            ]
        );

        // 3. Clientes (8 miembros)
        $clientesData = [
            ['nombre' => 'Mateo Morales', 'correo' => 'mateo.morales@alphafitness.test'],
            ['nombre' => 'Sofia Hernandez', 'correo' => 'sofia.hernandez@alphafitness.test'],
            ['nombre' => 'Diego Ramirez', 'correo' => 'diego.ramirez@alphafitness.test'],
            ['nombre' => 'Valentina Gomez', 'correo' => 'valentina.gomez@alphafitness.test'],
            ['nombre' => 'Alejandro Cruz', 'correo' => 'alejandro.cruz@alphafitness.test'],
            ['nombre' => 'Camila Torres', 'correo' => 'camila.torres@alphafitness.test'],
            ['nombre' => 'Daniela Castro', 'correo' => 'daniela.castro@alphafitness.test'],
            ['nombre' => 'Sebastian Reyes', 'correo' => 'sebastian.reyes@alphafitness.test'],
        ];

        $clientes = [];
        foreach ($clientesData as $c) {
            $clientes[] = Cliente::firstOrCreate(
                ['correo' => $c['correo']],
                [
                    'nombre' => $c['nombre'],
                    'password' => Hash::make('Cliente!Alpha2026'),
                    'activo' => true,
                ]
            );
        }

        // 4. Membresías y Pagos para los 8 clientes
        $planes = PlanMembresia::where('activo', true)->get();
        if ($planes->isEmpty()) {
            $planes = collect([
                PlanMembresia::create(['nombre' => 'Mensual', 'precio' => 25.00, 'duracion_dias' => 30, 'activo' => true]),
                PlanMembresia::create(['nombre' => 'Trimestral', 'precio' => 65.00, 'duracion_dias' => 90, 'activo' => true]),
                PlanMembresia::create(['nombre' => 'Anual', 'precio' => 220.00, 'duracion_dias' => 365, 'activo' => true]),
            ]);
        }

        $metodos = ['Efectivo', 'Tarjeta', 'Transferencia'];
        foreach ($clientes as $index => $cli) {
            $plan = $planes[$index % $planes->count()];
            $fechaPago = $hoy->copy()->startOfMonth()->addDays($index);

            // Crear solicitud aprobada
            $solicitud = SolicitudMembresia::firstOrCreate(
                ['cliente_id' => $cli->id, 'plan_id' => $plan->id],
                [
                    'plan_nombre' => $plan->nombre,
                    'precio_acordado' => $plan->precio,
                    'duracion_dias' => $plan->duracion_dias,
                    'estado' => 'aprobada',
                    'resuelta_en' => $fechaPago,
                    'resuelta_por' => $secretaria->id,
                    'created_at' => $fechaPago,
                    'updated_at' => $fechaPago,
                ]
            );

            // Crear membresía activa
            $membresia = Membresia::firstOrCreate(
                ['solicitud_id' => $solicitud->id],
                [
                    'cliente_id' => $cli->id,
                    'plan' => $plan->nombre,
                    'importe' => $plan->precio,
                    'inicio' => $fechaPago->toDateString(),
                    'fin' => $fechaPago->copy()->addDays($plan->duracion_dias)->toDateString(),
                    'cancelada' => false,
                    'activada_por' => $secretaria->id,
                    'created_at' => $fechaPago,
                    'updated_at' => $fechaPago,
                ]
            );

            // Pago asociado
            PagoMembresia::firstOrCreate(
                ['membresia_id' => $membresia->id],
                [
                    'solicitud_id' => $solicitud->id,
                    'registrado_por' => $secretaria->id,
                    'importe' => $plan->precio,
                    'pagado_en' => $fechaPago,
                    'referencia' => $metodos[$index % count($metodos)],
                    'created_at' => $fechaPago,
                    'updated_at' => $fechaPago,
                ]
            );
        }

        // 5. Asistencias (20 registros)
        // 16 visitas cerradas en diferentes días y horarios para generar el gráfico de horas pico
        $horasPico = [8, 9, 9, 10, 11, 14, 17, 17, 18, 18, 18, 19, 19, 20, 20, 21];
        for ($i = 0; $i < 16; $i++) {
            $cli = $clientes[$i % count($clientes)];
            $diaVisita = $hoy->copy()->subDays(15 - $i);
            $horaEntrada = $diaVisita->copy()->setHour($horasPico[$i])->setMinute(15);
            $horaSalida = $horaEntrada->copy()->addMinutes(75);

            Asistencia::create([
                'cliente_id' => $cli->id,
                'registrado_por' => $secretaria->id,
                'fecha_hora' => $horaEntrada,
                'fecha_salida' => $horaSalida,
                'salida_registrada_por' => $secretaria->id,
                'created_at' => $horaEntrada,
                'updated_at' => $horaSalida,
            ]);
        }

        // 4 visitas abiertas hoy (para que el medidor de Aforo en vivo marque personas dentro)
        for ($j = 0; $j < 4; $j++) {
            $cli = $clientes[$j];
            $entradaHoy = $hoy->copy()->subMinutes(40 - ($j * 10));

            Asistencia::create([
                'cliente_id' => $cli->id,
                'registrado_por' => $secretaria->id,
                'fecha_hora' => $entradaHoy,
                'fecha_salida' => null,
                'salida_registrada_por' => null,
                'created_at' => $entradaHoy,
                'updated_at' => $entradaHoy,
            ]);
        }

        // 6. Ventas TPV (20 ventas con detalle)
        $productos = Producto::where('activo', true)->get();
        if ($productos->isNotEmpty()) {
            $ventasConfig = [
                ['dia' => 1, 'prod_idx' => 0, 'cant' => 2, 'metodo' => 'Efectivo'],
                ['dia' => 1, 'prod_idx' => 1, 'cant' => 1, 'metodo' => 'Tarjeta'],
                ['dia' => 2, 'prod_idx' => 2, 'cant' => 3, 'metodo' => 'Efectivo'],
                ['dia' => 3, 'prod_idx' => 3, 'cant' => 1, 'metodo' => 'Transferencia'],
                ['dia' => 3, 'prod_idx' => 0, 'cant' => 4, 'metodo' => 'Efectivo'],
                ['dia' => 4, 'prod_idx' => 4, 'cant' => 1, 'metodo' => 'Tarjeta'],
                ['dia' => 5, 'prod_idx' => 1, 'cant' => 2, 'metodo' => 'Efectivo'],
                ['dia' => 5, 'prod_idx' => 2, 'cant' => 2, 'metodo' => 'Tarjeta'],
                ['dia' => 6, 'prod_idx' => 0, 'cant' => 5, 'metodo' => 'Efectivo'],
                ['dia' => 6, 'prod_idx' => 3, 'cant' => 1, 'metodo' => 'Tarjeta'],
                ['dia' => 7, 'prod_idx' => 1, 'cant' => 3, 'metodo' => 'Transferencia'],
                ['dia' => 7, 'prod_idx' => 4, 'cant' => 1, 'metodo' => 'Efectivo'],
                ['dia' => 8, 'prod_idx' => 2, 'cant' => 4, 'metodo' => 'Tarjeta'],
                ['dia' => 8, 'prod_idx' => 0, 'cant' => 3, 'metodo' => 'Efectivo'],
                ['dia' => 9, 'prod_idx' => 1, 'cant' => 2, 'metodo' => 'Transferencia'],
                ['dia' => 9, 'prod_idx' => 3, 'cant' => 1, 'metodo' => 'Tarjeta'],
                ['dia' => 10, 'prod_idx' => 0, 'cant' => 4, 'metodo' => 'Efectivo'],
                ['dia' => 10, 'prod_idx' => 4, 'cant' => 1, 'metodo' => 'Transferencia'],
                ['dia' => 11, 'prod_idx' => 2, 'cant' => 2, 'metodo' => 'Efectivo'],
                ['dia' => 11, 'prod_idx' => 1, 'cant' => 3, 'metodo' => 'Tarjeta'],
            ];

            foreach ($ventasConfig as $k => $vc) {
                $fechaVenta = $hoy->copy()->startOfMonth()->addDays($vc['dia'] - 1)->setHour(14)->setMinute($k * 2);
                $prod = $productos[$vc['prod_idx'] % $productos->count()];
                $cant = $vc['cant'];
                $total = round($prod->precio * $cant, 2);
                $cli = $clientes[$k % count($clientes)];

                $venta = Venta::create([
                    'user_id' => ($k % 2 === 0) ? $secretaria->id : $admin->id,
                    'cliente_id' => $cli->id,
                    'total' => $total,
                    'metodo_pago' => $vc['metodo'],
                    'venta_uuid' => (string) Str::uuid(),
                    'created_at' => $fechaVenta,
                    'updated_at' => $fechaVenta,
                ]);

                DetalleVenta::create([
                    'venta_id' => $venta->id,
                    'producto_id' => $prod->id,
                    'cantidad' => $cant,
                    'precio_unitario' => $prod->precio,
                    'subtotal' => $total,
                    'created_at' => $fechaVenta,
                    'updated_at' => $fechaVenta,
                ]);
            }
        }

        // 7. Récords Personales / Progreso (20 PRs para que los gráficos de fuerza y podio se vean geniales)
        $ejercicios = Ejercicio::where('activo', true)->get();
        if ($ejercicios->isNotEmpty()) {
            $mateo = $clientes[0];
            $ejPressBanca = $ejercicios->firstWhere('nombre', 'Press de banca') ?? $ejercicios[0];
            $ejInclinado = $ejercicios->firstWhere('nombre', 'Press inclinado con mancuernas') ?? ($ejercicios[1] ?? $ejercicios[0]);
            $ejAperturas = $ejercicios->firstWhere('nombre', 'Aperturas en máquina') ?? ($ejercicios[2] ?? $ejercicios[0]);
            $ejCruces = $ejercicios->firstWhere('nombre', 'Cruces en polea') ?? ($ejercicios[3] ?? $ejercicios[0]);

            // Progresión de Mateo en Press de Banca (8 registros de evolución histórica)
            $progresionBanca = [
                ['diasAtras' => 30, 'peso' => 60.0, 'reps' => 10, 'rpe' => 7.5, 'nota' => 'Semana de inicio'],
                ['diasAtras' => 26, 'peso' => 65.0, 'reps' => 10, 'rpe' => 8.0, 'nota' => 'Buena velocidad'],
                ['diasAtras' => 22, 'peso' => 70.0, 'reps' => 8, 'rpe' => 8.0, 'nota' => 'Adaptación'],
                ['diasAtras' => 18, 'peso' => 75.0, 'reps' => 8, 'rpe' => 8.5, 'nota' => 'Carga controlada'],
                ['diasAtras' => 14, 'peso' => 80.0, 'reps' => 6, 'rpe' => 8.5, 'nota' => 'Nueva marca'],
                ['diasAtras' => 10, 'peso' => 85.0, 'reps' => 5, 'rpe' => 9.0, 'nota' => 'Fuerza máxima'],
                ['diasAtras' => 6, 'peso' => 90.0, 'reps' => 3, 'rpe' => 9.5, 'nota' => 'Récord personal'],
                ['diasAtras' => 2, 'peso' => 95.0, 'reps' => 2, 'rpe' => 9.5, 'nota' => 'Superación personal'],
            ];

            foreach ($progresionBanca as $pb) {
                $f = $hoy->copy()->subDays($pb['diasAtras'])->setHour(18)->setMinute(0);
                PersonalRecord::create([
                    'cliente_id' => $mateo->id,
                    'ejercicio_id' => $ejPressBanca->id,
                    'peso_kg' => $pb['peso'],
                    'repeticiones' => $pb['reps'],
                    'notas' => $pb['nota'],
                    'created_at' => $f,
                    'updated_at' => $f,
                ]);
            }

            // Marcas de Mateo en otros ejercicios (4 registros)
            $f5 = $hoy->copy()->subDays(5)->setHour(18);
            PersonalRecord::create([
                'cliente_id' => $mateo->id,
                'ejercicio_id' => $ejInclinado->id,
                'peso_kg' => 32.0,
                'repeticiones' => 8,
                'notas' => 'Mancuernas de 32kg cada una',
                'created_at' => $f5,
                'updated_at' => $f5,
            ]);

            $f4 = $hoy->copy()->subDays(4)->setHour(18);
            PersonalRecord::create([
                'cliente_id' => $mateo->id,
                'ejercicio_id' => $ejAperturas->id,
                'peso_kg' => 65.0,
                'repeticiones' => 12,
                'notas' => 'Congestión óptima',
                'created_at' => $f4,
                'updated_at' => $f4,
            ]);

            $f3 = $hoy->copy()->subDays(3)->setHour(18);
            PersonalRecord::create([
                'cliente_id' => $mateo->id,
                'ejercicio_id' => $ejCruces->id,
                'peso_kg' => 25.0,
                'repeticiones' => 15,
                'notas' => 'Aislamiento pectoral',
                'created_at' => $f3,
                'updated_at' => $f3,
            ]);

            PersonalRecord::create([
                'cliente_id' => $mateo->id,
                'ejercicio_id' => $ejPressBanca->id,
                'peso_kg' => 100.0,
                'repeticiones' => 1,
                'notas' => '¡Marca centenaria lograda!',
                'created_at' => $hoy,
                'updated_at' => $hoy,
            ]);

            // Marcas para otros clientes (8 registros adicionales para poblar podios)
            $otrosPrs = [
                ['cli_idx' => 1, 'ej' => $ejPressBanca, 'peso' => 82.5, 'reps' => 5],
                ['cli_idx' => 2, 'ej' => $ejPressBanca, 'peso' => 92.5, 'reps' => 4],
                ['cli_idx' => 3, 'ej' => $ejInclinado, 'peso' => 28.0, 'reps' => 10],
                ['cli_idx' => 4, 'ej' => $ejAperturas, 'peso' => 70.0, 'reps' => 10],
                ['cli_idx' => 5, 'ej' => $ejCruces, 'peso' => 30.0, 'reps' => 12],
                ['cli_idx' => 6, 'ej' => $ejPressBanca, 'peso' => 75.0, 'reps' => 8],
                ['cli_idx' => 7, 'ej' => $ejInclinado, 'peso' => 30.0, 'reps' => 8],
                ['cli_idx' => 1, 'ej' => $ejAperturas, 'peso' => 60.0, 'reps' => 12],
            ];

            foreach ($otrosPrs as $idx => $op) {
                $fo = $hoy->copy()->subDays(($idx % 10) + 1)->setHour(17);
                PersonalRecord::create([
                    'cliente_id' => $clientes[$op['cli_idx']]->id,
                    'ejercicio_id' => $op['ej']->id,
                    'peso_kg' => $op['peso'],
                    'repeticiones' => $op['reps'],
                    'notas' => 'Entrenamiento supervisado',
                    'created_at' => $fo,
                    'updated_at' => $fo,
                ]);
            }
        }

        // 8. Rutina de entrenamiento para Mateo Morales asignada por Carlos Mendoza
        $rutina = Rutina::create([
            'user_id' => $mateo->id,
            'user_type' => 'cliente',
            'nombre' => 'Torso y Pecho Hipertrofia',
            'objetivo' => 'Hipertrofia',
            'nivel' => 'Intermedio',
            'dias_por_semana' => 4,
            'activa' => true,
            'asignado_por' => $coachCarlos->name,
            'asignado_por_id' => $coachCarlos->id,
        ]);

        $dia1 = RutinaDia::create([
            'rutina_id' => $rutina->id,
            'titulo' => 'Pecho y Tríceps',
            'orden' => 1,
            'duracion_estimada_min' => 45,
            'duracion_estimada_max' => 60,
        ]);

        $dia2 = RutinaDia::create([
            'rutina_id' => $rutina->id,
            'titulo' => 'Hombro y Abdomen',
            'orden' => 2,
            'duracion_estimada_min' => 40,
            'duracion_estimada_max' => 50,
        ]);

        if ($ejercicios->isNotEmpty()) {
            RutinaEjercicio::create([
                'rutina_dia_id' => $dia1->id,
                'ejercicio_id' => $ejercicios[0]->id,
                'series' => 4,
                'repeticiones' => '8-10',
                'descanso_segundos' => 90,
                'orden' => 1,
            ]);
            if ($ejercicios->count() > 1) {
                RutinaEjercicio::create([
                    'rutina_dia_id' => $dia1->id,
                    'ejercicio_id' => $ejercicios[1]->id,
                    'series' => 3,
                    'repeticiones' => '10-12',
                    'descanso_segundos' => 60,
                    'orden' => 2,
                ]);
            }
        }
    }
}
