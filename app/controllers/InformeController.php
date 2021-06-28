<?php
require_once './models/Pedido.php';
require_once './models/SubPedido.php';
require_once './models/Producto.php';
require_once './models/Mesa.php';
require_once './models/Usuario.php';
require_once './models/TareaUsuario.php';
require_once './helpers/Archivos.php';
require_once './helpers/PedidoHtml.php';

use App\Models\Encuesta;
use \App\Models\Pedido as Pedido;
use \App\Models\Usuario as Usuario;
use \App\Models\TareaUsuario as TareaUsuario;
use \App\Models\Login as Login;
use \App\Models\Mesa as Mesa;
use \App\Models\Producto as Producto;
use \App\Models\SubPedido as SubPedido;

class InformeController
{
    public function DiasYHorasLogin($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $usuarios = Usuario::all();

        foreach ($usuarios as $user) {
            $log = Login::select('hora_inicio as hora', 'fecha')->where('mail', $user->mail)->whereBetween('fecha', [$fecha, date('Y-m-d')])->get();
            if (count($log) > 0) {
                $logs["$user->mail"] = $log;
            } else {
                $logs["$user->mail"] = "El usuario no se logeo en los ultimos $dias dias";
            }
        }
        $payload = array("Hora y fecha de logeo de usuarios en los ultimos $dias dias" => $logs);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CantidadDeOperacionesSector($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $sectores = ['mesas', 'cocina', 'candy bar', 'barra de tragos y vinos', 'barra de choperas'];

        foreach ($sectores as $sector) {
            $operaciones = TareaUsuario::where('sector', $sector)->whereBetween('fecha', [$fecha, date('Y-m-d')])->count();
            $cantidad["$sector"] = $operaciones;
        }
        $payload = array("Cantidad de operaciones en los ultimos $dias dias por sector" => $cantidad);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CantidadDeOperacionesSectorUser($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $sectores = ['mesas', 'cocina', 'candy bar', 'barra de tragos y vinos', 'barra de choperas'];

        foreach ($sectores as $sector) {
            $operaciones = TareaUsuario::where('sector', $sector)->whereBetween('fecha', [$fecha, date('Y-m-d')])->get();

            foreach ($operaciones as $key => $operacion) {
                $usuario = Usuario::find($operacion->id_usuario);
                $op[$key] = array('usuario' => $usuario->mail, 'tarea' => $operacion->tarea);
            }
            if (count($operaciones) > 0) {
                $listado["$sector"] = array('cantidad' => count($operaciones), 'listado' => $op);
            } else {
                $listado["$sector"] = array('cantidad' => count($operaciones));
            }

            $op = array();
        }
        $payload = array("Cantidad de operaciones en los ultimos $dias dias por sector" => $listado);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CantidadDeOperacionesUser($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $usuarios = Usuario::all();

        foreach ($usuarios as $user) {
            $tareas = TareaUsuario::select('tarea')->where('id_usuario', $user->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->count();

            $listado["$user->mail"] = $tareas;
        }
        $payload = array("Cantidad de operaciones de cada usuario en los ultimos $dias dias" => $listado);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ProductoMasVendido($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $productos = Producto::all();

        foreach ($productos as $key => $prod) {
            $cantidad = SubPedido::where('id_producto', $prod->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->sum('cantidad');
            $listaProductos[$key] = array('id' => $prod->id, 'cantidad' => (int)$cantidad);
        }
        $max = 0;
        foreach ($listaProductos as $prod) {
            if ($prod['cantidad'] > $max) {
                $max = $prod['cantidad'];
            }
        }
        foreach ($listaProductos as $prod) {
            if ($prod['cantidad'] >= $max) {
                $producto = Producto::where('id', $prod['id'])->first();
                $prods[] = $producto->nombre;
            }
        }
        $masVendidos['cantidad'] = $max;
        $masVendidos['productos'] = $prods;
        $payload = array("Los productos mas vendidos en los ultimos $dias dias" => $masVendidos);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ProductoMenosVendido($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $productos = Producto::all();

        foreach ($productos as $key => $prod) {
            $cantidad = SubPedido::where('id_producto', $prod->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->sum('cantidad');
            $listaProductos[$key] = array('id' => $prod->id, 'cantidad' => (int)$cantidad);
        }
        $min = PHP_INT_MAX;
        foreach ($listaProductos as $prod) {
            if ($prod['cantidad'] < $min) {
                $min = $prod['cantidad'];
            }
        }
        foreach ($listaProductos as $prod) {
            if ($prod['cantidad'] <= $min) {
                $producto = Producto::where('id', $prod['id'])->first();
                $prods[] = $producto->nombre;
            }
        }
        $menosVendidos['cantidad'] = $min;
        $menosVendidos['productos'] = $prods;
        $payload = array("Los productos menos vendidos en los ultimos $dias dias" =>  $menosVendidos);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function NoEntregadoEnTiempo($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $pedidos = Pedido::whereNotNull('hora_fin')->whereBetween('fecha', [$fecha, date('Y-m-d')])->get();

        foreach ($pedidos as $key => $ped) {
            $tiempoEstimado = $ped->tiempo_estimado;
            $horaInicio = new DateTime($ped->hora_inicio);
            $horaFin = new DateTime($ped->hora_fin);

            $diferencia = $horaInicio->diff($horaFin)->format('%H:%I:%S');
            if ($tiempoEstimado < $diferencia) {
                $fueraDeTiempo[] = $ped;
            }
        }

        if (empty($fueraDeTiempo)) {
            $payload = array('mensaje' => "No hay pedidos fuera de tiempo en los ultimos $dias dias");
        } else {
            $payload = array("Pedidos fuera de tiempo en los ultimos $dias dias" => $fueraDeTiempo);
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function Cancelados($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));

        $pedidos = Pedido::onlyTrashed()->whereBetween('fecha_de_baja', [$fecha, date('Y-m-d')])->get();

        if (count($pedidos) > 0) {
            $payload = array("Pedidos cancelados en los ultimos $dias dias" => $pedidos);
        } else {
            $payload = array('mensaje' => "No hay pedidos cancelados en los ultimos $dias dias");
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMasUsada($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $mesas = Mesa::all();

        foreach ($mesas as $key => $m) {
            $suma = Pedido::where('id_mesa', $m->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->count();
            $listaMesas[$key] = array('id' => $m->id, 'usos' => $suma);
        }
        $max = 0;
        foreach ($listaMesas as $m) {
            if ($m['usos'] > $max) {
                $max = $m['usos'];
            }
        }
        foreach ($listaMesas as $m) {
            if ($m['usos'] >= $max) {
                $mesa = $mesas->where('id', $m['id'])->first();
                $idMasUsadas[] = array('id' => $mesa->id, 'codigo' => $mesa->codigo);
            }
        }
        $masUsadas['usos'] = $max;
        $masUsadas['mesas'] = $idMasUsadas;
        $payload = array("Las mesas mas usadas en los ultimos $dias dias" => $masUsadas);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMenosUsada($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $mesas = Mesa::all();

        foreach ($mesas as $key => $m) {
            $suma = Pedido::where('id_mesa', $m->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->count();
            $listaMesas[$key] = array('id' => $m->id, 'usos' => $suma);
        }
        $min = PHP_INT_MAX;
        foreach ($listaMesas as $m) {
            if ($m['usos'] < $min) {
                $min = $m['usos'];
            }
        }
        foreach ($listaMesas as $m) {
            if ($m['usos'] <= $min) {
                $mesa = $mesas->where('id', $m['id'])->first();
                $idMenosUsadas[] = array('id' => $mesa->id, 'codigo' => $mesa->codigo);
            }
        }
        $menosUsadas['usos'] = $min;
        $menosUsadas['mesas'] = $idMenosUsadas;
        $payload = array("Las mesas menos usadas en los ultimos $dias dias" => $menosUsadas);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaQueMasFacturo($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $mesas = Mesa::all();

        foreach ($mesas as $key => $m) {
            $suma = Pedido::where('id_mesa', $m->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->sum('precio');
            $listaMesas[$key] = array('id' => $m->id, 'importe' => $suma);
        }
        $max = 0;
        foreach ($listaMesas as $m) {
            if ($m['importe'] > $max) {
                $max = $m['importe'];
            }
        }
        foreach ($listaMesas as $m) {
            if ($m['importe'] >= $max) {
                $mesa = $mesas->where('id', $m['id'])->first();
                $idMasUsadas[] = array('id' => $mesa->id, 'codigo' => $mesa->codigo);
            }
        }

        $masFac['importe'] = $max;
        if (count($idMasUsadas) > 1) {
            $masFac['mesas'] = $idMasUsadas;
            $payload = array("Las mesas que mas facturaron en los ultimos $dias dias" => $masFac);
        } else {
            $masFac['mesa'] = $idMasUsadas;
            $payload = array("La mesa que mas facturo en los ultimos $dias dias" => $masFac);
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaQueMenosFacturo($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $mesas = Mesa::all();

        foreach ($mesas as $key => $m) {
            $suma = Pedido::where('id_mesa', $m->id)->whereBetween('fecha', [$fecha, date('Y-m-d')])->sum('precio');
            $listaMesas[$key] = array('id' => $m->id, 'importe' => $suma);
        }
        $min = PHP_INT_MAX;
        foreach ($listaMesas as $m) {
            if ($m['importe'] < $min) {
                $min = $m['importe'];
            }
        }
        foreach ($listaMesas as $m) {
            if ($m['importe'] <= $min) {
                $mesa = $mesas->where('id', $m['id'])->first();
                $idMenosUsadas[] = array('id' => $mesa->id, 'codigo' => $mesa->codigo);
            }
        }

        $menosFac['importe'] = $min;
        if (count($idMenosUsadas) > 1) {
            $menosFac['mesas'] = $idMenosUsadas;
            $payload = array("Las mesas que menos facturaron en los ultimos $dias dias" => $menosFac);
        } else {
            $menosFac['mesa'] = $idMenosUsadas;
            $payload = array("La mesa que menos facturo en los ultimos $dias dias" => $menosFac);
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMayorImporte($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $pedidos = Pedido::where('precio', Pedido::whereBetween('fecha', [$fecha, date('Y-m-d')])->max('precio'))->get();

        if (count($pedidos) == 0) {
            $payload = array('mensaje' => "No se uso ninguna mesa en los ultimos $dias dias");
        } else {
            foreach ($pedidos as $ped) {
                $mesa = Mesa::find($ped->id_mesa);
                $mesas[] = array('mesa' => $mesa->id, 'codigo' => $mesa->codigo, 'importe' => $ped->precio);
            }
            if (count($pedidos) == 1) {
                $payload = array("La mesa con mayor importe en los ultimos $dias dias" => $mesas);
            } else {
                $payload = array("Las mesas con mayor importe en los ultimos $dias dias" => $mesas);
            }
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MesaMenorImporte($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));
        $pedidos = Pedido::where('precio', Pedido::whereBetween('fecha', [$fecha, date('Y-m-d')])->min('precio'))->get();

        if (count($pedidos) == 0) {
            $payload = array('mensaje' => "No se uso ninguna mesa en los ultimos $dias dias");
        } else {
            foreach ($pedidos as $ped) {
                $mesa = Mesa::find($ped->id_mesa);
                $mesas[] = array('mesa' => $mesa->id, 'codigo' => $mesa->codigo, 'importe' => $ped->precio);
            }
            if (count($pedidos) == 1) {
                $payload = array("La mesa con menor importe en los ultimos $dias dias" => $mesas);
            } else {
                $payload = array("Las mesas con menor importe en los ultimos $dias dias" => $mesas);
            }
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function FacturacionEntreFechas($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $idMesa = $params['idMesa'];
        $fechaInicio = $params['fechaInicio'];
        $fechaFin = $params['fechaFin'];

        if (Mesa::find($idMesa) && self::validarFecha($fechaInicio) && self::validarFecha($fechaFin)) {
            $facturacion = Pedido::where('id_mesa', $idMesa)->whereBetween('fecha', [$fechaInicio, $fechaFin])->sum('precio');
            $payload = array("Facturacion mesa $idMesa entre $fechaInicio y $fechaFin" => (int)$facturacion);
        } else {
            $payload = array('error' => 'No existe mesa con ese id o ingreso mal la fecha');
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function MejoresComentarios($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));

        $encuestas = Encuesta::whereBetween('fecha', [$fecha, date('Y-m-d')])->get();
        foreach ($encuestas as $enc) {
            if ($enc->mesa >= 6) {
                $comentarios[] = array('mesa' => $enc->id_mesa, 'comentario' => $enc->experiencia);
            }
        }
        if (isset($comentarios)) {
            $payload = array("Mejores comentarios en los ultimos $dias dias" => $comentarios);
        } else {
            $payload = array('mensaje' => "No hay buenos comentarios en los ultimos $dias dias");
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function PeoresComentarios($request, $response, $args)
    {
        $dias = $args['dias'];
        $fecha = self::restarDias($dias, date('Y-m-d'));

        $encuestas = Encuesta::whereBetween('fecha', [$fecha, date('Y-m-d')])->get();
        foreach ($encuestas as $enc) {
            if ($enc->mesa < 6) {
                $comentarios[] = array('mesa' => $enc->id_mesa, 'comentario' => $enc->experiencia);
            }
        }
        if (isset($comentarios)) {
            $payload = array("Peores comentarios en los ultimos $dias dias" => $comentarios);
        } else {
            $payload = array('mensaje' => "No hay malos comentarios en los ultimos $dias dias");
        }
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function validarFecha($fecha)
    {
        $arrayFecha = explode('-', $fecha);
        $rta = false;

        if (count($arrayFecha) == 3) {
            if (ctype_digit($arrayFecha[0]) && ctype_digit($arrayFecha[1]) && ctype_digit($arrayFecha[2])) {
                $rta = true;
            }
        }
        return $rta;
    }

    public static function restarDias($dias, $fecha)
    {
        $nuevaFecha = strtotime($fecha . "- $dias days");
        return date('Y-m-d', $nuevaFecha);
    }
}
