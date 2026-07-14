<?php

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;

abstract class BaseController
{
    protected function paramId(Request $request): int
    {
        if (!isset($request->params['id'])) {
            Response::json(false, 'Id is required', 422);
            exit;
        }
        return (int) $request->params['id'];
    }

}
