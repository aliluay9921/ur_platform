<?php

namespace App\Http\Controllers;

use App\Events\notificationSocket;
use App\Models\User;
use App\Models\Status;
use App\Models\Company;
use App\Models\Deposit;
use App\Models\AdminLog;
use App\Traits\Pagination;
use App\Models\OrderStatus;
use App\Models\Transaction;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\ChangeCurrncy;
use App\Models\joinRelations;
use App\Models\Notifications;
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
            $notify =  Notifications::create([
                "title" => trans("message.notification.transactions.deposit.review"),
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            broadcast(new notificationSocket($notify, $order_status->transactions->user_id));
        } elseif ($request["type"] == 2) {
            $status = Status::where("type", 2)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,
                "before_operation" => $order_status->transactions->user->points,
                "after_operation" => $order_status->transactions->user->points + $order_status->transactions->value,
            ];
            $notify =  Notifications::create([
                "title" => trans("message.notification.transactions.deposit.accept"),
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            $user_id =  $order_status->transactions->user_id;
            broadcast(new notificationSocket($notify, $user_id));
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
            $notify =   Notifications::create([
                "title" => trans("message.notification.transactions.deposit.accept"),
                "body" => $request["message"],
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            broadcast(new notificationSocket($notify, $order_status->transactions->user_id));
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
            $notify = Notifications::create([
                "title" => trans("message.notification.transactions.deposit.reject"),
                "body" => $request["message"],
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            broadcast(new notificationSocket($notify, $order_status->transactions->user_id));
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
            $notify =   Notifications::create([
                "title" => trans("message.notification.transactions.withdraw.review"),
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            broadcast(new notificationSocket($notify, $order_status->transactions->user_id));
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
            $notify = Notifications::create([
                "title" => trans("message.notification.transactions.withdraw.accept"),
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            broadcast(new notificationSocket($notify, $order_status->transactions->user_id));
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
            $notify =  Notifications::create([
                "title" => trans("message.notification.transactions.withdraw.reject"),
                "body" => $request["message"],
                "target_id" => $order_status->order_id,
                "to_user" =>  $order_status->transactions->user_id,
                "from_user" => auth()->user()->id
            ]);
            broadcast(new notificationSocket($notify, $order_status->transactions->user_id));
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
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $order_status = OrderStatus::find($request["order_status_id"]);
        // return $order_status;
        if ($order_status->type == 0) {
            $new_order = $this->depositChangeState($order_status, $request);
        } elseif ($order_status->type == 1) {
            $new_order = $this->withdrawChangeState($order_status, $request);
        }
        $transaction = Transaction::find($order_status->order_id);
        $transaction->update([
            "last_order" => $new_order->id
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
            return $this->send_response(200, trans("message.get.order_status"), [], $res["model"], null, $res["count"]);
        }
    }
}