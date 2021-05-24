<?php

class Pedido
{
    public $id;
    public $codigo;
    public $nombre_cliente;
    public $id_usuario;
    public $id_mesa;
    public $id_producto;
    public $cantidad;
    public $precio;
    public $estado;
    public $tiempo_estimado;
    public $hora_inicio;
    public $hora_fin;

    public function crearPedido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO pedidos (codigo, nombre_cliente, id_usuario, id_mesa, id_producto, cantidad, precio, estado, hora_inicio) VALUES (:codigo, :nombre_cliente, :id_usuario, :id_mesa, :id_producto, :cantidad, :precio, :estado, :hora_inicio)");
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':nombre_cliente', $this->nombre_cliente, PDO::PARAM_STR);
        $consulta->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':id_producto', $this->id_producto, PDO::PARAM_INT);
        $consulta->bindValue(':cantidad', $this->cantidad, PDO::PARAM_INT);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':hora_inicio', $this->hora_inicio, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Pedido');
    }

    public static function obtenerPedido($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM pedidos WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Pedido');
    }

    public function modificarPedido()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE pedidos SET id_usuario = :id_usuario, estado = :estado, tiempo_estimado = :tiempo_estimado, hora_fin = :hora_fin WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':id_usuario', $this->id_usuario, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':tiempo_estimado', $this->tiempo_estimado, PDO::PARAM_STR);
        $consulta->bindValue(':hora_fin', $this->hora_fin, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarPedido($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM pedidos WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function calcularPrecioTotal($precio, $cantidad)
    {
        return $precio * $cantidad;
    }

    public static function verificarTiempoEstimado($tiempo)
    {
        $arrayHora = explode(":", $tiempo);
        $rta = false;
        if (count($arrayHora) >= 1 && count($arrayHora) <= 3) {
            foreach ($arrayHora as $key => $unidad) {
                if (!ctype_digit($unidad) || ($unidad > 59 && ($key == 1 || $key == 2))) {
                    $rta = false;
                    break;
                } else {
                    $rta = true;
                }
            }
        }
        return $rta;
    }

    public static function verificarEstado($estado)
    {
        $rta = false;
        if (strcasecmp("pendiente", $estado) == 0 || strcasecmp("en preparacion", $estado) == 0 || strcasecmp("suspendido", $estado) == 0 || strcasecmp("listo para servir", $estado) == 0) {
            $rta = true;
        }
        return $rta;
    }

    public static function verificarPedidoPorId($id){
        $pedidos = self::obtenerTodos();
        $rta = false;
        foreach ($pedidos as $pedido) {
           if($pedido->id == $id){
               $rta = true;
               break;
           }
        }
        return $rta;
    }

    public static function generarCodigoAleatorio()
    {
        $carac = '0123456789abcdefghijklmnopqrstuvwxyz';
        do {
            $codigo = substr(str_shuffle($carac), 0, 5);
        } while (self::validarCodigoAleatorio($codigo));
        return $codigo;
    }

    public static function validarCodigoAleatorio($codigo)
    {
        $pedidos = self::obtenerTodos();
        $rta = false;
        foreach ($pedidos as $pedido) {
            if ($pedido->codigo == $codigo) {
                $rta = true;
                break;
            }
        }
        return $rta;
    }
}
