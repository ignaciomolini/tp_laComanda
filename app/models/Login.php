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

// <?php

// class Login
// {
//     public $id;
//     public $mail;
//     public $hora_inicio;
//     public $hora_fin;

//     public function logearUsuario()
//     {
//         $objAccesoDatos = AccesoDatos::obtenerInstancia();
//         $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO logins (mail, hora_inicio) VALUES (:mail, :hora_inicio)");
//         $consulta->bindValue(':mail', $this->mail, PDO::PARAM_STR);
//         $consulta->bindValue(':hora_inicio', $this->hora_inicio, PDO::PARAM_STR);
//         $consulta->execute();

//         return $objAccesoDatos->obtenerUltimoId();
//     }
// }
