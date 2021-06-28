<?php

use \App\Models\Producto as Producto;
use \App\Models\SubPedido as SubPedido;
use \App\Models\Pedido as Pedido;

class CuentaHtml
{
    public static function TablaCuenta($pedido)
    {
        $subPedidos = SubPedido::where('codigo_pedido', $pedido->codigo)->get();
        $total = $pedido->precio;
        $tbody = '';
        foreach ($subPedidos as $subPedido) {
            $producto = Producto::find($subPedido->id_producto);
            $tbody .= "
            <tr>
                <td>$producto->nombre</td>
                <td>$subPedido->cantidad</td>
                <td>$$producto->precio</td>
                <td>$".$subPedido->cantidad*$producto->precio."</td>
            </tr>
            ";
        }

        $estilos = "
        <style>
        .container {
            background-color: rgb(246, 246, 214);
            text-align: center;
            font-family: 'Courier New', Courier, monospace;
          }
          table {
            //margin: auto;
            padding-bottom: 15px;
          }
    
          th,
          td {
            padding: 6px;
            text-align: center;
            font-size: 18px;
          }
    
          thead th {
            text-transform: uppercase;
            font-size: 18px;
            padding: 15px;
          }
    
          h1 {
            padding-top: 20px;     
          }

          hr{
            border: 1px dotted grey;
          }
    
          p {
            font-size: 20px;
            padding-bottom: 25px;
          }
    
          #tablaTotal {
            border-top: 1px dotted grey;
            width: fit-content;
            padding-bottom: 0px;
          }
        </style>
        ";

        $tabla = "$estilos
        <div class='container'>
        <h1>La Comanda</h1>
        <hr>
        <table>
            <thead>
            <tr>
                <th>producto</th>
                <th>cantidad</th>
                <th>precio</th>
                <th>importe</th>          
            </tr>
            </thead>
            <tbody>
            $tbody
            </tbody>
        </table> 
        <table id='tablaTotal'>
          <thead>
            <tr>
                <th>total</th>
                <th>$$total</th>          
            </tr>
          </thead>
        </table> 
        <hr>
        <p>Gracias por su visita</p>  
        </div>
        ";
        return $tabla;
    }
}
