<?php
require_once './models/Pedido.php';
require_once './models/SubPedido.php';
require_once './models/Producto.php';
require_once './models/Mesa.php';
require_once './models/Usuario.php';
require_once './models/TareaUsuario.php';
require_once './helpers/Archivos.php';
require_once './helpers/PedidoHtml.php';
require_once './helpers/TareaUsuarioHelper.php';
require_once './interfaces/IApiUsable.php';

use \App\Models\Pedido as Pedido;
use \App\Models\Usuario as Usuario;
use \App\Models\Mesa as Mesa;
use \App\Models\Producto as Producto;
use \App\Models\SubPedido as SubPedido;
use Dompdf\Dompdf;

class PedidoController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        try {
            $parametros = $request->getParsedBody();
            $archivos = $request->getUploadedFiles();
            $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
            $dataUsuario = AutentificadorJWT::ObtenerData($token);

            $nombreCliente = $parametros['nombreCliente'];
            $idMesa = $parametros['idMesa'];
            $idProductos = $parametros['idProductos'];
            $cantidades = $parametros['cantidades'];
            $tiempoEstimado = $parametros['tiempoEstimado'];

            $user = Usuario::where('mail', $dataUsuario->mail)->first();

            $pedido = new Pedido();
            $pedido->codigo = self::generarCodigoAleatorio();
            $pedido->nombre_cliente = $nombreCliente;
            $pedido->id_usuario = $user->id;
            $pedido->id_mesa = $idMesa;
            $pedido->id_productos = $idProductos;
            $pedido->cantidades = $cantidades;
            $pedido->estado = "pendiente";
            $pedido->tiempo_estimado = $tiempoEstimado;
            $pedido->hora_inicio = date('H:i:s');
            $pedido->fecha = date('Y-m-d');
            $ruta = Archivos::MoverArchivo('pedido-'.$pedido->codigo, $archivos['foto'], './assets/fotoPedidos/');
            $pedido->foto = $ruta;

            $mesa = Mesa::find($idMesa);

