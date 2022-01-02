<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Status;
use App\Models\Deposit;
use App\Models\Withdraw;
use App\Traits\Pagination;
use App\Models\OrderStatus;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\ChangeCurrncy;
use App\Models\joinRelations;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Validator;

class DepositController extends Controller
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
    public function addDeposit(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "order_key" => "required",
            "value" => "required",
            "payment_method_id" => "required|exists:payment_methods,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "order_key" => $request["order_key"],
            "value" => $request["value"],
            "operation_number" => $this->random_code(),
            "payment_method_id" => $request["payment_method_id"],
            "user_id" => auth()->user()->id
        ];
        $deposit = Deposit::create($data);
        $status = Status::where("type", 0)->first();
        OrderStatus::create([
            "order_id" => $deposit->id,
            "status_id" => $status->id,
            "type" => 0
        ]);
        // $company = joinRelations::where("payment_method_id", $request["payment_method_id"])->first();
        // $company_currency = $company->companies->currncy_type;
        // $currency = ChangeCurrncy::where("currency", $company_currency)->first();
        // $points = $currency->points * $request["value"];

        // $user = User::find(auth()->user()->id);
        // $user->update([
        //     "points" => $user->points + $points
        // ]);
        // return User::find(auth()->user()->id);
        return $this->send_response(200, "طلب الايداع بأنتضار المراجعة", [], Deposit::find($deposit->id));
    }
}
