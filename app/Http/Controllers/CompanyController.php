<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Company;
use App\Traits\Pagination;
use App\Traits\UploadImage;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    use SendResponse, Pagination, UploadImage;

    public function getCompanies()
    {
        $companies = Company::select("*");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            if ($filter->type == 0) {
                $companies->whereHas("join_relations", function ($query) use ($filter) {
                    $query->whereNotNull($filter->name); // filter in relations
                });
            } else {
                $companies->orwhere($filter->name, $filter->value); //filter in model without relations 
            }
        }
        if (isset($_GET['query'])) {
            $companies->where(function ($q) {
                $columns = Schema::getColumnListing('companies');
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
                    $companies->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($companies,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, trans("message.get.companies"), [], $res["model"], null, $res["count"]);
    }
    public function addCompany(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "name_ar" => "required",
            "name_en" => "required",
            "currncy_type" => "required",
            "images" => "required"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $company = Company::create([
            "name_ar" => $request["name_ar"],
            "name_en" => $request["name_en"],
            "currncy_type" => $request["currncy_type"],
        ]);
        if (array_key_exists("images", $request)) {
            foreach ($request["images"] as $image) {
                Image::create([
                    "target_id" => $company->id,
                    "image" => $this->uploadPicture($image, '/images/companies_image/')
                ]);
            }
        }

        return $this->send_response(200, "تم انشاء شركة جديدة بنجاح", [], Company::find($company->id));
    }

    public function editCompany(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:companies,id",
            "name_ar" => "required",
            "name_en" => "required",
            "currncy_type" => "required",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            "name_ar" => $request["name_ar"],
            "name_en" => $request["name_en"],
            "currncy_type" => $request["currncy_type"],
        ];

        Company::find($request["id"])->update($data);
        return $this->send_response(200, "تم التعديل على الشركة بنجاح", [], Company::find($request["id"]));
    }
    public function toggleActiveCompany(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:companies,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $company = Company::find($request["id"]);
        $company->update([
            "active" => !$company->active
        ]);
        return $this->send_response(200, "تم تغير حالة الشركة", [], Company::find($request["id"]));
    }

    public function deleteCompany(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:companies,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }

        $company = Company::find($request["id"]);
        $company->delete();
        return $this->send_response(200, "تم حذف الشركة بنجاح", [], []);
    }

    public function editImage(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "images_id.*" => 'exists:images,id',
            "target_id" => "exists:companies,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        if (array_key_exists("images_id", $request)) {
            foreach ($request["images_id"] as $image_id) {
                $image = Image::find($image_id);
                $image->delete();
            }
            return $this->send_response(200, "تم حذف الصور", [], []);
        } else {
            $images = [];
            foreach ($request["images"] as $image) {
                $image =  Image::create([
                    "target_id" => $request["target_id"],
                    "image" => $this->uploadPicture($image, '/images/companies_image/')
                ]);
                $images[] = $image;
            }
            return $this->send_response(200, "تم اضافة صور جديدة", [], $images);
        }
    }
}