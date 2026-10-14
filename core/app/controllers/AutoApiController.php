<?php
namespace App\Controllers;
use App\Services\AutoApiService;
use App\Services\UserService;

class AutoApiController extends \Zoomx\Controllers\Controller
{
    public function getVehicleData()
    {
        zoomx()->autoloadResource(false);
        UserService::requireAccess();

        $data = AutoApiService::getVehicleData($_GET);

        return jsonx([
            'data' => $data
        ]);
    }
}