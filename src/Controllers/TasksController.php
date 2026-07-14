<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\TasksService;

class TasksController extends BaseController
{
    public function __construct(private TasksService $taskService)
    {
    }

    public function getProjectTasks(Request $request): void
    {
        $user = $request->getAttribute('user');
        $projectId = $this->paramId($request);
        $tasks = $this->taskService->getTasksByProject($user['id'], $projectId);
        Response::json(true, 'Tasks retrieved successfully', 200, [
            'tasks' => $tasks
        ]);
    }

    public function createTask(Request $request): void
    {
        $user = $request->getAttribute('user');
        $projectId = $this->paramId($request);
        $task = $this->taskService->createTask($user['id'], $projectId, $request->body);
        Response::json(true, 'Task created successfully', 201, [
            'task' => $task
        ]);
    }

    public function updateTask(Request $request): void
    {
        $user = $request->getAttribute('user');
        $id = $this->paramId($request);
        $task = $this->taskService->updateTask($user['id'], $id, $request->body);
        Response::json(true, 'Task updated successfully', 200, [
            'task' => $task
        ]);
    }

    public function deleteTask(Request $request): void
    {
        $user = $request->getAttribute('user');
        $id = $this->paramId($request);
        $this->taskService->deleteTask($id, $user['id']);
        Response::json(true, 'Task deleted successfully', 200);
    }
}
