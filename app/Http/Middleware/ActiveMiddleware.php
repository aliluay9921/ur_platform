<?php

namespace App\Http\Middleware;

use App\Traits\SendResponse;
use Closure;
use Illuminate\Http\Request;

class ActiveMiddleware
{
    use SendResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->active == true) {
            return $next($request);
        } else {
            return $this->send_response(401, 'يجب تأكيد البريد الالكتروني ليتم تفعيل الصفحة الخاصة بك', [], []);
        }
    }
}