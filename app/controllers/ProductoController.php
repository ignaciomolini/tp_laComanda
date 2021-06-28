<?php
require_once './models/Producto.php';
require_once './interfaces/IApiUsable.php';

use \App\Models\Producto as Producto;

class ProductoController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $sector = $parametros['sector'];
        $stock = $parametros['stock'];
        $precio = $parametros['precio'];

        if (self::verificarNombre($nombre)) {
            if (self::verificarSector($sector)) {
                $producto = new Producto();
                $producto->nombre = $nombre;
                $producto->sector = $sector;
                $producto->stock = (int) $stock;
                $producto->precio = (int) $precio;
                $producto->save();
                $payload = json_encode(array('mensaje' => 'Producto agregado con exito'));
            } else {
                $payload = json_encode(array('error' => 'Sector incorrecto'));
            }
        }else{
            $payload = json_encode(array('error' => 'Ya existe un producto con ese nombre'));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CargarUnoCsv($request, $response, $args)
    {
        $archivos = $request->getUploadedFiles();
        $ruta = Archivos::MoverArchivo(rand(10000, 99999), $archivos['producto'], './assets/cargaProductosCsv/');
        $payload = array();

        foreach (Archivos::LeerArchivo($ruta) as $producto) {
            $nombre = $producto[0];
            $sector = $producto[1];
            $stock = $producto[2];
            $precio = $producto[3];

            if (self::verificarNombre($nombre)) {
                if (self::verificarSector($sector)) {
                    $producto = new Producto();
                    $producto->nombre = $nombre;
                    $producto->sector = $sector;
                    $producto->stock = $stock;
                    $producto->precio = $precio;
                    $producto->save();
                    array_push($payload, array('mensaje' => "El siguiente producto fue agregado con exito: $nombre"));
                } else {
                    array_push($payload, array('error' => "Sector incorrecto. No se pudo cargar el siguiente producto: $nombre"));
                }
            } else {
                array_push($payload, array('error' => "Ya existe un producto con ese nombre. No se pudo cargar el siguiente producto: $nombre"));
            }
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $id = (int) $args['id'];
        $producto = Producto::where('id', $id)->first();

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
        $lista = Producto::all();

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
        $sector = $parametros['sector'];
        $stock = $parametros['stock'];
        $precio = $parametros['precio'];

        $producto = Producto::where('id', $id)->first();

        if (self::verificarSector($sector)) {
            if ($producto) {
                $producto->nombre = $nombre;
                $producto->sector = $sector;
                $producto->stock = (int) $stock;
                $producto->precio = (int) $precio;
                $producto->save();
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
        $id = (int) $parametros['id'];

        $producto = Producto::find($id);

        if ($producto) {
            $producto->delete();
            $payload = json_encode(array("mensaje" => "Producto eliminado con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe un producto con ese id"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarSector($sector)
    {
        $rta = false;
        if (strcasecmp("cocina", $sector) == 0 || strcasecmp("barra de tragos y vinos", $sector) == 0 || strcasecmp("barra de choperas", $sector) == 0 || strcasecmp("candy bar", $sector) == 0) {
            $rta = true;
        }
        return $rta;
    }

    public static function verificarNombre($nombre)
    {
        $rta = false;
        $producto = Producto::where('nombre', $nombre)->get();
        if (count($producto) == 0) {
            $rta = true;
        };
        return $rta;
    }
}
