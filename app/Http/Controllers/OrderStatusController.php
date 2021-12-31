<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\ChangeCurrncy;
use App\Models\Company;
use App\Models\Deposit;
use App\Models\joinRelations;
use App\Models\Status;
use App\Traits\Pagination;
use App\Models\OrderStatus;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderStatusController extends Controller
{
    use SendResponse, Pagination;

    public function depositChangeState($order_status, $request)
    {

        $data = [];
        if ($request["type"] == 1) {
            $status = Status::where("type", 1)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type
            ];
        } elseif ($request["type"] == 2) {
            $status = Status::where("type", 2)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,
                "before_operation" => $order_status->transactions->user->points,
                "after_operation" => $order_status->transactions->user->points + $order_status->transactions->value,
            ];
            $user_id =  $order_status->transactions->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points + $order_status->transactions->value
            ]);
        } elseif ($request["type"] == 3) {
            $status = Status::where("type", 3)->first();
            $relations = joinRelations::where("payment_method_id", $order_status->transactions->payment_method_id)->first();
            $currency = ChangeCurrncy::where("currency", $relations->companies->currncy_type)->first();
            $new_points = $request["value"] * $currency->points;

            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,
                "before_operation" => $order_status->transactions->user->points,
                "after_operation" => $order_status->transactions->user->points + $new_points,
            ];
            if (array_key_exists("message", $request)) {
                $data["message"] = $request["message"];
            }
            if (array_key_exists("value", $request)) {
                $order_status->transactions->update([
                    "value" => $new_points
                ]);
            }
            $user_id =  $order_status->transactions->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points + $new_points
            ]);
        } elseif ($request["type"] == 4) {
            $status = Status::where("type", 4)->first();
            // $data = [];
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,

            ];
            if (array_key_exists("message", $request)) {
                $data["message"] = $request["message"];
            }
        }
        return   OrderStatus::create($data);
    }

    public function withdrawChangeState($order_status, $request)
    {
        $data = [];
        if ($request["type"] == 1) {
            $status = Status::where("type", 1)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type
            ];
        } elseif ($request["type"] == 2) {
            $status = Status::where("type", 2)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,
                "before_operation" => $order_status->transactions->user->points,
                "after_operation" => $order_status->transactions->user->points - $order_status->transactions->value,
            ];
            $user_id =  $order_status->transactions->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points - $order_status->transactions->value
            ]);
            if (array_key_exists("admin_order", $request)) {

                $data["admin_order"] = $request["admin_order"];
            }
        } elseif ($request["type"] == 4) {
            $status = Status::where("type", 4)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,

            ];
            if (array_key_exists("message", $request)) {
                $data["message"] = $request["message"];
            }
        }
        return   OrderStatus::create($data);
    }
    public function changeTypeOrderStatus(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "order_status_id" => "required|exists:order_statuses,id",
            "type" => "required" // type of status change 
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $order_status = OrderStatus::find($request["order_status_id"]);
        if ($order_status->type == 0) {
            $new_order = $this->depositChangeState($order_status, $request);
            AdminLog::create([
                "target_id" => $order_status->order_id,
            ]);
        } elseif ($order_status->type == 1) {
            $new_order = $this->withdrawChangeState($order_status, $request);
            AdminLog::create([
                "target_id" => $order_status->order_id,
            ]);
        }
        $transaction = Transaction::find($order_status->order_id);
        $transaction->update([
            "last_order" => $order_status->id
        ]);
        return  $this->send_response(200, "تم تغير حالة الطلب", [], OrderStatus::with("transactions")->find($new_order->id));
    }


    public function getOrderStatusByTransactions()
    {
        // get order status for each transactions 
        if (isset($_GET["transaction_id"])) {
            $orders = OrderStatus::where("order_id", $_GET["transaction_id"]);
            if (!isset($_GET['skip']))
                $_GET['skip'] = 0;
            if (!isset($_GET['limit']))
                $_GET['limit'] = 10;
            $res = $this->paging($orders,  $_GET['skip'],  $_GET['limit']);
            return $this->send_response(200, 'تم جلب حالات الطلب بنجاح ', [], $res["model"], null, $res["count"]);
        }
    }
}