<?php
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';

use \App\Models\Usuario as Usuario;

class UsuarioController implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $apellido = $parametros['apellido'];
        $mail = $parametros['mail'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];

        $userMail = Usuario::where('mail', $mail)->first();

        if (!$userMail) {
            if (self::verificarRol($rol)) {
                $user = new Usuario();
                $user->nombre = $nombre;
                $user->apellido = $apellido;
                $user->mail = $mail;
                $user->clave = $clave;
                $user->rol = $rol;
                $user->estado = "activo";
                $user->fecha_de_ingreso = date('Y-m-d H:i:s');
                $user->save();
                $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
            } else {
                $payload = json_encode(array("error" => "Rol incorrecto"));
            }
        } else {
            $payload = json_encode(array("error" => "Ya se encuentra registrado un usuario con ese mail"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $id = $args['id'];
        $usuario = Usuario::find($id);

        if (!$usuario) {
            $payload = json_encode(array("mensaje" => "El usuario no existe"));
        } else {
            $payload = json_encode($usuario);
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Usuario::all();

        if (empty($lista)) {
            $payload = json_encode(array("mensaje" => "No hay usuarios cargados en el sistema"));
        } else {
            $payload = json_encode(array("lista usuarios" => $lista));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $id = $parametros['id'];
        $nombre = $parametros['nombre'];
        $apellido = $parametros['apellido'];
        $mail = $parametros['mail'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];
        $estado = $parametros['estado'];

        $user = Usuario::find($id);

        if (self::verificarRol($rol) && self::verificarEstado($estado)) {
            if ($user) {
                $user->nombre = $nombre;
                $user->apellido = $apellido;
                $user->mail = $mail;
                $user->clave = $clave;
                $user->rol = $rol;
                $user->estado = $estado;
                $user->save();
                $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));
            } else {
                $payload = json_encode(array("error" => "No existe un usuario con ese id"));
            }
        } else {
            $payload = json_encode(array("error" => "Rol o estado incorrecto"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'];

        $user = Usuario::find($id);

        if ($user) {
            $user->estado = "baja";
            $user->save();
            $user->delete();
            $payload = json_encode(array("mensaje" => "Usuario dado de baja con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe un usuario con ese id o ya fue dado de baja"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function verificarRol($rol)
    {
        $rta = false;
        if (strcasecmp("mozo", $rol) == 0 || strcasecmp("cocinero", $rol) == 0 || strcasecmp("cervecero", $rol) == 0 || strcasecmp("bartender", $rol) == 0 || strcasecmp("socio", $rol) == 0) {
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
}
