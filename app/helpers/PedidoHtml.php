<?php

class PedidoHtml
{
    public static function TablaPedido($lista)
    {
        $tbody = '';
        foreach ($lista as $pedido) {
            $tbody .= "
            <tr>
                <td>$pedido->id</td>
                <td>$pedido->codigo</td>
                <td>$pedido->nombre_cliente</td>
                <td>$pedido->id_usuario</td>
                <td>$pedido->id_mesa</td>
                <td>$pedido->id_productos</td>
                <td>$pedido->cantidades</td>
                <td>$$pedido->precio</td>
                <td>$pedido->estado</td>
                <td>$pedido->tiempo_estimado</td>
                <td>$pedido->hora_inicio</td>
                <td>$pedido->hora_fin</td>  
                <td>$pedido->fecha</td>
            </tr>
            ";
        }

        $estilos = "
        <style>
            table{
                font-family:'Lucida Sans', 'Lucida Sans Regular', 'Lucida Grande', 'Lucida Sans Unicode', Geneva, Verdana, sans-serif;
                border-collapse: collapse;
                margin: 0 auto;
            }

            th, td {           
                border: 2px solid black;    
            }

            td {
                text-transform: capitalize;
                text-align: center;
                font-size: 23px;                
                padding: 10px;
            }
            
            thead tr {
            background-color: rgba(109, 160, 255, 0.7);
            }
            
            thead th {
            text-transform: uppercase;
            font-size: 18px;
            padding: 18px;
            }
              
        </style>
        ";

        $tabla = "$estilos
        <table>
            <thead>
            <tr>
                <th>id</th>
                <th>codigo</th>
                <th>nombre cliente</th>
                <th>id usuario</th>
                <th>id mesa</th>
                <th>id productos</th>
                <th>cantidades</th>
                <th>precio</th>
                <th>estado</th>
                <th>tiempo estimado</th>
                <th>hora inicio</th>
                <th>hora fin</th>
                <th>fecha</th>
            </tr>
            </thead>
            <tbody>
            $tbody
            </tbody>
        </table>
        ";
        return $tabla;
    }
}
