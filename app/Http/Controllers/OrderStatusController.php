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
                "before_operation" => $order_status->deposit->user->points,
                "after_operation" => $order_status->deposit->user->points + $order_status->deposit->value,
            ];
            $user_id =  $order_status->deposit->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points + $order_status->deposit->value
            ]);
        } elseif ($request["type"] == 3) {
            $status = Status::where("type", 3)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,
                "before_operation" => $order_status->deposit->user->points,
                "after_operation" => $order_status->deposit->user->points + $request["value"],
            ];
            if (array_key_exists("message", $request)) {
                $data["message"] = $request["message"];
            }
            if (array_key_exists("value", $request)) {
                $order_status->deposit->update([
                    "value" => $request["value"]
                ]);
            }
            $user_id =  $order_status->deposit->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points + $request["value"]
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
                "before_operation" => $order_status->withdraw->user->points,
                "after_operation" => $order_status->withdraw->user->points - $order_status->withdraw->value,
            ];
            $relations = joinRelations::where("payment_method_id", $order_status->withdraw->payment_method_id)->first();
            $companies = Company::find($relations->company_id);
            $currency =  ChangeCurrncy::where("currency", $companies->currncy_type)->first();
            // return $order_status;
            $currency_change = $order_status->withdraw->value / $currency->points;
            $tax_value = $order_status->withdraw->payment_method->tax * 1 / 100;
            $tax_company_value = $order_status->withdraw->payment_method->tax * 1 / 100;
            $taxes = ($currency_change * $tax_value) + ($currency_change * $tax_company_value);
            return $currency_change * $tax_value;

            $user_id =  $order_status->withdraw->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points - $order_status->withdraw->value
            ]);
        } elseif ($request["type"] == 3) {
            $status = Status::where("type", 3)->first();
            $data = [
                "order_id" => $order_status->order_id,
                "status_id" => $status->id,
                "type" => $order_status->type,
                "before_operation" => $order_status->withdraw->user->points,
                "after_operation" => $order_status->withdraw->user->points - $request["value"],
            ];
            if (array_key_exists("message", $request)) {
                $data["message"] = $request["message"];
            }
            if (array_key_exists("value", $request)) {
                $order_status->withdraw->update([
                    "value" => $request["value"]
                ]);
            }
            $user_id =  $order_status->withdraw->user_id;
            $user = User::find($user_id);
            $user->update([
                "points" => $user->points - $request["value"]
            ]);
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
            return $new_order;
            // AdminLog::create([
            //     "target_id" => $order_status->order_id,
            // ]);
        }
        return  $this->send_response(200, "تم تغير حالة الطلب", [], OrderStatus::find($new_order->id));
    }
}