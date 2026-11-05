<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoEntrada extends Model
{
    protected $table = 'pagos_entrada';

    protected $fillable = ['asistencia_id', 'registrado_por', 'importe', 'pagado_en'];

    protected function casts(): array
    {
        return ['importe' => 'decimal:2', 'pagado_en' => 'datetime'];
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }

    public function registrador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
