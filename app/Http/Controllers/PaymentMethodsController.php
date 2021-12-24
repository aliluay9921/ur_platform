<?php

namespace App\Http\Controllers;

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
        $payments = PaymentMethod::whereHas("join_relations", function ($q) {
            $q->whereHas("companies", function ($query) {
                $query->where("active", 1);
            });
        });
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
        return $this->send_response(200, 'تم جلب طرق الدفع بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addPaymentMethod(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "key" => "required",
            "order_key_type_id" => "required|exists:order_key_types,id",
            "tax" => "required",
            "company_id" => "required|exists:companies,id",
        ], [
            "key.required" => "يرجى ادخال مفتاح التحويل",
            "order_key_type_id.required" => "يرجى ادخال  نوع عملية التحويل",
            "tax.required" => "يرجى ادخال  قيمة الاستقطاع",
            "company_id.required" => "يرجى تحديد الشركة",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "key" => $request["key"],
            "order_key_type_id" => $request["order_key_type_id"],
            "tax" => $request["tax"],
        ];
        if (array_key_exists("note", $request)) {
            $data["note"] = $request["note"];
        }
        if (array_key_exists("point_value", $request)) {
            $data["point_value"] = $request["point_value"];
        }
        if (array_key_exists("barcode", $request)) {
            $data["barcode"] = $this->uploadPicture($request["barcode"], '/images/paymentBarcode/');
        }

        $payment = PaymentMethod::create($data);
        joinRelations::create([
            "payment_method_id" => $payment->id,
            "company_id" => $request["company_id"],
        ]);
        return $this->send_response(200, "تم اضافة طريقة دفع جديدة بنجاح", [], PaymentMethod::find($payment->id));
    }


    public function editPaymentMethod(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:payment_methods,id",
            "key" => "required",
            "order_key_type_id" => "required|exists:order_key_types,id",
            "tax" => "required",
            "company_id" => "required|exists:companies,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $data = [
            "key" => $request["key"],
            "order_key_type_id" => $request["order_key_type_id"],
            "tax" => $request["tax"],
        ];
        if (array_key_exists("note", $request)) {
            $data["note"] = $request["note"];
        }
        if (array_key_exists("point_value", $request)) {
            $data["point_value"] = $request["point_value"];
        }
        if (array_key_exists("barcode", $request)) {
            $data["barcode"] = $this->uploadPicture($request["barcode"], '/images/paymentBarcode/');
        }
        $payment = PaymentMethod::find($request["id"]);
        $payment->update($data);
        $relations = joinRelations::where("payment_method_id", $payment->id)->first();
        $relations->update([
            "company_id" => $request["company_id"]
        ]);
        return $this->send_response(200, "تم التعديل على طريقة الدفع", [], PaymentMethod::find($request["id"]));
    }
}
