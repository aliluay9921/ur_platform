<?php

namespace App\Http\Controllers;

use App\Models\ChangeCurrncy;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChangeCurrncyController extends Controller
{
    use SendResponse, Pagination;
    public function addCurrency(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "currency" => "required",
            "points" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
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
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $currncy =  ChangeCurrncy::find($request["id"]);
        $currncy->update([
            "currency" => $request["currency"],
            "points" => $request["points"],
        ]);
        return $this->send_response(200, "تم تعديل عملة", [], ChangeCurrncy::find($currncy->id));
    }
}