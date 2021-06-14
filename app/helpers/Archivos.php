<?php

class Archivos
{
    public static function MoverArchivo($nombre, $archivo, $destino)
    {
        $nombreCompleto = $archivo->getClientFilename();
        $extension = explode(".", $nombreCompleto);
        $extension = array_reverse($extension);
        $ruta = $destino . $nombre . "." . $extension[0];
        $archivo->moveTo($ruta);
        return $ruta;
    }

    public static function LeerArchivo($ruta)
    {
        $arrayRetorno = array();
        if ($archivo = fopen($ruta, "r")) {
            while ($arrayUsuarios = fgetcsv($archivo)) {
                array_push($arrayRetorno, $arrayUsuarios);
            }
            fclose($archivo);
        }
        return $arrayRetorno;
    }

    public static function GuardarArchivo($ruta, $contenido)
    {
        if ($archivo = fopen($ruta, 'w')) {
            fputs($archivo, $contenido);
            fclose($archivo);
        }
    }
}
