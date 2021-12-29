<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Card;
use App\Traits\Encryption;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\joinRelations;
use App\Models\SerialKeyCard;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CardController extends Controller
{
    use SendResponse, Pagination, UploadImage, Encryption;

    public function getCards()
    {
        $cards = Card::whereHas("join_relations", function ($q) {
            $q->whereHas("companies", function ($query) {
                $query->where("active", 1);
            });
        })->WhereHas("serial_keys", function ($q) {
            $q->where("used", false);
        });
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $cards->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $cards->where(function ($q) {
                $columns = Schema::getColumnListing('cards');
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
                    $cards->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($cards,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الكروت بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function addCard(Request $request)
    {

        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "card_sale" => "required",
            "value" => "required",
            "points" => "required",

            "company_id" => "required|exists:companies,id",
        ], [
            "card_sale.required" => "يرجى ادخال سعر الشراء",
            "value.required" => "يرجى ادخال سعر البيع",
            "points.required" => "يرجى ادخال قيمة النقاط ",
            "company_id.required" => "يرجى تحديد الشركة",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "card_sale" => $request["card_sale"],
            "value" => $request["value"],
            "points" => $request["points"],
        ];
        $card = Card::create($data);
        joinRelations::create([
            "card_id" => $card->id,
            "company_id" => $request["company_id"]
        ]);

        return $this->send_response(200, "تم انشاء بطاقة بنجاح", [], Card::find($card->id));
    }
    public function addSerialCard(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "card_id" => "required|exists:cards,id",
            "serial" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $serial = SerialKeyCard::create([
            "card_id" => $request["card_id"],
            "serial" => $this->desEncrypt($request["serial"], "ali_luay")
        ]);
        return $this->send_response(200, 'تم اضافة رقم تعريفي للبطاقة', [], SerialKeyCard::with("card")->find($serial));
    }
    public function editCard(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:cards,id",
            "card_sale" => "required",
            "value" => "required",
            "points" => "required",
            "company_id" => "exists:companies,id",

        ], [
            "card_sale.required" => "يرجى ادخال سعر الشراء",
            "value.required" => "يرجى ادخال سعر البيع",
            "points.required" => "يرجى ادخال قيمة النقاط ",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $data = [];
        $data = [
            "card_sale" => $request["card_sale"],
            "value" => $request["value"],
            "points" => $request["points"],
        ];
        $card = Card::find($request['id']);
        $card->update($data);
        if (array_key_exists("company_id", $request)) {
            $relations = joinRelations::where("card_id", $request["id"])->first();
            $relations->update([
                "company_id" => $request["company_id"]
            ]);
        }

        return $this->send_response(200, "تم تحديث الكارد بنجاح", [], Card::find($request["id"]));
    }

    public function buyCard(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "card_id" => "required|exists:cards,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $card = Card::with("serial_keys")->find($request["card_id"]);
        $user = User::find(auth()->user()->id);
        if ($user->points >= $card->points) {
            $get_serial = SerialKeyCard::where("card_id", $request["card_id"])->whereNull("user_id")->where("used", false)->first();
            if ($get_serial) {
                $get_serial->update([
                    "used" => true,
                    "user_id" => $user->id
                ]);
                $user->update([
                    "points" => $user->points - $card->points
                ]);
                AdminLog::create([
                    "target_id" => $card->id
                ]);
            } else {
                return $this->send_response(200, "عذراً لايوجد بطاقات متوفرة حالياً", [], []);
            }
            return $this->send_response(200, "تم شراء الكارت بنجاح", [], SerialKeyCard::find($get_serial->id));
        } else {
            return $this->send_response(200, "لا تمتلك رصيد كافي لشراء هذه البطاقة", [], []);
        }
    }
}