<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Traits\Pagination;
use App\Models\OrderStatus;
use App\Models\Transaction;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\ChangeCurrncy;
use App\Models\joinRelations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    use SendResponse, Pagination;
    public function random_code()
    {
        $code1 = substr(str_shuffle("0123456789ABCD"), 0, 3);
        $code2 = substr(str_shuffle("0123456789ABCD"), 0, 3);
        $code3 = substr(str_shuffle("0123456789ABCD"), 0, 3);
        $code = $code1 . '-' . $code2 . '-' . $code3;
        $get = Transaction::where('operation_number', $code)->first();

        if ($get) {
            return $this->random_code();
        } else {
            return $code;
        }
    }
    public function getTransaction()
    {
        $transactions = Transaction::with("last_order");

        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($transactions,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب التحويلات بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addDeposit(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "target" => "required",
            "value" => "required",
            "payment_method_id" => "required|exists:payment_methods,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "target" => $request["target"],
            "value" => $request["value"],
            "operation_number" => $this->random_code(),
            "payment_method_id" => $request["payment_method_id"],
            "user_id" => auth()->user()->id,
            "type" => 0
        ];
        $deposit = Transaction::create($data);
        $status = Status::where("type", 0)->first();
        OrderStatus::create([
            "order_id" => $deposit->id,
            "status_id" => $status->id,
            "type" => 0
        ]);

        return $this->send_response(200, "طلب الايداع بأنتضار المراجعة", [], Transaction::find($deposit->id));
    }

    public function addWithdraw(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "payment_method_id" => "required|exists:payment_methods,id",
            "value" => "required", // points 
            "net_price" => "required", // net price 
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
            "type" => 1
        ];
        $user = auth()->user();
        if ($request["net_price"] > 10) {
            if (is_int($request["net_price"])) {
                if ($user->points >= $request["value"]) {
                    $relations = joinRelations::where("payment_method_id", $request["payment_method_id"])->first();
                    $currency =  ChangeCurrncy::where("currency", $relations->companies->currncy_type)->first();
                    $system_tax =  ($relations->payment_methods->tax / 100);
                    $company_tax = ($relations->payment_methods->company_tax  / 100);
                    $new_curreny_point = ceil($request["net_price"] / (1 - $system_tax - $company_tax));
                    $points = $new_curreny_point * $currency->points;
                    error_log($points . "" . $new_curreny_point);

                    if ($points == $request["value"]) {
                        $withdraw = Transaction::create($data);
                        $status = Status::where("type", 0)->first();
                        OrderStatus::create([
                            "order_id" => $withdraw->id,
                            "status_id" => $status->id,
                            "type" => 1
                        ]);
                        return $this->send_response(200, "طلب سحب الاموال بأنتضار المراجعة", [], Transaction::find($withdraw->id));
                    } else {
                        return $this->send_response(400, "لتصير لوتي براسي حبيبي", [], []);
                    }
                } else {
                    return $this->send_response(400, "يرجى سحب قيمة موجود في حسابك", [], []);
                }
            } else {
                return $this->send_response(400, "لايمكنك ادخال قيم عشرية", [], []);
            }
        } else {
            return $this->send_response(400, "يجب تحويل قيمة اكبر من $10", [], []);
        }
    }
}