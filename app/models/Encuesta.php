<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encuesta extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'encuestas';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
    ];
}
