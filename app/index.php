<?php
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';

require_once './db/AccesoDatos.php';
require_once './db/AccesoDatosORM.php';

require_once './controllers/UsuarioController.php';
require_once './controllers/ProductoController.php';
require_once './controllers/MesaController.php';
require_once './controllers/PedidoController.php';
require_once './controllers/SubPedidoController.php';
require_once './controllers/LoginController.php';
require_once './controllers/EncuestaController.php';
require_once './controllers/InformeController.php';

require_once './middlewares/AutentificadorMW.php';

// Load ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

date_default_timezone_set('America/Argentina/Buenos_Aires');

// Instantiate App
$app = AppFactory::create();

$app->setBasePath((function () {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $uri = (string) parse_url('http://a' . $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (stripos($uri, $_SERVER['SCRIPT_NAME']) === 0) {
        return $_SERVER['SCRIPT_NAME'];
    }
    if ($scriptDir !== '/' && stripos($uri, $scriptDir) === 0) {
        return $scriptDir;
    }
    return '';
})());
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

//Database connection
$con = new AccesoDatosORM();

// // Add error middleware
$app->addErrorMiddleware(true, true, true);

const ROLES = ['mozo', 'socio', 'cocinero', 'bartender', 'cervecero'];

// Routes
$app->post('/login', \LoginController::class . ':Login');

$app->group('/usuarios', function (RouteCollectorProxy $group) {
    $group->get('[/]', \UsuarioController::class . ':TraerTodos');
    $group->get('/{id}', \UsuarioController::class . ':TraerUno');
    $group->post('[/]', \UsuarioController::class . ':CargarUno');
    $group->put('[/]', \UsuarioController::class . ':ModificarUno');
    $group->delete('[/]', \UsuarioController::class . ':BorrarUno');
})->add(new AutentificadorMW(['socio']));

$app->group('/productos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \ProductoController::class . ':TraerTodos')->add(new AutentificadorMW(ROLES));
    $group->get('/{id}', \ProductoController::class . ':TraerUno')->add(new AutentificadorMW(ROLES));
    $group->post('[/]', \ProductoController::class . ':CargarUno')->add(new AutentificadorMW(["socio"]));
    $group->post('/csv[/]', \ProductoController::class . ':CargarUnoCsv')->add(new AutentificadorMW(["socio"]));
    $group->put('[/]', \ProductoController::class . ':ModificarUno')->add(new AutentificadorMW(["socio"]));
    $group->delete('[/]', \ProductoController::class . ':BorrarUno')->add(new AutentificadorMW(["socio"]));
});

$app->group('/mesas', function (RouteCollectorProxy $group) {
    $group->get('[/]', \MesaController::class . ':TraerTodos')->add(new AutentificadorMW(["mozo", "socio"]));;
    $group->get('/{id}', \MesaController::class . ':TraerUno')->add(new AutentificadorMW(["mozo", "socio"]));;
    $group->post('[/]', \MesaController::class . ':CargarUno')->add(new AutentificadorMW(["socio"]));;
    $group->put('[/]', \MesaController::class . ':ModificarUno')->add(new AutentificadorMW(["mozo", "socio"]));;
    $group->delete('[/]', \MesaController::class . ':BorrarUno')->add(new AutentificadorMW(["socio"]));;
});

$app->group('/pedidos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \PedidoController::class . ':TraerTodos')->add(new AutentificadorMW(['socio']));
    $group->get('/id/{id}', \PedidoController::class . ':TraerUno')->add(new AutentificadorMW(['socio']));
    $group->get('/csv', \PedidoController::class . ':GuardarCsv')->add(new AutentificadorMW(['socio']));
    $group->get('/pdf', \PedidoController::class . ':GuardarPdf')->add(new AutentificadorMW(['socio']));
    $group->post('[/]', \PedidoController::class . ':CargarUno')->add(new AutentificadorMW(['mozo', 'socio']));
    $group->put('[/]', \PedidoController::class . ':ModificarUno')->add(new AutentificadorMW(['socio']));
    $group->put('/servir[/]', \PedidoController::class . ':ServirPedido')->add(new AutentificadorMW(['socio', 'mozo']));
    $group->put('/entregar-cuenta[/]', \PedidoController::class . ':EntregarCuenta')->add(new AutentificadorMW(['socio', 'mozo']));
    $group->put('/cobrar[/]', \PedidoController::class . ':CobrarPedido')->add(new AutentificadorMW(['socio', 'mozo']));
    $group->delete('[/]', \PedidoController::class . ':BorrarUno')->add(new AutentificadorMW(['socio']));
});

