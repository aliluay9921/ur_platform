<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Card;
use App\Models\User;
use App\Models\Company;
use App\Models\AdminLog;
use App\Events\BoxSocket;
use App\Traits\Encryption;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\ChangeCurrncy;
use App\Models\joinRelations;
use App\Models\SerialKeyCard;
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
        });
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            if ($filter->name == "companies") {
                $cards->whereHas("join_relations", function ($q) use ($filter) {
                    $q->whereHas("companies", function ($query)  use ($filter) {
                        $query->where("id", $filter->value);
                    });
                });
            } else {
                $cards->where($filter->name, $filter->value);
            }
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
        return $this->send_response(200, trans("message.get.cards"), [], $res["model"], null, $res["count"]);
    }

    public function addCard(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "card_sale" => "required",
            "value" => "required",
            "card_buy" => "required",
            "company_id" => "required|exists:companies,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        // $company = Company::find($request["company_id"]);
        // $change_currency = ChangeCurrncy::where("currency", $company->currncy_type)->first();
        $data = [];
        $data = [
            "card_sale" => $request["card_sale"],
            "value" => $request["value"],
            "card_buy" => $request["card_buy"],
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
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $this->desEncrypt($request["serial"], "ali_luay");
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
            "card_buy" => "required",
            "company_id" => "exists:companies,id",

        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        // $company = Company::find($request["company_id"]);
        // $change_currency = ChangeCurrncy::where("currency", $company->currncy_type)->first();
        $data = [];
        $data = [
            "card_sale" => $request["card_sale"],
            "value" => $request["value"],
            "card_buy" => $request["card_buy"]
        ];
        $card = Card::find($request['id']);
        $card->update($data);
        $relations = joinRelations::where("card_id", $request["id"])->first();
        if (array_key_exists("company_id", $request)) {
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
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $card = Card::with("serial_keys")->find($request["card_id"]);

        $user = User::find(auth()->user()->id);
        // return $card;
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
                // الية تقسيم الارباح من عملية شراء الكارتات
                $company_currency = $card->join_relations[0]->companies->currncy_type;
                $change_currency = ChangeCurrncy::where("currency", $company_currency)->first();
                $card_buy = $card->points / $change_currency->points;
                $profit = $card_buy - $card->card_sale;

                $box = Box::first();
                $box->update([
                    "total_value" => $box->total_value + $profit,
                    "company_ratio" => $box->company_ratio + $profit * 0.1,
                    "programmer_ratio" => $box->programmer_ratio + $profit * 0.3,
                    "managment_ratio" => $box->managment_ratio + $profit * 0.6
                ]);
                broadcast(new BoxSocket($box));
            } else {
                return $this->send_response(400, trans("message.empty.cards"), [], []);
            }
            return $this->send_response(200, trans("message.buy.cards"), [], SerialKeyCard::find($get_serial->id));
        } else {
            return $this->send_response(400, trans("message.enough.balance"), [], []);
        }
    }

    public function deleteCard(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:cards,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $card = Card::find($request["id"]);
        $card->delete();
        return $this->send_response(200, "تم حذف البطاقة بنجاح", [], []);
    }

    public function getLogUserCard()
    {
        $logs = SerialKeyCard::with("card", "user")->where("user_id", auth()->user()->id);
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $logs->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $logs->where(function ($q) {
                $q->whereHas("card", function ($q) {
                    $q->whereHas("join_relations", function ($q) {
                        $q->whereHas("companies", function ($query) {
                            $query->Where('name_en', 'LIKE', '%' . $_GET['query'] . '%')->orWhere('name_ar', 'LIKE', '%' . $_GET['query'] . '%');
                        });
                    });
                });
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $logs->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($logs->orderBy("created_at", "DESC"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, trans("message.get.logs.card"), [], $res["model"], null, $res["count"]);
    }
}