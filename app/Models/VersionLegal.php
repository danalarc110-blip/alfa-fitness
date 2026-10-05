<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VersionLegal extends Model
{
    protected $table = 'versiones_legales';

    protected $fillable = ['version', 'hash_contenido', 'documentos'];

    protected function casts(): array
    {
        return ['documentos' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Una versión legal archivada no se puede modificar. Publica una versión nueva.');
        });
        static::deleting(function () {
            throw new \LogicException('Una versión legal archivada no se puede eliminar desde la aplicación.');
        });
    }
}
