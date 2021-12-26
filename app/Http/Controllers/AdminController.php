<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use SendResponse, Pagination;

    public function getAdminLogs()
    {
        $logs = AdminLog::select("*");
        return $logs;
    }
}