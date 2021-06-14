<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TareaUsuario extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'tareas_usuarios';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
    ];
}