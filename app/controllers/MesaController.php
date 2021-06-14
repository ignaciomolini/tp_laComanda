<?php
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

use \App\Models\Mesa as Mesa;

class MesaController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $mesa = new Mesa();
        $mesa->codigo = self::generarCodigoAleatorio();
        $mesa->estado = "libre";

        $mesa->save();
        $payload = json_encode(array("mensaje" => "Mesa creada con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $id = $args['id'];
        $mesa = Mesa::find($id);

        if (!$mesa) {
            $payload = json_encode(array("mensaje" => "La mesa no existe"));
        } else {
            $payload = json_encode($mesa);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Mesa::all();

        if (count($lista) == 0) {
            $payload = json_encode(array("mensaje" => "No hay mesas cargadas en el sistema"));
        } else {
            $payload = json_encode(array("lista mesas" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);

        $parametros = $request->getParsedBody();

        $id = $parametros['id'];
        $estado = $parametros['estado'];

        $mesa = Mesa::where('id', $id)->first();

        if (self::verificarEstado($estado)) {
            if ($mesa) {
                if ($dataUsuario->rol != 'mozo' || $estado != 'cerrada') {
                    $mesa->estado = $estado;
                    $mesa->save();
                    $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
                } else {
                    $payload = json_encode(array("error" => "El mozo no puede cerrar la mesa"));
                }
            } else {
                $payload = json_encode(array("error" => "No existe una mesa con ese id"));
            }
        } else {
            $payload = json_encode(array("error" => "Estado incorrecto"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'];

        $mesa = Mesa::find($id);

        if ($mesa) {
            $mesa->delete();
            $payload = json_encode(array("mensaje" => "Mesa eliminada con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe una mesa con ese id"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function generarCodigoAleatorio()
    {
        $carac = '0123456789abcdefghijklmnopqrstuvwxyz';
        $codigo = substr(str_shuffle($carac), 0, 5);
        return $codigo;
    }

    public static function verificarEstado($estado)
    {
        $rta = false;
        if (
            strcasecmp("libre", $estado) == 0 || strcasecmp("cerrada", $estado) == 0 || strcasecmp("con cliente esperando pedido", $estado) == 0 || strcasecmp("con cliente comiendo", $estado) == 0 || strcasecmp("con cliente pagando", $estado) == 0
        ) {
            $rta = true;
        }
        return $rta;
    }
}
