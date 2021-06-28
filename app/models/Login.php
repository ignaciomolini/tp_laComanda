<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'logins';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
    ];
}