<?php
require_once './models/Pedido.php';
require_once './models/Producto.php';
require_once './models/Mesa.php';
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';

class PedidoController extends Pedido implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombreCliente = $parametros['nombreCliente'];
        $idUsuario = $parametros['idUsuario'];
        $idMesa = $parametros['idMesa'];
        $idProducto = $parametros['idProducto'];
        $cantidad = $parametros['cantidad'];

        $pedido = new Pedido();
        $pedido->codigo = parent::generarCodigoAleatorio();
        $pedido->nombre_cliente = $nombreCliente;
        $pedido->id_usuario = $idUsuario;
        $pedido->id_mesa = $idMesa;
        $pedido->id_producto = $idProducto;
        $pedido->cantidad = $cantidad;
        $pedido->estado = "pendiente";
        $pedido->hora_inicio = date('H:i:s');

        if (strcasecmp(Usuario::obtenerRol($idUsuario), "mozo") == 0 && strcasecmp(Usuario::obtenerEstado($idUsuario), "activo") == 0) {
            if (strcasecmp(Mesa::obtenerEstado($idMesa), "libre") == 0) {
                if (Producto::verificarStock($idProducto, $cantidad)) {
                    $producto = Producto::obtenerProducto($idProducto);
                    $pedido->precio = Pedido::calcularPrecioTotal($producto->precio, $cantidad);
                    $pedido->crearPedido();
                    $producto->modificarStockProducto($cantidad);
                    $mesa = Mesa::obtenerMesa($idMesa);
                    $mesa->modificarEstadoMesa("con cliente esperando pedido");
                    $payload = json_encode(array("mensaje" => "Pedido creado con exito", "codigo de pedido" => $pedido->codigo, "codigo de mesa" => $mesa->codigo));
                } else {
                    $payload = json_encode(array("error" => "El producto no existe o hay stock insuficiente"));
                }
            } else {
                $payload = json_encode(array("error" => "La mesa ingresada no existe o no esta libre"));
            }
        } else {
            $payload = json_encode(array("error" => "El usuario debe ser mozo y estar activo. Solo el mozo puede tomar el pedido"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $idPedido = $args['id'];
        $pedido = parent::obtenerPedido($idPedido);

        if (!$pedido) {
            $payload = json_encode(array("mensaje" => "El pedido no existe"));
        } else {
            $payload = json_encode($pedido);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = parent::obtenerTodos();

        if (empty($lista)) {
            $payload = json_encode(array("mensaje" => "No hay pedidos cargados en el sistema"));
        } else {
            $payload = json_encode(array("lista pedidos" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $id = $parametros['id'];
        $idUsuario = $parametros['idUsuario'];
        $estado = $parametros['estado'];
        $tiempoEstimado = $parametros['tiempoEstimado'];

        $pedido = new Pedido();
        $pedido->id = (int) $id;
        $pedido->id_usuario = $idUsuario;
        $pedido->estado = $estado;
        $pedido->tiempo_estimado = $tiempoEstimado;

        if (parent::verificarPedidoPorId($id)) {
            if (Usuario::verificarUsuarioPorId($idUsuario)) {
                if (parent::verificarEstado($estado)) {
                    if (Pedido::verificarTiempoEstimado($tiempoEstimado)) {
                        if (strcasecmp($estado, "listo para servir") == 0) {
                            $pedido->hora_fin = date('H:i:s');
                        }
                        $pedido->modificarPedido();
                        $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));
                    } else {
                        $payload = json_encode(array("error" => "El tiempo de espera estimado es incorrecto"));
                    }
                } else {
                    $payload = json_encode(array("error" => "El estado del pedido es incorrecto"));
                }
            }else{
                $payload = json_encode(array("error" => "El usuario ingresado no existe"));
            }
        } else {
            $payload = json_encode(array("error" => "El pedido ingresado no existe"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idPedido = $parametros['idPedido'];

        if (parent::verificarPedidoPorId($idPedido)) {
            //parent::borrarPedido($idPedido);
            $payload = json_encode(array("mensaje" => "Pedido eliminado con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe un pedido con ese id"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
