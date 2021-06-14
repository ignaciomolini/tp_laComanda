<?php
require_once './models/Login.php';
require_once './middlewares/AutentificadorJWT.php';

use \App\Models\Login as Login;
use \App\Models\Usuario as Usuario;

class LoginController
{
    public function Login($request, $response, $args)
    {
        $parametros = $request->getParsedBody();

        $mail = $parametros['mail'];
        $clave = $parametros['clave'];

        $user = Usuario::where('mail', $mail)->where('clave', $clave)->first();

        if($user){
            $log = new Login();
            $log->mail = $mail;
            $log->hora_inicio = date('H:i:s');
            $log->fecha = date('Y-m-d');
            $log->save();
            $data = array('mail' => $user->mail, 'rol' => $user->rol, 'estado' => $user->estado);
            $token = AutentificadorJWT::CrearToken($data);
            $payload = json_encode(array('jwt' => $token));
        }
        else{
            $payload = json_encode(array('error' => 'Clave o mail incorrectos'));
        }
        
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
