<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Deposit;
use App\Models\Withdraw;
use App\Traits\Pagination;
use App\Models\OrderStatus;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WithdrawController extends Controller
{
    use SendResponse, Pagination;
    public function random_code()
    {
        $code1 = substr(str_shuffle("0123456789ABCD"), 0, 3);
        $code2 = substr(str_shuffle("0123456789ABCD"), 0, 3);
        $code3 = substr(str_shuffle("0123456789ABCD"), 0, 3);
        $code = $code1 . '-' . $code2 . '-' . $code3;
        $get = Deposit::where('operation_number', $code)->first();
        $get_ = Withdraw::where('operation_number', $code)->first();

        if ($get) {
            return $this->random_code();
        } else if ($get_) {
            return $this->random_code();
        } else {
            return $code;
        }
    }
    public function addWithdraw(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "payment_method_id" => "required|exists:payment_methods,id",
            "value" => "required",
            "target" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "payment_method_id" => $request["payment_method_id"],
            "value" => $request["value"],
            "target" => $request["target"],
            "user_id" => auth()->user()->id,
            "operation_number" => $this->random_code(),
        ];

        $withdraw = Withdraw::create($data);
        $status = Status::where("type", 0)->first();
        OrderStatus::create([
            "order_id" => $withdraw->id,
            "status_id" => $status->id,
            "type" => 1
        ]);
        return $this->send_response(200, "طلب سحب الاموال بأنتضار المراجعة", [], Withdraw::find($withdraw->id));
    }
}