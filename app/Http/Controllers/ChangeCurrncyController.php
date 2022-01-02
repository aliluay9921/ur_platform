<?php

namespace App\Http\Controllers;

use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use App\Models\ChangeCurrncy;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ChangeCurrncyController extends Controller
{
    use SendResponse, Pagination;

    public function getCurrency()
    {
        $currencies = ChangeCurrncy::select("*");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $currencies->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $currencies->where(function ($q) {
                $columns = Schema::getColumnListing('change_currncies');
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
                    $currencies->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($currencies,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب  العملات بنجاح ', [], $res["model"], null, $res["count"]);
    }
    public function addCurrency(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "currency" => "required",
            "points" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }

        $currncy =  ChangeCurrncy::create([
            "currency" => $request["currency"],
            "points" => $request["points"],
        ]);
        return $this->send_response(200, "تم اضافة عملة", [], ChangeCurrncy::find($currncy->id));
    }

    public function editCurrency(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:change_currncies,id",
            "currency" => "required",
            "points" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $currncy =  ChangeCurrncy::find($request["id"]);
        $currncy->update([
            "currency" => $request["currency"],
            "points" => $request["points"],
        ]);
        return $this->send_response(200, "تم تعديل عملة", [], ChangeCurrncy::find($currncy->id));
    }

    public function deleteCurrency(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:change_currncies,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        ChangeCurrncy::find($request["id"])->delete();
        return $this->send_response(200, "تم حذف العملة بنجاح", [], []);
    }
}
