<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoMembresia extends Model
{
    protected $table = 'pagos_membresia';
    protected $fillable = ['membresia_id', 'solicitud_id', 'registrado_por', 'importe', 'pagado_en', 'referencia'];
    protected function casts(): array { return ['importe' => 'decimal:2', 'pagado_en' => 'datetime']; }
    public function membresia(): BelongsTo { return $this->belongsTo(Membresia::class); }
    public function solicitud(): BelongsTo { return $this->belongsTo(SolicitudMembresia::class); }
    public function registrador(): BelongsTo { return $this->belongsTo(User::class, 'registrado_por'); }
}
