<?php

use App\Support\ImagenesCatalogo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (ImagenesCatalogo::PRODUCTOS as $nombre => $imagen) {
            if (! is_file(public_path('images/productos/'.$imagen))) {
                continue;
            }
            foreach (DB::table('productos')->where('nombre', $nombre)->get(['id', 'imagen']) as $producto) {
                $actual = $producto->imagen;
                $esLocal = $actual && basename($actual) === $actual;
                $esValida = $esLocal && (is_file(public_path('images/productos/'.$actual))
                    || is_file(public_path('images/productos/'.pathinfo($actual, PATHINFO_FILENAME).'.webp')));
                if (! $esValida) {
                    DB::table('productos')->where('id', $producto->id)->update(['imagen' => $imagen]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (ImagenesCatalogo::PRODUCTOS as $nombre => $imagen) {
            DB::table('productos')->where('nombre', $nombre)->where('imagen', $imagen)->update(['imagen' => null]);
        }
    }
};
