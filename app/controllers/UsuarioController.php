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

        // Creamos el usuario
        $usr = new Usuario();
        $usr->nombre = $nombre;
        $usr->apellido = $apellido;
        $usr->mail = $mail;
        $usr->clave = $clave;
        $usr->rol = $rol;
        $usr->estado = "activo";
        $usr->fecha_de_ingreso = date('Y-m-d H:i:s');

        if(Usuario::verificarRol($rol)){
            $usr->crearUsuario();
            $payload = json_encode(array("mensaje" => "Usuario creado con exito"));
        }
        else{
            $payload = json_encode(array("error" => "Rol incorrecto"));
        }

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno($request, $response, $args)
    {
        $idUsuario = $args['id'];
        $usuario = Usuario::obtenerUsuario($idUsuario);
        $payload = json_encode($usuario);

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerTodos($request, $response, $args)
    {
        $lista = Usuario::obtenerTodos();
        $payload = json_encode(array("listaUsuarios" => $lista));

        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    public function ModificarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $nombre = $parametros['nombre'];
        $apellido = $parametros['apellido'];
        $mail = $parametros['mail'];
        $clave = $parametros['clave'];
        $rol = $parametros['rol'];
        $estado = $parametros['estado'];

        $usr = new Usuario();
        $usr->nombre = $nombre;
        $usr->apellido = $apellido;
        $usr->mail = $mail;
        $usr->clave = $clave;
        $usr->rol = $rol;
        $usr->estado = $estado;


        $payload = json_encode(array("mensaje" => "Usuario modificado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }

    public function BorrarUno($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $usuarioId = $parametros['usuarioId'];
        Usuario::borrarUsuario($usuarioId);

        $payload = json_encode(array("mensaje" => "Usuario borrado con exito"));

        $response->getBody()->write($payload);
        return $response
          ->withHeader('Content-Type', 'application/json');
    }
}
