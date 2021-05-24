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
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerUsuario($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM usuarios WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public function modificarUsuario()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE usuarios SET nombre = :nombre, apellido = :apellido, mail = :mail, clave = :clave, rol = :rol, estado = :estado WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':apellido', $this->apellido, PDO::PARAM_STR);
        $consulta->bindValue(':mail', $this->mail, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $this->clave, PDO::PARAM_STR);
        $consulta->bindValue(':rol', $this->rol, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function borrarUsuario($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("UPDATE usuarios SET fecha_de_baja = :fecha_de_baja, estado = :estado WHERE id = :id");
        $fecha = date('Y-m-d H:i:s');
        $estado = "baja";
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_de_baja', $fecha, PDO::PARAM_STR);
        $consulta->execute();
    }

    public static function obtenerEstado($id){
        $usuarios = self::obtenerTodos();
        $estado = false;
        foreach ($usuarios as $usr) {
           if($usr->id == $id){
               $estado = $usr->estado;
               break;
           }
        }
        return $estado;
    }

    public static function obtenerRol($id){
        $usuarios = self::obtenerTodos();
        $rol = false;
        foreach ($usuarios as $usr) {
           if($usr->id == $id){
               $rol = $usr->rol;
               break;
           }
        }
        return $rol;
    }

    public static function verificarRol($rol){
        $rta = false;
        if(strcasecmp("mozo", $rol) == 0 || strcasecmp("cocinero", $rol) == 0 || strcasecmp("cervecero", $rol) == 0 || strcasecmp("bartender", $rol) == 0 || strcasecmp("socio", $rol) == 0){
            $rta = true;
        }
        return $rta;
    }

    public static function verificarEstado($estado){
        $rta = false;
        if(strcasecmp("activo", $estado) == 0 || strcasecmp("suspendido", $estado) == 0){
            $rta = true;
        }
        return $rta;
    }

    public static function verificarUsuarioPorId($id){
        $usuarios = self::obtenerTodos();
        $rta = false;
        foreach ($usuarios as $usr) {
           if($usr->id == $id){
               $rta = true;
               break;
           }
        }
        return $rta;
    }

}