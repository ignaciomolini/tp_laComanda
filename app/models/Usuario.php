<?php

class Usuario
{
    public $id;
    public $nombre;
    public $apellido;
    public $mail;
    public $clave;
    public $rol;
    public $estado;
    public $fecha_de_ingreso;
    public $fecha_de_baja;
    
    public function crearUsuario()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO usuarios (nombre, apellido, mail, clave, rol, estado, fecha_de_ingreso) VALUES (:nombre, :apellido, :mail, :clave, :rol, :estado, :fecha_de_ingreso)");
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
        $consulta->bindValue(':mail', $this->mail, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
        $consulta->bindValue(':rol', $this->rol, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_de_ingreso', $this->fecha_de_ingreso, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, apellido, mail, clave, rol, estado, fecha_de_ingreso FROM usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerUsuario($idUsuario)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT id, nombre, apellido, mail, clave, rol, estado, fecha_de_ingreso FROM usuarios WHERE id = :idUsuario");
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public function modificarUsuario()
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET nombre = :nombre, apellido = :apellido, mail = :mail, clave = :clave, rol = :rol, estado = :estado WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
        $consulta->bindValue(':mail', $this->mail, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
        $consulta->bindValue(':rol', $this->rol, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function borrarUsuario($idUsuario)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fecha_de_baja = :fecha_de_baja, estado = :estado WHERE id = :id");
        $fecha = new DateTime(date("d-m-Y"));
        $estado = "baja";
        $consulta->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_de_baja', date_format($fecha, 'Y-m-d H:i:s'));
        $consulta->execute();
    }

    public static function verificarRol($rol){
        $rta = false;
        if(strcasecmp("mozo", $rol) == 0 || strcasecmp("cocinero", $rol) == 0 || strcasecmp("cervezero", $rol) == 0 || strcasecmp("bartender", $rol) == 0 || strcasecmp("socio", $rol) == 0){
            $rta = true;
        }
        return $rta;
    }


}