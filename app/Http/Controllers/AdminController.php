<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\User;
use App\Models\BoxLog;
use App\Models\AdminLog;
use App\Events\BoxSocket;
use App\Traits\Pagination;
use App\Models\OrderKeyType;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\Notifications;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use SendResponse, Pagination;
    public function getBox()
    {
        $box = Box::first();
        return $this->send_response(200, 'تم جلب القاصة', [], $box);
    }
    public function getBoxLogs()
    {
        $box_logs = BoxLog::select("*");
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($box_logs->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب سجلات القاصة', [], $res["model"], null, $res["count"]);
    }
    public function getNotifications()
    {
        $notifications = Notifications::where("to_user", auth()->user()->id)->where("seen", false);
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($notifications->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, trans("message.get.all.notifications"), [], $res["model"], null, $res["count"]);
    }
    public function getAdminLogs()
    {

        if (isset($_GET["type"])) {
            if ($_GET["type"] ===  "transactions") {
                error_log("transactions");
                $logs = AdminLog::with("transactions", "transactions.last_status")->whereHas("transactions");
            } elseif ($_GET["type"] == "cards") {
                error_log("cards");
                $logs = AdminLog::with("cards", "card.serial_keys")->whereHas("cards");
            }
        }

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
                    })->orwhere("operation_number", 'LIKE', '%' . $_GET['query'] . '%')->orwhere("net_price", 'LIKE', '%' . $_GET['query'] . '%');
                });
                foreach ($columns as $column) {
                    $q->orWhere("admin_logs." . $column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter' || $key == 'type') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    if ($key == "status") {
                        $logs->join('transactions', 'admin_logs.target_id', '=', 'transactions.id')->select("admin_logs.*");
                        $logs->join('order_statuses', 'transactions.last_order', '=', 'order_statuses.id');
                        $logs->join('statuses', 'order_statuses.status_id', '=', 'statuses.id');
                        $logs->orderBy('statuses.type', $sort);
                    } else {
                        $logs->join('transactions', 'admin_logs.target_id', '=', 'transactions.id')->select("admin_logs.*");
                        $logs->orderBy('transactions.' . $key, $sort);
                        // $logs->orderBy($key,  $sort);
                    }
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($logs->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
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
        $res = $this->paging($users->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب ألمستخدمين بنجاح ', [], $res["model"], null, $res["count"]);
    }
    public function getOrderKeyTypes()
    {
        $get = OrderKeyType::select('*');
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $get->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {

            $get->where(function ($q) {
                $columns = Schema::getColumnListing('order_key_types');
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
                    $get->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($get->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب العنصر بنجاح', [], $res["model"], null, $res["count"]);
    }

    public function seenNotification(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'id' => 'required|exists:notifications,id',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $notification = Notifications::find($request["id"]);
        $notification->update([
            "seen" => true
        ]);
        return $this->send_response(200, 'تم تعديل الحالة بنجاح', [], $notification);
    }

    public function withdrawBox(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'value' => 'required',
            'target' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'يرجى ادخال قيمة السحب', [], $validator->errors());
        }
        $box = Box::first();

        if ($request["target"] == "company_ratio") {

            if ($request["value"] > $box->company_ratio) {
                return $this->send_response(400, 'القيمة المدخلة اكبر من القيمة المتاحة', [], []);
            } else {
                $box->update([
                    "company_ratio" => $box->company_ratio - $request["value"]
                ]);
                $log = BoxLog::create([
                    "text" => "تم عملية سحب مبلغ من رصيد الشركة",
                    "user_id" => auth()->user()->id,
                    "value" => $request["value"],
                ]);
            }
        } else if ($request["target"] == "programmer_ratio") {
            if ($request["value"] > $box->programmer_ratio) {
                return $this->send_response(400, 'القيمة المدخلة اكبر من القيمة المتاحة', [], []);
            } else {
                $box->update([
                    "programmer_ratio" => $box->programmer_ratio - $request["value"]
                ]);
                $log =  BoxLog::create([
                    "text" => "تم عملية سحب مبلغ من رصيد المطور",
                    "user_id" => auth()->user()->id,
                    "value" => $request["value"],
                ]);
            }
        } else {
            if ($request["value"] > $box->managment_ratio) {
                return $this->send_response(400, 'القيمة المدخلة اكبر من القيمة المتاحة', [], []);
            } else {
                $box->update([
                    "managment_ratio" => $box->managment_ratio - $request["value"]
                ]);
                $log =  BoxLog::create([
                    "text" => "تم عملية سحب مبلغ من رصيد الإدارة",
                    "user_id" => auth()->user()->id,
                    "value" => $request["value"],
                ]);
            }
            broadcast(new BoxSocket($box));
        }
        return  $this->send_response(200, 'تم سحب المبلغ بنجاح', [], BoxLog::find($log->id));
    }
}
