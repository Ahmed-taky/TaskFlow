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

    public function calendar(Request $request): void
    {
        $user = $request->getAttribute('user');
        $projectId = $this->paramId($request);

        $query = $request->query;
        $month = isset($query['month']) ? (int) $query['month'] : null;
        $year = isset($query['year']) ? (int) $query['year'] : null;
        $from = $query['from'] ?? null;
        $to = $query['to'] ?? null;

        $hasMonth = array_key_exists('month', $query);
        $hasYear = array_key_exists('year', $query);
        $hasFrom = array_key_exists('from', $query);
        $hasTo = array_key_exists('to', $query);

        if ($hasMonth || $hasYear) {
            if ($hasMonth && ($month < 1 || $month > 12)) {
                Response::json(false, 'month must be between 1 and 12', 422);
                return;
            }
            if ($hasYear && $year < 1) {
                Response::json(false, 'year must be a positive number', 422);
                return;
            }
            if ($hasFrom || $hasTo) {
                Response::json(false, 'cannot mix month/year with from/to', 422);
                return;
            }
        }

        if ($hasFrom || $hasTo) {
            if (!$hasFrom || !$hasTo) {
                Response::json(false, 'from and to must be provided together', 422);
                return;
            }
            if (!strtotime($from) || !strtotime($to)) {
                Response::json(false, 'Invalid date format', 422);
                return;
            }
            if ($to < $from) {
                Response::json(false, 'to must not be before from', 422);
                return;
            }
        }

        $days = $this->taskService->getCalendar(
            $user['id'], $projectId, $month, $year, $from, $to
        );

        Response::json(true, 'Calendar retrieved successfully', 200, [
            'days' => $days,
        ]);
    }
}