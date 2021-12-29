<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\User;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    use SendResponse, Pagination;

    public function getAdminLogs()
    {
        $logs = AdminLog::with("transactions", "transactions.last_order", "cards")->select("*");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $logs->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {

            $logs->where(function ($q) {
                $columns = Schema::getColumnListing('admin_logs');

                $q->whereHas("transactions", function ($q) {
                    $q->whereHas("user", function ($q) {
                        $q->where("user_name", 'LIKE', '%' . $_GET['query'] . '%');
                    })->orwhere("operation_number", 'LIKE', '%' . $_GET['query'] . '%');
                });

                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';

                    if ($key == "type") {
                        $logs->join('transactions', 'admin_logs.target_id', '=', 'transactions.id')->select("admin_logs.*");
                        $logs->orderBy('transactions.type', $sort);
                    } elseif ($key == "status") {
                        $logs->join('transactions', 'admin_logs.target_id', '=', 'transactions.id')->select("admin_logs.*");
                        $logs->join('order_statuses', 'transactions.last_order', '=', 'order_statuses.id');
                        $logs->join('statuses', 'order_statuses.status_id', '=', 'statuses.id');
                        $logs->orderBy('statuses.type', $sort);
                    } else {
                        $logs->orderBy($key,  $sort);
                    }
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($logs,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الحركات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function getUsers()
    {
        $users = User::select("*");
        // return $users->get();
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $users->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {

            $users->where(function ($q) {
                $columns = Schema::getColumnListing('users');
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $users->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($users,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب ألمستخدمين بنجاح ', [], $res["model"], null, $res["count"]);
    }
}