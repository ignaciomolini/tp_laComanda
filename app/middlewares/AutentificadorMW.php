<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AutentificadorMW
{
    public $roles;

    public function __construct($roles)
    {
        $this->roles = $roles;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            $header = $request->getHeaderLine('Authorization');
            if (!empty($header)) {
                $token = trim(explode('Bearer', $header)[1]);
            } else {
                $token = '';
            }
            AutentificadorJWT::VerificarToken($token);
            $data = AutentificadorJWT::ObtenerData($token);
            foreach ($this->roles as $rol) {
                if ($rol == $data->rol) {
                    $response = $handler->handle($request);
                    return $response;
                }
            }
            throw new Exception('No tiene autorizacion');
        } catch (Exception $e) {
            $response = new Response();
            $rta = json_encode(array('error' => $e->getMessage()));
            $response->getBody()->write($rta);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
