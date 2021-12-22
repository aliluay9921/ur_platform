<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Image;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use App\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    use SendResponse, Pagination, UploadImage;

    public function addCompany(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "name_ar" => "required",
            "name_en" => "required",
            "currncy_type" => "required",
            "image" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ بالمدخلات', $validator->errors(), []);
        }
        $company = Company::create([
            "name_ar" => $request["name_ar"],
            "name_en" => $request["name_en"],
            "currncy_type" => $request["currncy_type"],
        ]);
        Image::create([
            "target_id" => $company->id,
            "image" => $this->uploadPicture($request["image"], '/images/companies_image/')
        ]);

        return $this->send_response(200, "تم انشاء شركة جديدة بنجاح", [], Company::find($company->id));
    }
}