<?php
session_start();


// Autoloader simples
spl_autoload_register(function ($class) {
    // Prefix 'App\' maps to 'backend/app/'
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/../config/config.php';

use App\Core\Router;
use App\Controllers\DashboardController;
use App\Controllers\EngineeringController;
use App\Controllers\AuthController;
use App\Controllers\AcademyController;
use App\Controllers\MapController;
use App\Controllers\LibraryController;
use App\Controllers\TeamController;
use App\Controllers\OsController;
use App\Controllers\EstoqueController;
use App\Controllers\ReportController;

$router = new Router();

// Rotas de Autenticação
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);

// Rotas Protegidas (Dashboard e Engenharia já estão protegidas via construtor ou podem ser via middleware, mas vamos adicionar no Dashboard/Engenharia)
// Adicionando a verificação de login nas requisições da rota baseadas no construtor
$router->get('/', function() { AuthController::requireLogin(); $c = new DashboardController(); $c->index(); });
$router->get('/simulador', function() { AuthController::requireLogin(); $c = new EngineeringController(); $c->simulator(); });
$router->get('/calculadora', function() { AuthController::requireLogin(); $c = new EngineeringController(); $c->calculator(); });

// Rotas da Academia e Biblioteca
$router->get('/academia', [AcademyController::class, 'index']);
$router->get('/academia/quiz', [AcademyController::class, 'quiz']);
$router->post('/academia/quiz', [AcademyController::class, 'quiz']);
$router->get('/biblioteca', [LibraryController::class, 'index']);

// Rotas do Mapa (Projetos MUBI)
$router->get('/mapa/upload', [MapController::class, 'upload']);
$router->post('/mapa/upload', [MapController::class, 'upload']);
$router->get('/mapa/view', [MapController::class, 'view']);

// Rotas de Gestão de RH (Equipe)
$router->get('/equipe', [TeamController::class, 'index']);
$router->get('/equipe/novo', [TeamController::class, 'create']);
$router->post('/equipe/novo', [TeamController::class, 'store']);
$router->get('/equipe/editar', [TeamController::class, 'edit']);
$router->post('/equipe/editar', [TeamController::class, 'update']);
$router->post('/equipe/excluir', [TeamController::class, 'delete']);

// Rotas de Ordens de Serviço (O.S)
$router->get('/os', [OsController::class, 'index']);
$router->post('/os/nova', [OsController::class, 'store']);
$router->get('/os/checkout', [OsController::class, 'checkout']);
$router->post('/os/completa', [OsController::class, 'complete']);

$router->get('/estoque', [\App\Controllers\EstoqueController::class, 'index']);
$router->post('/estoque/transfer', [\App\Controllers\EstoqueController::class, 'transferir']);
$router->post('/estoque/report', [\App\Controllers\EstoqueController::class, 'report']);

$router->get('/apr', [\App\Controllers\AprController::class, 'index']);
$router->get('/apr/nova', [\App\Controllers\AprController::class, 'create']);
$router->post('/apr/salvar', [\App\Controllers\AprController::class, 'store']);
$router->get('/apr/view', [\App\Controllers\AprController::class, 'view']);

$router->get('/perigo', [\App\Controllers\PerigoController::class, 'index']);
$router->get('/perigo/novo', [\App\Controllers\PerigoController::class, 'create']);
$router->post('/perigo/salvar', [\App\Controllers\PerigoController::class, 'store']);
$router->get('/perigo/view', [\App\Controllers\PerigoController::class, 'view']);

// Rotas de Relatórios (Croqui e CSV)
$router->get('/relatorios/exportar_os', [ReportController::class, 'exportOs']);
$router->post('/relatorios/importar_os', [ReportController::class, 'importOs']);

// Executar
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
