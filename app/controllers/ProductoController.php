<?php
require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';

class ProductoController extends Producto implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $tipo = $parametros['tipo'];
        $stock = $parametros['stock'];
        $precio = $parametros['precio'];

        $producto = new Producto();
        $producto->nombre = $nombre;
        $producto->tipo = $tipo;
        $producto->stock = (int) $stock;
        $producto->precio = (int) $precio;

        if (parent::verificarTipo($tipo)) {
            $producto->crearProducto();
            $payload = json_encode(array("mensaje" => "Producto agregado con exito"));
        } else {
            $payload = json_encode(array("error" => "Tipo incorrecto"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $idProducto = (int) $args['id'];
        $producto = parent::obtenerProducto($idProducto);

        if (!$producto) {
            $payload = json_encode(array("mensaje" => "El producto no existe"));
        } else {
            $payload = json_encode($producto);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = parent::obtenerTodos();

        if (empty($lista)) {
            $payload = json_encode(array("mensaje" => "No hay productos cargados en el sistema"));
        } else {
            $payload = json_encode(array("lista productos" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $id = $parametros['id'];
        $nombre = $parametros['nombre'];
        $tipo = $parametros['tipo'];
        $stock = $parametros['stock'];
        $precio = $parametros['precio'];

        $producto = new Producto();
        $producto->id = (int) $id;
        $producto->nombre = $nombre;
        $producto->tipo = $tipo;
        $producto->stock = (int) $stock;
        $producto->precio = (int) $precio;

        if (parent::verificarTipo($tipo)) {
            if (parent::verificarProductoPorId($id)) {
                $producto->ModificarProducto();
                $payload = json_encode(array("mensaje" => "Producto modificado con exito"));
            } else {
                $payload = json_encode(array("error" => "No existe un producto con ese id"));
            }
        } else {
            $payload = json_encode(array("error" => "Tipo incorrecto"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idProducto = (int) $parametros['idProducto'];

        if (parent::verificarProductoPorId($idProducto)) {
            parent::borrarProducto($idProducto);
            $payload = json_encode(array("mensaje" => "Producto eliminado con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe un producto con ese id"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
