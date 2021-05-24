<?php
require_once './models/Usuario.php';
require_once './interfaces/IApiUsable.php';

class UsuarioController extends Usuario implements IApiUsable
{
    public function CargarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $apellido = $parametros['apellido'];
        $mail = $parametros['mail'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];

        $usr = new Usuario();
        $usr->nombre = $nombre;
        $usr->apellido = $apellido;
        $usr->mail = $mail;
        $usr->clave = $clave;
        $usr->rol = $rol;
        $usr->estado = "activo";
        $usr->fecha_de_ingreso = date('Y-m-d H:i:s');

        if (parent::verificarRol($rol)) {
            $usr->crearUsuario();
            $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
        } else {
            $payload = json_encode(array("error" => "Rol incorrecto"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $idUsuario = $args['id'];
        $usuario = parent::obtenerUsuario($idUsuario);

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
        $lista = parent::obtenerTodos();

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

        $usr = new Usuario();
        $usr->id = (int) $id;
        $usr->nombre = $nombre;
        $usr->apellido = $apellido;
        $usr->mail = $mail;
        $usr->clave = $clave;
        $usr->rol = $rol;
        $usr->estado = $estado;

        if (parent::verificarRol($rol) && parent::verificarEstado($estado)) {
            if (parent::verificarUsuarioPorId($id)) {
                $usr->modificarUsuario();
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
        $idUsuario = $parametros['idUsuario'];

        if (parent::verificarUsuarioPorId($idUsuario) && parent::obtenerEstado($idUsuario) != "baja") {
            parent::borrarUsuario($idUsuario);
            $payload = json_encode(array("mensaje" => "Usuario dado de baja con exito"));
        } else {
            $payload = json_encode(array("error" => "No existe un usuario con ese id o ya fue dado de baja"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