            if ($mesa && strcasecmp($mesa->estado, "libre") == 0) {

                $arrayProductos = explode('-', $idProductos);
                $arrayCantidades = explode('-', $cantidades);

                if (!self::verificarTiempoEstimado($tiempoEstimado)) {
                    throw new Exception("El tiempo estimado no es correcto");
                }

                if (count($arrayProductos) == count($arrayCantidades)) {
                    foreach ($arrayProductos as $key => $idProducto) {
                        $producto = Producto::find($idProducto);
                        if ($producto && $producto->stock >= $arrayCantidades[$key]) {
                            $pedido->precio += $producto->precio * $arrayCantidades[$key];
                            $nuevoStock = $producto->stock - $arrayCantidades[$key];
                            $producto->stock = $nuevoStock;
                            $producto->save();
                            $subPedido = new SubPedido();
                            $subPedido->codigo_pedido = $pedido->codigo;
                            $subPedido->cantidad = $arrayCantidades[$key];
                            $subPedido->id_producto = $idProducto;
                            $subPedido->sector = $producto->sector;
                            $subPedido->estado = 'pendiente';
                            $subPedido->fecha = date('Y-m-d');
                            $subPedido->save();
                        } else {
                            throw new Exception("El producto de id $idProducto no existe o hay stock insuficiente");
                        }
                    }
                } else {
                    throw new Exception("No coinciden los productos ingresados con las cantidades");
                }
                $mesa->estado = "con cliente esperando pedido";
                $mesa->save();
                TareaUsuarioHelper::CargarDatos($pedido->codigo, $user->id, 'mesas', 'tomar pedido');
                $pedido->save();
                $payload = json_encode(array("mensaje" => "Pedido creado con exito", "codigo de pedido" => $pedido->codigo, "codigo de mesa" => $mesa->codigo));
            } else {
                throw new Exception("La mesa ingresada no existe o no esta libre");
            }
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $payload = json_encode(array('error' => $e->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function TraerUno($request, $response, $args)
    {
        $id = $args['id'];
        $pedido = Pedido::find($id);

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
        $lista = Pedido::all();

        if (count($lista) == 0) {
            $payload = json_encode(array("mensaje" => "No hay pedidos cargados en el sistema"));
        } else {
            $payload = json_encode(array("lista pedidos" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        try {
            $parametros = $request->getParsedBody();
            $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
            $dataUsuario = AutentificadorJWT::ObtenerData($token);

            $idPedido = $parametros['idPedido'];
            $nombreCliente = $parametros['nombreCliente'];
            $idMesa = $parametros['idMesa'];
            $idProductos = $parametros['idProductos'];
            $cantidades = $parametros['cantidades'];
            $tiempoEstimado = $parametros['tiempoEstimado'];

            $user = Usuario::where('mail', $dataUsuario->mail)->first();
            $pedido = Pedido::find($idPedido);

            if ($pedido) {
                $pedido->nombre_cliente = $nombreCliente;
                $pedido->id_usuario = $user->id;
                $pedido->cantidades = $cantidades;
                $pedido->precio = 0;
                $pedido->tiempo_estimado = $tiempoEstimado;
                $pedido->estado = 'pendiente';

                $mesaVieja = Mesa::find($pedido->id_mesa);
                $mesaVieja->estado = 'libre';
                $mesaVieja->save();

                if (!self::verificarTiempoEstimado($tiempoEstimado)) {
                    throw new Exception("El tiempo estimado no es correcto");
                }

                $mesa = Mesa::find($idMesa);

                if ($mesa && (strcasecmp($mesa->estado, "libre") == 0 || $pedido->id_mesa == $idMesa)) {
                    $arrayProductos = explode('-', $idProductos);
                    $arrayCantidades = explode('-', $cantidades);
                    $idProductosViejos = explode('-', $pedido->id_productos);
                    $cantidadesViejas = explode('-', $pedido->cantidades);

                    if (count($arrayProductos) == count($arrayCantidades)) {
                        foreach ($arrayProductos as $key => $idProducto) {
                            $producto = Producto::find($idProducto);
                            if ($producto && $producto->stock >= $arrayCantidades[$key]) {
                                $pedido->precio += $producto->precio * $arrayCantidades[$key];
                                $nuevoStock = $producto->stock - $arrayCantidades[$key] + $cantidadesViejas[$key];
                                $producto->stock = $nuevoStock;
                                $producto->save();
                                $subPedido = SubPedido::where('codigo_pedido', $pedido->codigo)->where('id_producto', $idProductosViejos[$key])->first();
                                $subPedido->id_producto = $idProducto;
                                $subPedido->cantidad = $arrayCantidades[$key];
                                $subPedido->sector = $producto->sector;
                                $subPedido->estado = 'pendiente';
                                $subPedido->save();
                            } else {
                                throw new Exception("El producto de id $idProducto no existe o hay stock insuficiente");
                            }
                        }
                    } else {
                        throw new Exception("No coinciden los productos ingresados con las cantidades");
                    }
                    $pedido->id_productos = $idProductos;
                    $mesa->estado = 'con cliente esperando pedido';
                    $mesa->save();
                    $pedido->id_mesa = $idMesa;
                    $pedido->save();
                    $payload = json_encode(array("mensaje" => "Pedido modificado con exito"));
                    $response->getBody()->write($payload);
                    return $response->withHeader('Content-Type', 'application/json');
                } else {
                    throw new Exception("La mesa no existe o esta ocupada");
                }
            } else {
                throw new Exception("El pedido no existe");
            }
        } catch (Exception $e) {
            $payload = json_encode(array('error' => $e->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'];

        $pedido = Pedido::find($id);

        if ($pedido && $pedido->estado != 'suspendido') {
            $pedido->estado = 'suspendido';
            $pedido->save();
            $pedido->delete();
            $payload = json_encode(array("mensaje" => "Pedido eliminado con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe un pedido con ese id o ya fue eliminado"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ServirPedido($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idPedido = $parametros['idPedido'];
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);
        $usuario = Usuario::where('mail', $dataUsuario->mail)->first();
        $pedido = Pedido::find($idPedido);

        if ($pedido && $pedido->estado == "listo para servir") {
            $pedido->id_usuario = $usuario->id;
            $pedido->estado = 'servido';
            $pedido->hora_fin = date('H:i:s');
            $pedido->save();
            $mesa = Mesa::find($pedido->id_mesa);
            $mesa->estado = 'con cliente comiendo';
            $mesa->save();
            TareaUsuarioHelper::CargarDatos($pedido->codigo, $usuario->id, 'mesas', 'servir pedido');
            $payload = array('mensaje' => 'El pedido fue servido');
        } else {
            $payload = array('error' => 'El pedido no existe o no esta listo para servirse');
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function EntregarCuenta($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idPedido = $parametros['idPedido'];
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);
        $usuario = Usuario::where('mail', $dataUsuario->mail)->first();
        $pedido = Pedido::find($idPedido);

        if ($pedido && $pedido->estado == "servido") {
            $pedido->id_usuario = $usuario->id;
            $pedido->estado = 'cobrandolo';
            $pedido->save();
            $mesa = Mesa::find($pedido->id_mesa);
            $mesa->estado = 'con cliente pagando';
            $mesa->save();
            TareaUsuarioHelper::CargarDatos($pedido->codigo, $usuario->id, 'mesas', 'entregar cuenta');
            $payload = array('mensaje' => 'La cuenta fue entregada al cliente');
        } else {
            $payload = array('error' => 'El pedido no existe o no fue servido');
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function CobrarPedido($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $idPedido = $parametros['idPedido'];
        $token = trim(explode('Bearer', $request->getHeaderLine('Authorization'))[1]);
        $dataUsuario = AutentificadorJWT::ObtenerData($token);
        $usuario = Usuario::where('mail', $dataUsuario->mail)->first();
        $pedido = Pedido::find($idPedido);

        if ($pedido && $pedido->estado == "cobrandolo") {
            $pedido->id_usuario = $usuario->id;
            $pedido->estado = 'cobrado';
            $pedido->save();
            $mesa = Mesa::find($pedido->id_mesa);
            $mesa->estado = 'cerrada';
            $mesa->save();
            TareaUsuarioHelper::CargarDatos($pedido->codigo, $usuario->id, 'mesas', 'cobrar pedido');
            $payload = array('mensaje' => 'El pedido fue cobrado');
        } else {
            $payload = array('error' => 'El pedido no existe o no se entrego la cuenta aun');
        }

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TiempoPedidoCliente($request, $response, $args)
    {
        $params = $request->getQueryParams();
        $codigoMesa = $params['codigoMesa'];
        $codigoPedido = $params['codigoPedido'];
        $payload = json_encode(array('error' => "algun codigo es incorrecto"));

        $mesa = Mesa::where('codigo', $codigoMesa)->first();
        if ($mesa) {
            $pedido = Pedido::where('codigo', $codigoPedido)->where('id_mesa', $mesa->id)->first();
            if ($pedido) {
                if ($pedido->estado == 'pendiente' || $pedido->estado == 'en preparacion' || $pedido->estado == 'listo para servir') {
                    $horaInicio = $pedido->hora_inicio;
                    $tiempoEstimado = $pedido->tiempo_estimado;
                    $arrayTE = explode(':',  $tiempoEstimado);
                    $horaEstFin = strtotime("+$arrayTE[0] hour", strtotime($horaInicio));
                    $horaEstFin = strtotime("+$arrayTE[1] minute", $horaEstFin);
                    $horaEstFin = strtotime("+$arrayTE[2] second", $horaEstFin);
                    $horaEstFin = date('H:i:s', $horaEstFin);

                    $arrayHEF = explode(':', date('H:i:s'));
                    $tiempoFaltante = strtotime("-$arrayHEF[0] hour", strtotime($horaEstFin));
                    $tiempoFaltante = strtotime("-$arrayHEF[1] minute", $tiempoFaltante);
                    $tiempoFaltante = strtotime("-$arrayHEF[2] second", $tiempoFaltante);
                    $tiempoFaltante = date('H:i:s', $tiempoFaltante);

                    if (strtotime($tiempoFaltante) > strtotime($tiempoEstimado)) {
                        $payload = json_encode(array('tiempo faltante del pedido' => 'En breve estara su pedido, disculpe la tardanza'));
                    } else {
                        $arrayTF = explode(':', $tiempoFaltante);
                        if ($arrayTF[0] == '00') {
                            $tiempoFaltante = (int)date('i', strtotime($tiempoFaltante));
                            $payload = json_encode(array('tiempo faltante del pedido' => "$tiempoFaltante minutos aproximadamente"));
                        } else {
                            $tiempoFaltante = date('H:i', strtotime($tiempoFaltante));
                            $payload = json_encode(array('tiempo faltante del pedido' => "$tiempoFaltante horas aproximadamente"));
                        }
                    }
                } else {
                    $payload = json_encode(array('tiempo faltante del pedido' => "Su pedido ya fue entregado"));
                }
            }
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function GuardarCsv($request, $response, $args)
    {
        $lista = Pedido::all();
        if (empty($lista)) {
            $payload = json_encode(array("mensaje" => "No hay pedidos cargados en el sistema"));
        } else {
            $linea = '';
            foreach ($lista as $pedido) {
                $linea .= $pedido->id . ',';
                $linea .= $pedido->codigo . ',';
                $linea .= $pedido->nombre_cliente . ',';
                $linea .= $pedido->id_usuario . ',';
                $linea .= $pedido->id_mesa . ',';
                $linea .= $pedido->id_productos . ',';
                $linea .= $pedido->cantidades . ',';
                $linea .= $pedido->precio . ',';
                $linea .= $pedido->estado . ',';
                $linea .= $pedido->tiempo_estimado . ',';
                $linea .= $pedido->hora_inicio . ',';
                $linea .= $pedido->hora_fin .  ',';
                $linea .= $pedido->foto .  ',';
                $linea .= $pedido->fecha . "\n";
            }
            $fecha = date('d-m-y');
            Archivos::GuardarArchivo("./assets/descargaPedidosCsv/pedidos_$fecha.csv", $linea);
            $payload = $linea;
        }
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'text/csv');
    }

    public function GuardarPdf($request, $response, $args)
    {
        $lista = Pedido::all();

        $dompdf = new Dompdf();

        if (!$lista) {
            $lista = "<h1>No hay pedidos</h1>";
        } else {
            $lista = PedidoHtml::TablaPedido($lista);
        }

        $dompdf->loadHtml($lista);
        $dompdf->setPaper('A2', 'landscape');
        $dompdf->render();
        $output = $dompdf->output();

        $fecha = date('d-m-y');
        Archivos::GuardarArchivo("./assets/descargaPedidosPdf/pedidos_$fecha.pdf", $output);

        $response->getBody()->write($output);
        return $response->withHeader('Content-Type', 'application/pdf');
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
        if (strcasecmp("pendiente", $estado) == 0 || strcasecmp("en preparacion", $estado) == 0 || strcasecmp("suspendido", $estado) == 0 || strcasecmp("listo para servir", $estado) == 0 || strcasecmp("servido", $estado) == 0 || strcasecmp("cobrado", $estado) == 0 || strcasecmp("cobrandolo", $estado) == 0) {
            $rta = true;
        }
        return $rta;
    }

    public static function verificarTiempoEstimado($tiempo)
    {
        $arrayHora = explode(":", $tiempo);
        $rta = true;
        if (count($arrayHora) >= 1 && count($arrayHora) <= 3) {
            foreach ($arrayHora as $key => $unidad) {
                if (!ctype_digit($unidad) || ($unidad > 2 && $key == 0) ||   ($unidad > 59 && ($key == 1 || $key == 2))) {
                    $rta = false;
                    break;
                }
            }
        }
        return $rta;
    }
}
