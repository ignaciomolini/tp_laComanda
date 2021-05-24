<?php
require_once './models/Mesa.php';
require_once './interfaces/IApiUsable.php';

class MesaController extends Mesa implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $mesa = new Mesa();
        $mesa->codigo = parent::generarCodigoAleatorio();
        $mesa->estado = "libre";

        $mesa->crearMesa();
        $payload = json_encode(array("mensaje" => "Mesa creada con exito"));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $idMesa = $args['id'];
        $mesa = parent::obtenerMesa($idMesa);

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
        $lista = parent::obtenerTodos();

        if (empty($lista)) {
            $payload = json_encode(array("mensaje" => "No hay mesas cargadas en el sistema"));
        } else {
            $payload = json_encode(array("lista mesas" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $id = $parametros['id'];
        $estado = $parametros['estado'];

        $mesa = new Mesa();
        $mesa->id = (int) $id;
        $mesa->estado = $estado;

        if (parent::verificarEstado($estado)) {
            if (parent::verificarMesaPorId($id)) {
                $mesa->modificarMesa();
                $payload = json_encode(array("mensaje" => "Mesa modificada con exito"));
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
        $idMesa = $parametros['idMesa'];

        if (parent::verificarMesaPorId($idMesa)) {
            parent::borrarMesa($idMesa);
            $payload = json_encode(array("mensaje" => "Mesa eliminada con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe una mesa con ese id"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
