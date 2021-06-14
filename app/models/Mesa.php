<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesa extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'mesas';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'estado'
    ];   
}
    