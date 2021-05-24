<?php

class Producto{
    public $id;
    public $nombre;
    public $tipo;
    public $stock;
    public $precio;

    public function crearProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("INSERT INTO productos (nombre, tipo, stock, precio) VALUES (:nombre, :tipo, :stock, :precio)");
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':stock', $this->stock, PDO::PARAM_INT);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public static function obtenerTodos()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Producto');
    }

    public static function obtenerProducto($id)
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("SELECT * FROM productos WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Producto');
    }

    public function modificarProducto()
    {
        $objAccesoDatos = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDatos->prepararConsulta("UPDATE productos SET nombre = :nombre, tipo = :tipo, stock = :stock, precio = :precio WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':tipo', $this->tipo, PDO::PARAM_STR);
        $consulta->bindValue(':stock', $this->stock, PDO::PARAM_INT);
        $consulta->bindValue(':precio', $this->precio, PDO::PARAM_INT);
        $consulta->execute();

        return $objAccesoDatos->obtenerUltimoId();
    }

    public function modificarStockProducto($cantidad)
    {
        $rta = false;
        if($this->stock >= $cantidad){
            $this->stock -= $cantidad;
            $this->modificarProducto();
            $rta = true;
        }
        return $rta;
    }

    public static function borrarProducto($id)
    {
        $objAccesoDato = AccesoDatos::obtenerInstancia();
        $consulta = $objAccesoDato->prepararConsulta("DELETE FROM usuarios WHERE id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function verificarTipo($tipo){
        $rta = false;
        if(strcasecmp("bebida", $tipo) == 0 || strcasecmp("comida", $tipo) == 0){
            $rta = true;
        }
        return $rta;
    }

    public static function verificarProductoPorId($id){
        $productos = self::obtenerTodos();
        $rta = false;
        foreach ($productos as $prod) {
           if($prod->id == $id){
               $rta = true;
               break;
           }
        }
        return $rta;
    }

    public static function verificarStock($id, $cantidad){
        $producto = self::obtenerProducto($id);
        $rta = false;
        if($producto && $producto->stock >= $cantidad){
            $rta = true;
        }
        return $rta;
    }
}