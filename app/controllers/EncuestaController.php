<?php
require_once './models/Pedido.php';
require_once './models/Encuesta.php';

use \App\Models\Pedido as Pedido;
use \App\Models\Encuesta as Encuesta;

class EncuestaController
{
    public function CrearEncuesta($request, $response, $args)
    {
        try {
            $parametros = $request->getParsedBody();
            $codigoPedido = $parametros['codigoPedido'];
            $mesa = $parametros['mesa'];
            $restaurante = $parametros['restaurante'];
            $mozo = $parametros['mozo'];
            $cocinero = $parametros['cocinero'];
            $experiencia = $parametros['experiencia'];

            $pedido = Pedido::where('codigo', $codigoPedido)->first();

            if ($pedido && $pedido->estado == 'cobrado') {
                if (!Encuesta::where('codigo_pedido', $codigoPedido)->first()) {
                    $encuesta = new Encuesta();
                    $encuesta->codigo_pedido = $codigoPedido;
                    $encuesta->id_mesa = $pedido->id_mesa;
                    if (self::ValidarPuntuacion($mesa) && self::ValidarPuntuacion($restaurante) && self::ValidarPuntuacion($mozo) && self::ValidarPuntuacion($mozo)) {
                        $encuesta->mesa = $mesa;
                        $encuesta->restaurante = $restaurante;
                        $encuesta->mozo = $mozo;
                        $encuesta->cocinero = $cocinero;
                    } else {
                        throw new Exception('La puntuacion debe ser del 1 a 10');
                    }
                    if (self::ValidarExperiencia($experiencia)) {
                        $encuesta->experiencia = $experiencia;
                    } else {
                        throw new Exception('La descripcion de la experiencia no debe superar los 66 caracteres');
                    }
                    $encuesta->fecha = date('Y-m-d');
                    $encuesta->save();
                    $payload = json_encode(array('mensaje' => 'Se guardo la encuesta correctamente'));
                } else {
                    throw new Exception('Ya se realizo una encuesta para ese pedido');
                }
            } else {
                throw new Exception('El pedido no existe o no fue cobrado aun');
            }
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $payload = json_encode(array('error' => $e->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public static function ValidarPuntuacion($punt)
    {
        $rta = false;
        if(ctype_digit($punt) && $punt <= 10){
            $rta = true;
        }
        return $rta;
    }

    public static function ValidarExperiencia($exp)
    {
        $rta = false;
        if (strlen($exp) <= 66) {
            $rta = true;
        }
        return $rta;
    }
}
