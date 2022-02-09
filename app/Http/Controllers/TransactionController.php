<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\User;
use App\Models\Status;
use App\Models\AdminLog;
use App\Events\BoxSocket;
use App\Traits\Pagination;
use App\Models\OrderStatus;
use App\Models\Transaction;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\ChangeCurrncy;
use App\Models\joinRelations;
use App\Models\Notifications;
use App\Models\PaymentMethod;
use App\Events\notificationSocket;
use App\Events\transactionsSocket;
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
    public function getTransactions()
    {
        if (auth()->user()->user_type == 2 || auth()->user()->user_type == 1) {
            $transactions = Transaction::with("last_status");
        } else {
            $transactions = Transaction::with("last_status")->where("user_id", auth()->user()->id);
        }
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            // return $filter;
            if ($filter->name == "status") {
                error_log("here");
                $transactions->whereHas("last_status", function ($q) use ($filter) {
                    $q->whereHas("status", function ($query)  use ($filter) {
                        $query->where("type", $filter->value);
                    });
                });
            } else {
                $transactions->where($filter->name, $filter->value);
            }
        }
        if (isset($_GET['query'])) {
            $transactions->where(function ($q) {
                $columns = Schema::getColumnListing('transactions');
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
                    // return $sort;
                    if ($key == "status") {
                        $transactions->join('order_statuses', 'transactions.last_order', '=', 'order_statuses.id')->select("transactions.*");
                        $transactions->join('statuses', 'order_statuses.status_id', '=', 'statuses.id');
                        $transactions->orderBy('statuses.type', $sort);
                    } else {
                        $transactions->orderBy($key,  $sort);
                    }
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($transactions->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, trans("message.get.transactions"), [], $res["model"], null, $res["count"]);
    }

    public function addDeposit(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "target" => "required",
            "value" => "required",
            "net_price" => "required",
            "payment_method_id" => "required|exists:payment_methods,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "target" => $request["target"],
            "value" => $request["value"], //points
            "operation_number" => $this->random_code(),
            "payment_method_id" => $request["payment_method_id"],
            "user_id" => auth()->user()->id,
            "type" => 0,
            "net_price" => $request["net_price"]

        ];
        $payments = PaymentMethod::find($request["payment_method_id"]);
        if ($request["net_price"] >= $payments->min_value) {
            $deposit = Transaction::create($data);
            $status = Status::where("type", 0)->first();
            $order =   OrderStatus::create([
                "order_id" => $deposit->id,
                "status_id" => $status->id,
                "type" => 0
            ]);
            $deposit->update([
                "last_order" => $order->id
            ]);
            $admin_log =  AdminLog::create([
                "target_id" => $deposit->id
            ]);
            $admins = User::whereIn("user_type", [1, 2])->get();
            foreach ($admins as $admin) {
                broadcast(new transactionsSocket(AdminLog::with("transactions", "transactions.last_status")->find($admin_log->id), $admin));
            }
        } else {
            return $this->send_response(400, trans("message.limit.transactions") . ' $' . $payments->min_value, [], []);
        }
        return $this->send_response(200, trans("message.add.deposit"), [], Transaction::find($deposit->id));
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
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "payment_method_id" => $request["payment_method_id"],
            "value" => $request["value"],
            "target" => $request["target"],
            "user_id" => auth()->user()->id,
            "operation_number" => $this->random_code(),
            "type" => 1,
            "net_price" => $request["net_price"]
        ];
        $user = auth()->user();
        $payments = PaymentMethod::find($request["payment_method_id"]);
        if ($request["net_price"] >= $payments->min_value) {
            if (is_int($request["net_price"])) {
                if ($user->points >= $request["value"]) {
                    $relations = joinRelations::where("payment_method_id", $request["payment_method_id"])->first();
                    $currency =  ChangeCurrncy::where("currency", $relations->companies->currency_type)->first();
                    $system_tax =  ($relations->payment_methods->tax / 100);
                    $company_tax = ($relations->payment_methods->company_tax  / 100);
                    $new_currecny_point = ceil($request["net_price"] / (1 - $system_tax - $company_tax));
                    $profit = ($request["net_price"]) *  $system_tax;
                    if ($currency->currency != "dollar") {
                        $dollar = ChangeCurrncy::where("currency", "dollar")->first();
                        $translate_currency = $profit * $currency->points;
                        $profit = $translate_currency / $dollar->points;
                    }
                    $box = Box::first();
                    $box->update([
                        "total_value" => $box->total_value + $profit,
                        "company_ratio" => $box->company_ratio + $profit * 0.1,
                        "programmer_ratio" => $box->programmer_ratio + $profit * 0.3,
                        "managment_ratio" => $box->managment_ratio + $profit * 0.6
                    ]);
                    broadcast(new BoxSocket($box));
                    $points = $new_currecny_point * $currency->points;
                    error_log("" . $points);
                    if ($points == $request["value"]) {
                        if ($relations->companies->currency_type == "points") {
                            // transaction point on user to another
                            $to_user = User::where("user_name", $request["target"])->first();
                            $from_user = User::find($user->id);
                            // if ($from_user->points)
                            if ($to_user) {
                                $to_user->update([
                                    "points" => $to_user->points + $request["net_price"]
                                ]);
                            } else {
                                return $this->send_response(400, trans("message.error.withdraw.transactions.to.user"), [], []);
                            }

                            $transactions_points = Transaction::create($data);
                            AdminLog::create([
                                "target_id" => $transactions_points->id
                            ]);
                            $status = Status::where("type", 2)->first();
                            $order =  OrderStatus::create([
                                "order_id" => $transactions_points->id,
                                "status_id" => $status->id,
                                "type" => 1,
                                "after_operation" => $from_user->points - $request["value"],
                                "before_operation" => $from_user->points,
                            ]);
                            $from_user->update([
                                "points" => $from_user->points - $request["value"]
                            ]);
                            $transactions_points->update([
                                'last_order' => $order->id
                            ]);
                            $notify =  Notifications::create([
                                'title' => trans("message.received.points.to.user") . "  " . $transactions_points->user->user_name,
                                "target_id" => $order->id,
                                "to_user" =>  $to_user->id,
                                "from_user" => auth()->user()->id,
                                "type" => 3
                            ]);
                            // Broadcast(new transactionsSocket($transactions_points, $to_user));
                            Broadcast(new transactionsSocket($transactions_points, $from_user));
                            broadcast(new notificationSocket($notify, $to_user->id));
                            // الية الربح من عملية تحويل نقاط
                            $box = Box::first();
                            $dollar = ChangeCurrncy::where("currency", "dollar")->first();
                            $system__tax = $request["value"] * $payments->tax / 100;
                            $profit = $system__tax / $dollar->points;
                            $box->update([
                                "total_value" => $box->total_value + $profit,
                                "company_ratio" => $box->company_ratio + $profit * 0.1,
                                "programmer_ratio" => $box->programmer_ratio + $profit * 0.3,
                                "managment_ratio" => $box->managment_ratio + $profit * 0.6
                            ]);
                            broadcast(new BoxSocket($box));
                            return $this->send_response(200, trans("message.translate.points"), [], Transaction::find($transactions_points->id));
                        } else {
                            $withdraw = Transaction::create($data);
                            $status = Status::where("type", 0)->first();
                            $order =  OrderStatus::create([
                                "order_id" => $withdraw->id,
                                "status_id" => $status->id,
                                "type" => 1
                            ]);
                            $withdraw->update([
                                "last_order" => $order->id
                            ]);
                            $admin_log =   AdminLog::create([
                                "target_id" => $withdraw->id
                            ]);
                            $admins = User::whereIn("user_type", [1, 2])->get();
                            foreach ($admins as $admin) {
                                broadcast(new transactionsSocket(AdminLog::with("transactions", "transactions.last_status")->find($admin_log->id), $admin));
                            }
                            return $this->send_response(200, trans("message.withdraw.review"), [], Transaction::find($withdraw->id));
                        }
                    } else {
                        return $this->send_response(400, trans("message.withdraw.error"), [], []);
                    }
                } else {
                    return $this->send_response(400, trans("message.withdraw.check.points"), [], []);
                }
            } else {
                return $this->send_response(400, trans("message.withdraw.check.integer.points"), [], []);
            }
        } else {
            return $this->send_response(400, trans("message.limit.transactions") . ' $' . $payments->min_value, [], []);
        }
    }
}