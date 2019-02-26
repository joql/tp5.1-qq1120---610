<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/5
 * Time: 17:20
 */

namespace app\admin\middleware;

use auth\Auth;

class CheckAuth
{
    public function handle($request, \Closure $next)
    {
        $user = session('user');//获取当前登录信息
        $userid = session('userid');//获取当前登录信息
        $power = session('power');//获取当前登录信息

        if (!$userid) { //验证是否登录
            alert_error('登录后查看', url('/admin/login'));
        }

        $request->Login = $user;
        $request->Loginid = $userid;
        $request->Power = $power;
        return $next($request);
    }
}