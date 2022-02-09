<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Country;
use App\Models\UserCode;
use App\Mail\ActivationMail;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use SendResponse, Pagination;


    public function getTimeZone()
    {
        // return Carbon::now()->timezone('GMT+3');
    }

    public function random_code()
    {
        $code = substr(str_shuffle("0123456789ABCD"), 0, 6);
        $get = UserCode::where('code', $code)->first();
        if ($get) {
            return $this->random_code();
        } else {
            return $code;
        }
    }


    public function login(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'password' => 'required',
            "user_name" => 'required',
        ], [
            'user_name.required' => ' يرجى ادخال اسم المستخدم ',
            'password.required' => 'يرجى ادخال كلمة المرور ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $fieldType = filter_var($request['user_name'], FILTER_VALIDATE_EMAIL) ? 'email' : 'user_name';

        if (auth()->attempt(array($fieldType => $request['user_name'], 'password' => $request['password']))) {
            // $user = Auth::user();
            $user = auth()->user();
            $token = $user->createToken('ur_platform')->accessToken;
            return $this->send_response(200, 'تم تسجيل الدخول بنجاح', [], $user, $token);
        } else {
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', null, null, null);
        }
    }

    public function register(Request $request)
    {

        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'first_name' => 'required|min:2|max:15',
            'last_name' => 'min:2|max:15',
            'user_name' => 'required|unique:users,user_name|min:2|max:15',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'min:8',
                'same:confirm_password',             // must be at least 10 characters in length
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                // 'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&_]/', // must contain a special character
            ],
            'confirm_password'  => [
                'required',
                // 'min:8',
                // 'regex:/[a-z]/',      // must contain at least one lowercase letter
                // 'regex:/[A-Z]/',      // must contain at least one uppercase letter
                // 'regex:/[0-9]/',      // must contain at least one digit
                // 'regex:/[@$!%*#?&]/', // must contain a special character
            ],

        ], [
            'first_name.required' => 'يرجى ادخال الاسم الاؤل ',
            'user_name.required' => ' يرجى ادخال اسم المستخدم ',
            'user_name.unique' => ' اسم المستخدم مستخدم سابقاً ',
            'email.required' => ' يرجى ادخال البريدالالكتروني ',
            'email.email' => ' يرجى ادخال بريد الكتروني صالح  ',
            'email.unique' => ' البريد الالكتروني مستخدم سابقاً ',
            'password.required' => 'يرجى ادخال كلمة المرور ',
            'password.min' => ' كلمة المرور يجب انت تكون على الاقل 8 عناصر',
            'password.regex' => '  كلمة المرور ضعيفة يجب ان تحتوي على احرف صغيرة واحرف كبيرة وارقام  ',
            'password.same' => 'كلمتا المرور غير متطابقة',
            'confirm_password.required' => 'يرجى ملئ هذا الحقل للتأكيد ',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $data = [];
        $data = [
            'first_name' => $request['first_name'],
            'user_name' => $request['user_name'],
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
            'user_type' => 0,
        ];
        if (array_key_exists("phone_number", $request)) {
            $data["phone_number"] = $request["phone_number"];
        }
        if (array_key_exists("country_code", $request)) {
            $data["country_code"] = $request["country_code"];
        }
        if (array_key_exists("last_name", $request)) {
            $data["last_name"] = $request["last_name"];
        }
        if (array_key_exists("language", $request)) {
            $data["language"] = $request["language"];
        }
        if (array_key_exists("country_id", $request)) {
            $data["country_id"] = $request["country_id"];
        }

        $user = User::create($data);
        $code = UserCode::create([
            "user_id" => $user->id,
            "code" => $this->random_code(),
            "type" => 1
        ]);
        $token = $user->createToken($user->user_name)->accessToken;
        $details = [
            'title' => 'رمز التفعيل الخاص بك في منصة اؤر الالكترونية',
            'body' =>  $code->code
        ];

        \Mail::to($user->email)->send(new ActivationMail($details));
        return $this->send_response(200, 'تم تسجيل الدخول بنجاح', [], User::find($user->id), $token);
    }

    public function activationAccount(Request $request)
    {

        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'code' => 'required',
        ], [
            'code.required' => 'يرجى ادخال رمز التفعيل الخاص بك'
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $user = auth()->user();
        if ($user->active == true) {
            return $this->send_response(400, "لقد تم تفعيل حسابك سابقاً", [], []);
        }
        $code = UserCode::where("type", 1)->where("user_id", $user->id)->where("code", $request["code"])->first();

        if (!$code) {
            return $this->send_response(400, "يرجى ادخال رمز تفعيل صالح للأستخدام", [], []);
        } else {
            User::find($user->id)->update([
                "active" => true
            ]);
            UserCode::where("type", 1)->where("user_id", $user->id)->where("code", $request["code"])->delete();
        }
        return $this->send_response(200, "تم تفعيل حسابك بنجاح يمكن الأن استخدام منصة اؤر الالكترونية", [], User::find($user->id));
    }
    public function infoAuth()
    {
        return $this->send_response(200, "تم جلب معلوماتك الخاصة", [], User::find(auth()->user()->id));
    }

    public function forgetPassword(Request $request)
    {
        $request = $request->json()->all();
        if (array_key_exists('email', $request) && !array_key_exists("code", $request)) {

            $validator = Validator::make($request, [
                'email' => 'required|exists:users,email',
            ], [
                'email.required' => 'يرجى ادخال البريد الالكتروني الخاص بك',
                'email.exists' => 'البريد الالكتروني الذي قمت بأدخاله غير مرتبط بحساب'
            ]);
            if ($validator->fails()) {
                return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
            }
            $user = User::where('email', $request['email'])->first();
            $code = UserCode::create([
                "user_id" => $user->id,
                "code" =>  $this->random_code(),
                "type" => 0
            ]);

            $details = [
                'title' => 'رمز اعادة تعيين كلمة المرور الخاص بك هو',
                'body' => $code->code
            ];

            \Mail::to($user->email)->send(new ActivationMail($details));

            return $this->send_response(200, "تم ارسال رمز تعيين كلمة مرور جديدة ", []);
        }
        if (array_key_exists('code', $request) && array_key_exists("email", $request) && array_key_exists("password", $request) && array_key_exists("confirm_password", $request)) {

            $validator = Validator::make($request, [
                'code' => 'required|min:6|max:6|exists:user_codes,code',
                'password' => [
                    'required',
                    'min:8',
                    'same:confirm_password',             // must be at least 10 characters in length
                    'regex:/[a-z]/',      // must contain at least one lowercase letter
                    'regex:/[0-9]/',      // must contain at least one digit
                    // 'regex:/[@$!%*#?&]/', // must contain a special character
                ],
                'confirm_password'  => [
                    'required',
                ],
                // 'logout' => 'required|boolean',
            ], [
                'code.required' => 'يرجى ادخال الرمز الخاص لأعادة تعيين كلمة المرور',
                'code.exists' => 'يجب ادخال كود تغير كلمة مرور صالح',
                'confirm_password.required' => 'يرجى ملئ هذا الحقل للتأكيد ',

                'password.required' => 'يرجى ادخال كلمة المرور ',
                'password.min' => ' كلمة المرور يجب انت تكون على الاقل 8 عناصر',
                'password.regex' => '  كلمة المرور ضعيفة يجب ان تحتوي على احرف صغيرة واحرف كبيرة وارقام  ',
                'password.same' => 'كلمتا المرور غير متطابقة',
            ]);
            if ($validator->fails()) {
                return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
            }
            $user = User::where("email", $request["email"])->first();
            $user->update([
                "password" => bcrypt($request["password"])
            ]);
            $user_codes = UserCode::where("user_id", $user->id)->get();
            foreach ($user_codes as $user_code) {
                $user_code->delete();
            }
            return $this->send_response(200, "تم تغيير كلمة المرور بنجاح ", [], []);
        }
    }

    public function updateAuthUser(Request $request)
    {
        $request = $request->json()->all();
        $user_id = auth()->user()->id;
        $validator = Validator::make($request, [
            'user_name' => 'required|unique:users,user_name,' . $user_id,
            'first_name' => 'required',

        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        $info = [];
        $info = [
            "user_name" => $request["user_name"],
            "first_name" => $request["first_name"],
        ];
        if (array_key_exists("phone_number", $request)) {
            $info["phone_number"] = $request["phone_number"];
        }
        if (array_key_exists("country_code", $request)) {
            $info["country_code"] = $request["country_code"];
        }
        if (array_key_exists("last_name", $request)) {
            $info["last_name"] = $request["last_name"];
        }
        if (array_key_exists("language", $request)) {
            $info["language"] = $request["language"];
        }
        if (array_key_exists("country_id", $request)) {
            $info["country_id"] = $request["country_id"];
        }
        User::findOrFail($user_id)->update($info);

        return $this->send_response(200, 'تم التحديث بنجاح', [], User::find($user_id));
    }

    public function toggleRestrictUser(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "user_id" => "required|exists:users,id"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }

        $user = User::find($request["user_id"]);
        $user->update([
            "restrict" => !$user->restrict
        ]);
        return $this->send_response(200, "تم تغير حالة المستخدم", [], User::find($request["user_id"]));
    }

    public function getCountries()
    {
        $countries = Country::select("*");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $countries->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $countries->where(function ($q) {
                $columns = Schema::getColumnListing('countries');
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
                    $countries->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($countries,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب الدول بنجاح ', [], $res["model"], null, $res["count"]);
    }

    public function changeEmail(Request $request)
    {
        $request = $request->json()->all();
        if (array_key_exists("email", $request) && !array_key_exists("code", $request)) {
            $validator = Validator::make($request, [
                "email" => "required|email|unique:users,email"
            ]);
            if ($validator->fails()) {
                return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
            }
            $code =  UserCode::create([
                "user_id" => auth()->user()->id,
                "email" => $request["email"],
                "code" => $this->random_code(),
                "type" => 3
            ]);
            $details = [
                'title' => 'هو رمز التحقق من عملية تغير البريد الالكتروني الخاص بك في منصة اؤر الالكترونية',
                'body' =>  $code->code
            ];

            \Mail::to($request["email"])->send(new ActivationMail($details));
            $details_old = [
                'title' => 'انتباه',
                'body' => 'سوف يتم تغير البريد الالكتروني الخاص بك في منصة اؤر الالكترونية'
            ];

            \Mail::to(auth()->user()->email)->send(new ActivationMail($details_old));
            return $this->send_response(200, 'تم ارسال الرمز لتغير البريد الالكتروني', [], []);
        } else if (array_key_exists("email", $request) && array_key_exists("code", $request)) {
            $validator = Validator::make($request, [
                "email" => "required|email|unique:users,email",
                "code" => "required|exists:user_codes,code"
            ]);
            if ($validator->fails()) {
                return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
            }
            $info = UserCode::where("user_id", auth()->user()->id)->where("email", $request["email"])->where("code", $request["code"])->where("type", 3)->first();
            if ($info) {
                User::find(auth()->user()->id)->update([
                    "email" => $info->email,
                ]);
                $info->delete();
                return $this->send_response(200, "تم تغيرر البريد الالكتروني بنجاح", [], User::find(auth()->user()->id));
            } else {
                return $this->send_response(400, "يرجى التأكد من المعلومات جيداً", [], []);
            }
        }
    }

    public function checkUserName(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "user_name" => "required|unique:users,user_name"
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, trans("message.error.key"), $validator->errors(), []);
        }
        return $this->send_response(200, "اسم المستخدم صالح للأستخدام", [], []);
    }
}