$app->group('/subpedidos', function (RouteCollectorProxy $group) {
    $group->get('[/]', \SubPedidoController::class . ':TraerTodos')->add(new AutentificadorMW(['socio']));
    $group->get('/codigo/{codigo}', \SubPedidoController::class . ':TraerPorCodigo')->add(new AutentificadorMW(['socio']));
    $group->get('/pendientes[/]', \SubPedidoController::class . ':TraerPendientes')->add(new AutentificadorMW(['socio', 'cocinero', 'bartender', 'cervecero']));
    $group->get('/acargo[/]', \SubPedidoController::class . ':TraerTodosACargo')->add(new AutentificadorMW(['socio', 'cocinero', 'bartender', 'cervecero']));
    $group->put('/tomar-pendiente[/]', \SubPedidoController::class . ':TomarPendiente')->add(new AutentificadorMW(['socio', 'cocinero', 'bartender', 'cervecero']));
    $group->put('/terminar[/]', \SubPedidoController::class . ':FinalizarSubPedido')->add(new AutentificadorMW(['socio', 'cocinero', 'bartender', 'cervecero']));
});

$app->group('/cliente', function (RouteCollectorProxy $group) {
    $group->get('/tiempo[/]', \PedidoController::class . ':TiempoPedidoCliente');
    $group->post('/encuesta[/]', \EncuestaController::class . ':CrearEncuesta');
});

$app->group('/informes', function (RouteCollectorProxy $group) {
    $group->get('/logins/{dias}', \InformeController::class . ':DiasYHorasLogin');
    $group->get('/operaciones-sector/{dias}', \InformeController::class . ':CantidadDeOperacionesSector');
    $group->get('/operaciones-sector-usuario/{dias}', \InformeController::class . ':CantidadDeOperacionesSectorUser');
    $group->get('/operaciones-usuario/{dias}', \InformeController::class . ':CantidadDeOperacionesUser');
    $group->get('/producto-mas-vendido/{dias}', \InformeController::class . ':ProductoMasVendido');
    $group->get('/producto-menos-vendido/{dias}', \InformeController::class . ':ProductoMenosVendido');
    $group->get('/fuera-de-tiempo/{dias}', \InformeController::class . ':NoEntregadoEnTiempo');
    $group->get('/cancelados/{dias}', \InformeController::class . ':Cancelados');
    $group->get('/mesa-mas-usada/{dias}', \InformeController::class . ':MesaMasUsada');
    $group->get('/mesa-menos-usada/{dias}', \InformeController::class . ':MesaMenosUsada');
    $group->get('/mesa-mayor-facturacion/{dias}', \InformeController::class . ':MesaQueMasFacturo');
    $group->get('/mesa-menor-facturacion/{dias}', \InformeController::class . ':MesaQueMenosFacturo');
    $group->get('/mesa-mayor-importe/{dias}', \InformeController::class . ':MesaMayorImporte');
    $group->get('/mesa-menor-importe/{dias}', \InformeController::class . ':MesaMenorImporte');
    $group->get('/facturacion[/]', \InformeController::class . ':FacturacionEntrefechas');
    $group->get('/mejores-comentarios/{dias}', \InformeController::class . ':MejoresComentarios');
    $group->get('/peores-comentarios/{dias}', \InformeController::class . ':PeoresComentarios');
})->add(new AutentificadorMW(['socio']));

$app->get('[/]', function (Request $request, Response $response) {
    //local: http://localhost/programacion3/TP_LaComanda/app/
    //remoto: https://molini-ignacio.herokuapp.com/
    $response->getBody()->write("TP La Comanda - Programacion 3");
    return $response;
});

$app->run();
