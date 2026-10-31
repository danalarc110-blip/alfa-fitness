<?php

namespace App\Support;

use App\Models\ConfiguracionGimnasio;

final class InformacionGimnasio
{
    public static function predeterminada(): array
    {
        return [
            'nombre' => 'Alpha Fitness',
            'descripcion' => 'Centro Integral de Acondicionamiento Físico y Rendimiento',
            'capacidad' => 80,
            'horarios' => "Lunes a Viernes: 5:00 a.m. – 10:00 p.m.\nSábados: 6:00 a.m. – 6:00 p.m.\nDomingos y Feriados: 8:00 a.m. – 2:00 p.m.",
            'instalaciones' => "Zona de musculación y peso libre de alto impacto.\nÁrea de cardio y acondicionamiento aeróbico.\nEspacio de entrenamiento funcional y calistenia.\nCasilleros y vestidores climatizados.",
            'direccion' => null,
            'telefono' => null,
            'correo' => config('privacy.contact_email'),
            'atencion' => "Atención en recepción: Mostrador principal del gimnasio.\nGestión de membresías: Pagos y renovaciones presenciales.",
        ];
    }

    public static function obtener(): array
    {
        $guardada = ConfiguracionGimnasio::find(1)?->datos ?? [];

        return array_replace(self::predeterminada(), $guardada);
    }

    public static function guardar(array $datos): void
    {
        // A fixed primary key and atomic upsert keep concurrent first saves in one row.
        ConfiguracionGimnasio::upsert([
            ['id' => 1, 'datos' => json_encode($datos, JSON_THROW_ON_ERROR)],
        ], ['id'], ['datos', 'updated_at']);
    }
}
