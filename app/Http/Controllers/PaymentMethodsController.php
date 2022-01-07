<?php

namespace App\Http\Controllers;

use App\Events\paymentSocket;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\joinRelations;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class PaymentMethodsController extends Controller
{
    use SendResponse, Pagination, UploadImage;

    public function getPaymentMethods()
    {
        if (isset($_GET["company_id"])) {
            $payment = PaymentMethod::whereHas("join_relations", function ($q) {
                $q->where("company_id", $_GET["company_id"]);
            })->first();
            return $this->send_response(200, "تم جلب العنصر بنجاح", [], $payment);
        }
        if (auth()->user()->user_type == 1 || auth()->user()->user_type == 2) {
            $payments = PaymentMethod::whereHas("join_relations");
        } else {
            $payments = PaymentMethod::whereHas("join_relations", function ($q) {
                $q->whereHas("companies", function ($query) {
                    $query->where("active", 1);
                });
            })->where("active", 1);
        }

        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $payments->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $payments->where(function ($q) {
                $columns = Schema::getColumnListing('payment_methods');
                $q->whereHas("join_relations", function ($q) {
                    $q->whereHas("companies", function ($query) {
                        $query->Where('name_en', 'LIKE', '%' . $_GET['query'] . '%')->orWhere('name_ar', 'LIKE', '%' . $_GET['query'] . '%');
                    });
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
                    $payments->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($payments,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, trans("message.get.payment_methods"), [], $res["model"], null, $res["count"]);
    }
    public function addPaymentMethod(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "order_key_type_id" => "exists:order_key_types,id",
            "tax" => "required",
            "company_id" => "required|exists:companies,id",
            "company_tax" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "tax" => $request["tax"],
            "company_tax" => $request["company_tax"],
        ];
        if (array_key_exists("note", $request)) {
            $data["note"] = $request["note"];
        }
        if (array_key_exists("key", $request)) {
            $data["key"] = $request["key"];
        }
        if (array_key_exists("min_value", $request)) {
            $data["min_value"] = $request["min_value"];
        }
        if (array_key_exists("order_key_type_id", $request)) {
            $data["order_key_type_id"] = $request["order_key_type_id"];
        }
        if (array_key_exists("barcode", $request)) {
            $data["barcode"] = $this->uploadPicture($request["barcode"], '/images/paymentBarcode/');
        }

        $payment = PaymentMethod::create($data);
        joinRelations::create([
            "payment_method_id" => $payment->id,
            "company_id" => $request["company_id"],
        ]);
        broadcast(new paymentSocket(PaymentMethod::find($payment->id), "add"));
        return $this->send_response(200, "تم اضافة طريقة دفع جديدة بنجاح", [], PaymentMethod::find($payment->id));
    }
    public function editPaymentMethod(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:payment_methods,id",
            "order_key_type_id" => "exists:order_key_types,id",
            "tax" => "required",
            "company_id" => "required|exists:companies,id",
            "company_tax" => "required"

        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [
            "tax" => $request["tax"],
            "company_tax" => $request["company_tax"],
        ];
        if (array_key_exists("key", $request)) {
            $data["key"] = $request["key"];
        }
        if (array_key_exists("order_key_type_id", $request)) {
            $data["order_key_type_id"] = $request["order_key_type_id"];
        }
        if (array_key_exists("note", $request)) {
            $data["note"] = $request["note"];
        }
        if (array_key_exists("min_value", $request)) {
            $data["min_value"] = $request["min_value"];
        }

        if (array_key_exists("barcode", $request)) {
            if ($request["barcode"] == null) {
                $data["barcode"] = null;
            } else {
                $data["barcode"] = $this->uploadPicture($request["barcode"], '/images/paymentBarcode/');
            }
        }
        $payment = PaymentMethod::find($request["id"]);
        $payment->update($data);
        $relations = joinRelations::where("payment_method_id", $payment->id)->first();
        $relations->update([
            "company_id" => $request["company_id"]
        ]);
        broadcast(new paymentSocket(PaymentMethod::find($request["id"]), "edit"));

        return $this->send_response(200, "تم التعديل على طريقة الدفع", [], PaymentMethod::find($request["id"]));
    }
    public function deletePaymentMethod(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:payment_methods,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $payment = PaymentMethod::find($request["id"]);
        broadcast(new paymentSocket(PaymentMethod::find($request["id"]), "delete"));
        $payment->delete();
        return $this->send_response(200, "تم الحذف بنجاح", [], []);
    }
    public function toggleActivePaymentMethod(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:payment_methods,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $payment = PaymentMethod::find($request["id"]);
        $payment->update([
            "active" => !$payment->active
        ]);
        broadcast(new paymentSocket(PaymentMethod::find($request["id"]), "toogleActive"));

        return $this->send_response(200, "تم تغير حاله طريقة الدفع", [], PaymentMethod::find($request["id"]));
    }
}