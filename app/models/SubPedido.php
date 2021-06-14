<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubPedido extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'id';
    protected $table = 'sub_pedidos';
    public $incrementing = true;
    public $timestamps = false;

    const DELETED_AT = 'fecha_de_baja';

    protected $fillable = [
        'id_usuario', 'estado'
    ];
}