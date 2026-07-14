<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Helpers\Response;
use Dotenv\Dotenv;
use Symfony\Component\RateLimiter\RateLimit;
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


$request = new Request();
$connection = (new Database())->getConnection();

// Repositories
$userRepository = new UserRepository($connection);
$projectRepository = new ProjectRepository($connection);
$tasksRepository = new TasksRepository($connection);

// Services
$authService = new AuthService($userRepository, $projectRepository, $connection);
$userService = new UserService($userRepository);
$projectService = new ProjectService($projectRepository);
$tasksService = new TasksService($tasksRepository, $projectRepository);

// Controllers
$authController = new AuthController($authService);
$userController = new UserController($userService);
$projectsController = new ProjectsController($projectService);
$tasksController = new TasksController($tasksService);

// Middleware
$authMiddleware = new AuthMiddleware($userRepository);

// Routes
$router = new Router();

$router->post('/auth/register', [$authController, 'register']);
$router->post('/auth/login', [$authController, 'login']);

$router->get('/user/me', [$userController, 'me'], [[$authMiddleware, 'handle']]);
$router->put('/user/me', [$userController, 'updateProfile'], [[$authMiddleware, 'handle']]);

// Projects
$router->get('/projects', [$projectsController, 'getUserProjects'], [[$authMiddleware, 'handle']]);
$router->post('/projects', [$projectsController, 'createProject'], [[$authMiddleware, 'handle']]);
$router->get('/projects/{id}', [$projectsController, 'getProject'], [[$authMiddleware, 'handle']]);
$router->patch('/projects/{id}', [$projectsController, 'updateProject'], [[$authMiddleware, 'handle']]);
$router->delete('/projects/{id}', [$projectsController, 'deleteProject'], [[$authMiddleware, 'handle']]);

// Tasks scoped to project
$router->get('/projects/{id}/tasks', [$tasksController, 'getProjectTasks'], [[$authMiddleware, 'handle']]);
$router->post('/projects/{id}/tasks', [$tasksController, 'createTask'], [[$authMiddleware, 'handle']]);

// Tasks direct (update / move / delete)
$router->patch('/tasks/{id}', [$tasksController, 'updateTask'], [[$authMiddleware, 'handle']]);
$router->delete('/tasks/{id}', [$tasksController, 'deleteTask'], [[$authMiddleware, 'handle']]);

$router->buildDispatcher();
ErrorHandler::handle(function () use ($router, $request) {
    $router->handle($request);
});
