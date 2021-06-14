<?php

require_once './models/TareaUsuario.php';

use \App\Models\TareaUsuario as TareaUsuario;

class TareaUsuarioHelper
{
    public static function CargarDatos($codigoPedido, $idUsuario, $sector, $tarea)
    {
        $tareaUser = new TareaUsuario();
        $tareaUser->codigo_pedido = $codigoPedido;
        $tareaUser->id_usuario = $idUsuario;
        $tareaUser->sector = $sector;
        $tareaUser->tarea = $tarea;
        $tareaUser->fecha = date('Y-m-d');
        $tareaUser->save();
    }
}
