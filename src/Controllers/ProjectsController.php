<?php
namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\ProjectService;

class ProjectsController extends BaseController
{
    public function __construct(private ProjectService $projectService)
    {
    }

    public function getUserProjects(Request $request): void
    {
        $user = $request->getAttribute('user');
        $projects = $this->projectService->getUserProjects($user['id']);
        Response::json(true, 'Projects retrieved successfully', 200, [
            'projects' => $projects
        ]);
    }

    public function createProject(Request $request): void
    {
        $user = $request->getAttribute('user');
        $project = $this->projectService->createProject($user['id'], $request->body);
        Response::json(true, 'Project created successfully', 201, [
            'project' => $project
        ]);
    }

    public function getProject(Request $request): void
    {
        $user = $request->getAttribute('user');
        $id = $this->paramId($request);
        $project = $this->projectService->getProject($id, $user['id']);
        Response::json(true, 'Project retrieved successfully', 200, [
            'project' => $project
        ]);
    }

    public function updateProject(Request $request): void
    {
        $user = $request->getAttribute('user');
        $id = $this->paramId($request);
        $project = $this->projectService->updateProject($id, $user['id'], $request->body);
        Response::json(true, 'Project updated successfully', 200, [
            'project' => $project
        ]);
    }

    public function deleteProject(Request $request): void
    {
        $user = $request->getAttribute('user');
        $id = $this->paramId($request);
        $this->projectService->deleteProject($id, $user['id']);
        Response::json(true, 'Project deleted successfully', 200);
    }
}
