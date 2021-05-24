<?php

class Mesa
{
    public $id;
    public $codigo;
    public $estado;

    public function crearMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO mesas (codigo, estado) VALUES (:codigo, :estado)");
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Mesa');
    }

    public static function obtenerMesa($idMesa)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM mesas WHERE id = :idMesa");
        $consulta->bindValue(':idMesa', $idMesa, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Mesa');
    }

    public function modificarMesa()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE mesas SET estado = :estado WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public function modificarEstadoMesa($estado){
        $rta = false;
        if(self::verificarEstado($estado)){
            $this->estado = $estado;
            $this->modificarMesa();
            $rta = true;
        }
        return $rta;
    }

    public static function borrarMesa($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM mesas WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function obtenerEstado($id)
    {
        $mesas = self::obtenerTodos();
        $estado = false;
        foreach ($mesas as $mesa) {
            if ($mesa->id == $id) {
                $estado = $mesa->estado;
                break;
            }
        }
        return $estado;
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

    public static function verificarMesaPorId($id)
    {
        $mesas = self::obtenerTodos();
        $rta = false;
        foreach ($mesas as $mesa) {
            if ($mesa->id == $id) {
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
        $mesas = self::obtenerTodos();
        $rta = false;
        foreach ($mesas as $mesa) {
            if ($mesa->codigo == $codigo) {
                $rta = true;
                break;
            }
        }
        return $rta;
    }
}
