<?php
require_once './models/Pedido.php';
require_once './models/SubPedido.php';
require_once './models/Producto.php';
require_once './models/Mesa.php';
require_once './models/Usuario.php';
require_once './models/TareaUsuario.php';

use \App\Models\Pedido as Pedido;
use \App\Models\Usuario as Usuario;
use \App\Models\SubPedido as SubPedido;

class SubPedidoController 
{
    public function TraerTodos($request, $response, $args)
    {
        $lista = SubPedido::all();

        if (count($lista) == 0) {
            $payload = json_encode(array("mensaje" => "No hay subpedidos cargados en el sistema"));
        } else {
            $payload = json_encode(array("lista subpedidos" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerPorCodigo($request, $response, $args)
    { 
        $codigo = $args['codigo'];
        $lista = SubPedido::where('codigo_pedido', $codigo)->get();

        if (count($lista) != 0) {
            $payload = json_encode(array("lista pedidos" => $lista));        
        } else {
            $payload = json_encode(array("mensaje" => "No existe un pedido con ese codigo"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerPendientes($request, $response, $args)
    {
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);

        $lista = self::devolverSubPedidosPorRolYEstado($dataUsuario->rol, 'pendiente');

        if (count($lista) == 0) {
            $payload = json_encode(array("mensaje" => "No hay pedidos pendientes"));
        } else {
            $payload = json_encode(array("pedidos pendientes" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function devolverSubPedidosPorRolYEstado($rol, $estado)
    {
        switch ($rol) {
            case 'socio':
                $lista = SubPedido::where('estado', $estado)->get();
                break;
            case 'cocinero':
                $lista = SubPedido::where('estado', $estado)->where(function($query) {
                    $query->where('sector', 'cocina')
                          ->orWhere('sector', 'candy bar');
                })->get();
                break;
            case 'bartender':
                $lista = SubPedido::where('estado', $estado)->where('sector', 'barra de tragos y vinos')->get();
                break;
            case 'cervecero':
                $lista = SubPedido::where('estado', $estado)->where('sector', 'barra de choperas')->get();
                break;
        }
        return $lista;
    }

    public function TraerTodosACargo($request, $response, $args){
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);
        $usuario = Usuario::where('mail', $dataUsuario->mail)->first();

        $lista = SubPedido::where('id_usuario', $usuario->id)->where('estado', 'en preparacion')->get();

        if(count($lista) == 0){
            $payload = array('mensaje' => 'No tiene subpedidos a cargo');
        }else{
            $payload = array('subpedidos a cargo' => $lista);
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TomarPendiente($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idSubPedido = $parametros['idSubPedido'];
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);
        $subPedido = null;

        $usuario = Usuario::where('mail', $dataUsuario->mail)->first();
        $lista = self::devolverSubPedidosPorRolYEstado($dataUsuario->rol, 'pendiente');

        foreach ($lista as $subPed) {
            if ($subPed->id == $idSubPedido) {
                $subPedido = $subPed;
                break;
            }
        }
        if ($subPedido) {
            $subPedido->id_usuario = $usuario->id;
            $subPedido->estado = 'en preparacion';
            $subPedido->save();
            $pedido = Pedido::where('codigo', $subPedido->codigo_pedido)->first();
            $pedido->estado = 'en preparacion';
            $pedido->save();
            TareaUsuarioHelper::CargarDatos($subPedido->codigo_pedido, $subPedido->id_usuario, $subPedido->sector, 'tomar subpedido pendiente');
            $payload = json_encode(array("mensaje" => "El subpedido esta en preparacion"));
        } else {
            $payload = json_encode(array("error" => "No puede tomar ese subpedido"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function FinalizarSubPedido($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idSubPedido = $parametros['idSubPedido'];
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);
        $usuario = Usuario::where('mail', $dataUsuario->mail)->first();

        if ($usuario->rol != 'socio') {
            $subPedido = SubPedido::where('id', $idSubPedido)->where('id_usuario', $usuario->id)->where('estado', 'en preparacion')->first();
        } else {
            $subPedido = SubPedido::where('id', $idSubPedido)->where('estado', 'en preparacion')->first();
        }

        if ($subPedido && $subPedido->estado == 'en preparacion') {
            $subPedido->id_usuario = $usuario->id;
            $subPedido->estado = 'listo para servir';
            $subPedido->save();
            $payload = array("mensaje" => "El subpedido esta listo para servir");
            TareaUsuarioHelper::CargarDatos($subPedido->codigo_pedido, $subPedido->id_usuario, $subPedido->sector, 'terminar subpedido');
            if (count(SubPedido::where('codigo_pedido', $subPedido->codigo_pedido)->get()) == count(SubPedido::where('codigo_pedido', $subPedido->codigo_pedido)->where('estado', 'listo para servir')->get())) {
                $pedido = Pedido::where('codigo', $subPedido->codigo_pedido)->first();
                $pedido->estado = 'listo para servir';
                $pedido->save();       
                $payload = array("mensaje" => "El pedido completo esta listo para servir");
            }
        }
        else{
            $payload = array("error" => "El subpedido no existe, no esta en preparacion o no tiene los permisos para terminarlo");
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
