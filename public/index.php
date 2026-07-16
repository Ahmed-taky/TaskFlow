<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Middleware\LoggerMiddleware;
use Dotenv\Dotenv;
Dotenv::createImmutable(__DIR__ . '/../')->load();

use App\Core\ErrorHandler;
use App\Helpers\Request;
use App\Database\Database;
use App\Repositories\UserRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\TasksRepository;
use App\Services\AuthService;
use App\Services\UserService;
use App\Services\ProjectService;
use App\Services\TasksService;
use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Controllers\ProjectsController;
use App\Controllers\TasksController;
use App\Middleware\AuthMiddleware;
use App\Routes\Router;
use App\Middleware\CORSMiddleware;
use App\Core\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

use App\Core\Container;
// Config
$container = new Container();

$request = new Request();
$connection = (new Database())->getConnection();
$Monolog = new MonologLogger("App");
$Monolog->pushHandler(new StreamHandler(__DIR__ . "/../logs/app.log", MonologLogger::DEBUG));
$logger = new Logger($Monolog);

$globalErorHandler = new ErrorHandler($logger);

// Primitives
$container->set('connection', $connection);
$container->set('logger', $logger);

// Repositories
$container->set('userRepository', fn(Container $c) => new UserRepository($c->get('connection')));
$container->set('projectRepository', fn(Container $c) => new ProjectRepository($c->get('connection')));
$container->set('tasksRepository', fn(Container $c) => new TasksRepository($c->get('connection')));

// Services
$container->set('authService', fn(Container $c) => new AuthService(
    $c->get('userRepository'),
    $c->get('projectRepository'),
    $c->get('connection'),
));
$container->set('userService', fn(Container $c) => new UserService(
    $c->get('userRepository'),
    $c->get('projectRepository'),
    $c->get('tasksRepository'),
));
$container->set('projectService', fn(Container $c) => new ProjectService($c->get('projectRepository')));
$container->set('tasksService', fn(Container $c) => new TasksService(
    $c->get('tasksRepository'),
    $c->get('projectRepository'),
));

// Controllers
$container->set('authController', fn(Container $c) => new AuthController($c->get('authService')));
$container->set('userController', fn(Container $c) => new UserController($c->get('userService')));
$container->set('projectsController', fn(Container $c) => new ProjectsController($c->get('projectService')));
$container->set('tasksController', fn(Container $c) => new TasksController($c->get('tasksService')));

// Middleware
$container->set('corsMiddleware', fn() => new CorsMiddleware());
$container->set('loggerMiddleware', fn(Container $c) => new LoggerMiddleware($c->get('logger')));
$container->set('authMiddleware', fn(Container $c) => new AuthMiddleware($c->get('userRepository')));

// Routes
$router = new Router();

$router->use($container->resolve('corsMiddleware', 'handle'));
$router->use($container->resolve('loggerMiddleware', 'logRequestTime'));

$router->post('/auth/register', $container->resolve('authController', 'register'));
$router->post('/auth/login', $container->resolve('authController', 'login'));

$authGuard = $container->resolve('authMiddleware', 'handle');

$router->get('/user/me', $container->resolve('userController', 'me'), [$authGuard]);
$router->put('/user/me', $container->resolve('userController', 'updateProfile'), [$authGuard]);
$router->get('/user/dashboard', $container->resolve('userController', 'dashboard'), [$authGuard]);

$router->get('/projects', $container->resolve('projectsController', 'getUserProjects'), [$authGuard]);
$router->post('/projects', $container->resolve('projectsController', 'createProject'), [$authGuard]);
$router->get('/projects/{id}', $container->resolve('projectsController', 'getProject'), [$authGuard]);
$router->patch('/projects/{id}', $container->resolve('projectsController', 'updateProject'), [$authGuard]);
$router->delete('/projects/{id}', $container->resolve('projectsController', 'deleteProject'), [$authGuard]);

$router->get('/projects/{id}/tasks', $container->resolve('tasksController', 'getProjectTasks'), [$authGuard]);
$router->post('/projects/{id}/tasks', $container->resolve('tasksController', 'createTask'), [$authGuard]);
$router->get('/projects/{id}/calendar', $container->resolve('tasksController', 'calendar'), [$authGuard]);

$router->patch('/tasks/{id}', $container->resolve('tasksController', 'updateTask'), [$authGuard]);
$router->delete('/tasks/{id}', $container->resolve('tasksController', 'deleteTask'), [$authGuard]);

$globalErorHandler->handle(function () use ($router, $request) {
    $router->buildDispatcher();
    $router->handle($request);
});
