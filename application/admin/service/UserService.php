<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin\service;

use app\admin\model\Adduser;
use app\admin\model\Kai;
use app\admin\model\Moneylog;
use app\admin\model\PassLog;
use app\admin\model\Timelog;
use app\admin\model\User;
use app\admin\model\LoginLog;
use app\admin\model\AuthGroupAccess;
use think\db\Where;
use think\facade\Request;
use app\admin\traits\Result;
use think\Validate;

class UserService
{
    use Result;

    /**
     * 验证登录
     * @param $data  待验证数据
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function login($data)
    {
        $validate = validate('User');
        if (!$validate->check($data)) {
            return Result::error($validate->getError());
        }
        $list = User::where([
            'username' => $data['user'],
            'status' => 1,
            ])->where('power','in','0,1')->find();
        if (empty($list)) {
            return Result::error('登陆失败');
        }
        if (md5(sha1($data['password'])) !== $list['password']) {
            $msg = Result::error('密码错误');
        } else {
            self::autoSession($list);
            $msg = Result::success('登录成功', url('/admin/index'));
        }
        return $msg;
    }

    /**
     * 记录session
     * @param $uid 用户id
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    private static function autoSession($u)
    {
        session('user', $u);
        session('userid', $u['id']);
        //设置session签名
        session('power', $u['power']);
    }

    /**
     * 记录登录日志
     * @param $data
     * @author 原点 <467490186@qq.com>
     */
    private static function log($data)
    {
        //添加数据
        $LoginLog = new LoginLog;
        $LoginLog->save([
            'uid' => $data['uid'],
            'user' => $data['user'],
            'name' => $data['name'],
            'last_login_ip' => $data['last_login_ip'],
        ]);
    }

    /**
     * 修改密码
     * @param $uid     用户id
     * @param $oldpsd  原密码
     * @param $newpsd  新密码
     * @return array
     * @author 原点 <467490186@qq.com>
     * @throws \think\Exception\DbException
     */
    public static function editPassword($uid, $oldpsd, $newpsd)
    {
        $list = User::get($uid);
        if (!password_verify($oldpsd, $list['password'])) {
            $msg = Result::error('原密码错误');
            return $msg;
        }
        $list->password = password_hash($newpsd, PASSWORD_DEFAULT);
        $list->updatapassword = 1;
        if ($list->save()) {
            //清除当前登录信息
            session('user_auth', null);
            session('user_auth_sign', null);
            $msg = Result::success('重置成功,请重新登录', url('/admin/login'));
        } else {
            $msg = Result::error('修改失败');
        }
        return $msg;
    }

    /**
     * 添加用户
     * @param $data
     * @return array
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function add($data, $uid)
    {
        //验证数据合法性
        $validate = Validate::make([
            'name'  => 'require',
            'password' => 'require',
            'password_confirm' => 'require',
            'wx' => 'require',
            'contact' => 'require',
            'user_type' => 'require',
        ]);
        if (!$validate->check($data)) {
            $msg = Result::error($validate->getError(), null, ['token' => Request::token()]);
            return $msg;
        }
        if ($data['password'] !== $data['password_confirm']) {
            $msg = Result::error('两次密码不一致', null, ['token' => Request::token()]);
            return $msg;
        }

        $user = new User;
        $money = User::where('id','=',$uid)->value('money');
        if($money < 20){
            $msg = Result::error('点数不足', null, ['token' => Request::token()]);
            return $msg;
        }
        $user->parentid = $uid;
        $user->username = $data['name'];
        $user->password =  md5(sha1($data['password']));
        $user->power =  '1';
        $user->status =  '1';
        $user->phone =  $data['contact'];
        $user->weichat =  $data['wx'];
        $user->share_ma =  rand('100000','999999');
        $user->ctime =  time();
        $user->logintime =  '0';
        $user->lasttime =  '0';
        $user->money =  '0.00';
        $sha_count = User::where('share_ma','=',$user->share_ma)->count();
        if($sha_count>0){
            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
            return $msg;
        }
        $count = User::where('username','=',$user->username)->count();
        if($count>0){
            $msg = Result::error('用户名已存在', null, ['token' => Request::token()]);
            return $msg;
        }
        $res = $user->save();
        if ($res) {
            User::where('id','=',$uid)->setDec('money',20);
            $moneylog = new Moneylog();
            $moneylog->uid = $uid;
            $moneylog->ctime = time();
            $moneylog->cid = $user->id;
            $moneylog->money = '20';
            $moneylog->save();

            User::update(['money'=>20],['id'=>$moneylog->cid]);
            $kai = new Kai();
            $kai->uid = $uid;
            $kai->cid = $moneylog->cid;
            $kai->ctime = time();
            $kai->save();
            $msg = Result::success('添加成功', url('/admin/platagent'));
        }else{
            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
        }
        return $msg;

    }

    /**
     * 编辑用户
     * @param $data
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function edit($data, $uid)
    {
        //验证数据合法性
        $validate = Validate::make([
            'id'  => 'require',
        ]);
        if (!$validate->check($data)) {
            $msg = Result::error($validate->getError(), null, ['token' => Request::token()]);
            return $msg;
        }
        if($data['money']){
            $agent = User::where('id', $uid)->find();
            if($agent->money < $data['money']){
                $msg = Result::error('点数不足', null, ['token' => Request::token()]);
                return $msg;
            }
            $member = User::where('id', $data['id'])->find();
            $res = $member->setInc('money', $data['money']);
            if(!$res){
                $msg = Result::error('充值失败', null, ['token' => Request::token()]);
                return $msg;
            }
            $agent->setDec('money', $data['money']);
            $member->parentid = $uid;
            $member->save();
            $log = new Moneylog();
            $log->uid = $uid;
            $log->cid = $data['id'];
            $log->ctime = time();
            $log->money = $data['money'];
            $log->save();
            unset($agent);
            unset($member);
        }
        if($data['card_type']){
            $agent = User::where('id', $uid)->find();
            $member = User::where('id', $data['id'])->find();
            if($agent->money < $data['card_type']){
                $msg = Result::error('点数不足', null, ['token' => Request::token()]);
                return $msg;
            }
            $type   =   '0';
            switch ($data['card_type'])
            {
                case 0.5;
                    $time  =   7*60*60*24;
                    $day ='七天';
                    break;
                case 1;
                    $time  =   30*60*60*24;
                    $day ='一个月';
                    break;
                case 2;
                    $time  =   90*60*60*24;
                    $day ='三个月';
                    break;
                case 8;
                    $time  =   365*60*60*24;
                    $day ='一年';
                    break;
                case 10;
                    $type   =   1;
                    $day ='永久';
                    break;
            }
            if($type == '1'){
                $member->type = 1;
                $agent->money = $agent->money - $data['card_type'];
                $member->parentid = $uid;
                $agent->save();
                $member->save();
                $log = new Timelog();
                $log->uid = $uid;
                $log->cid = $data['id'];
                $log->ctime = time();
                $log->day = 'all';
                $log->money = $data['card_type'];
                $log->lasttime = 'all';
                $log->save();
            }else{
                if($member->lasttime < time()){
                   $member->lasttime = time() + $time;
                }else{
                    $member->lasttime = $member->lasttime + $time;
                }
                $agent->money = $agent->money - $data['card_type'];
                $member->parentid = $uid;
                $agent->save();
                $member->save();
                $day = timediff($time);
                $log = new Timelog();
                $log->uid = $uid;
                $log->cid = $data['id'];
                $log->ctime = time();
                $log->day = $day['day'];
                $log->money = $data['card_type'];
                $log->lasttime = $member->lasttime;
                $log->save();
            }
        }
        return $msg = Result::success('操作成功', url('/admin/platagent'));
    }

    /**
     * use for:
     * auth: Joql
     * @param $data
     * @return array|string
     * @throws \Exception
     * date:
     */
    public static function editAccount($data, $uid)
    {
        $res = User::update($data, ['id' => $uid]);
        if ($res) {

            $msg = Result::success('编辑成功', url('/admin/accountEdit'));
        } else {
            $msg = Result::error('编辑失败');
        }
        return $msg;
    }

    /**
     * 删除用户
     * @param $uid 用户id
     * @return array|string
     * @author 原点 <467490186@qq.com>
     * @throws \Exception
     */
    public static function delete($uid)
    {
        if (!$uid) {
            return Result::error('参数错误');
        }
        if ($uid == 1) {
            return Result::error('超级管理员无法删除');
        }
        $res = User::destroy($uid);
        if ($res) {
            AuthGroupAccess::where('uid', '=', $uid)->delete();
            $msg = Result::success('删除成功');
        } else {
            $msg = Result::error('删除失败');
        }
        return $msg;
    }

    public static function addMember($data, $uid)
    {
        //验证数据合法性
        $validate = Validate::make([
            'name'  => 'require',
            'card_type' => 'require',
        ]);
        if (!$validate->check($data)) {
            $msg = Result::error($validate->getError(), null, ['token' => Request::token()]);
            return $msg;
        }
        if (isset($data['password']) && ($data['password'] !== $data['password_confirm'])) {
            $msg = Result::error('两次密码不一致', null, ['token' => Request::token()]);
            return $msg;
        }

        $user = new User;
        $money = User::where('id','=',$uid)->value('money');
        if($money < $data['card_type']){
            $msg = Result::error('点数不足', null, ['token' => Request::token()]);
            return $msg;
        }
        $type   =   '0';
        switch ($data['card_type'])
        {
            case 0.5;
                $time  =   7*60*60*24;
                break;
            case 1;
                $time  =   30*60*60*24;
                break;
            case 2;
                $time  =   90*60*60*24;
                break;
            case 8;
                $time  =   365*60*60*24;
                break;
            case 10;
                $type   =   1;
                break;
        }

        $user->parentid = $uid;
        $user->username = $data['name'];
        isset($data['password']) && $user->password =  md5(sha1($data['password']));
        $user->power =  '2';
        $user->status =  '1';
        //$user->phone =  $data['contact'];
        //$user->weichat =  $data['wx'];
        //$user->share_ma =  rand('100000','999999');
        $user->ctime =  time();
        $user->logintime =  '0';
        $user->lasttime =  '0';
        $user->money =  '0.00';
//        $sha_count = User::where('share_ma','=',$user->share_ma)->count();
//        if($sha_count>0){
//            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
//            return $msg;
//        }
        $count = User::where('username','=',$user->username)->count();
        if($count>0){
            $msg = Result::error('用户名已存在', null, ['token' => Request::token()]);
            return $msg;
        }
        $res = $user->save();
        if ($res) {
            if($type == '1'){
                User::update([
                    'type'=>'1',
                    'money' => '-10.00'
                ],['id'=>$user->id]);
                $timelog = new Timelog();
                $timelog->uid = $uid;
                $timelog->ctime = time();
                $timelog->cid = $user->id;
                $timelog->day = 'all';
                $timelog->money = $data['card_type'];
                $timelog->lasttime = 'all';
                $timelog->save();
            }else{
                $now = time();
                User::update([
                    'lasttime' => $now+$time,
                ],['id'=>$user->id]);
                $card = timediff($time);
                $timelog = new Timelog();
                $timelog->uid = $uid;
                $timelog->ctime = $now;
                $timelog->cid = $user->id;
                $timelog->day = $card['day'];
                $timelog->money = $data['card_type'];
                $timelog->lasttime = $now+$time;
                $timelog->save();
                User::Where(['id'=>$uid])->setDec('money',$data['card_type']);
            }
            $msg = Result::success('添加成功', url('/admin/platmember'));
        }else{
            $msg = Result::error('添加失败', null, ['token' => Request::token()]);
        }
        return $msg;

    }

    public static function editMember($data, $uid)
    {
        if(isset($data['user_type']) && $data['user_type'] == '1'){
            //验证数据合法性
            $validate = Validate::make([
                'id' => 'require',
                'name'  => 'require',
                'wx'  => 'require',
                'contact'  => 'require',
                'user_type' => 'require',
            ]);
        }elseif (isset($data['user_type']) && $data['user_type'] == '2'){
            //验证数据合法性
            $validate = Validate::make([
                'id' => 'require',
                'name'  => 'require',
                'user_type' => 'require',
            ]);
        }

        if (!$validate->check($data)) {
            $msg = Result::error($validate->getError(), null, ['token' => Request::token()]);
            return $msg;
        }

        $user = User::Where('id',$data['id'])->find();
        //修改密码 可选
        if (isset($data['password']) && ($data['password'] !== $data['password_confirm'])) {
            $msg = Result::error('两次密码不一致', null, ['token' => Request::token()]);
            return $msg;
        }else{
            $old_pass = $user->password;
            $user->password =  md5(sha1($data['password']));
            if($old_pass !== md5(sha1($data['password']))){
                $passlog = new PassLog;
                $passlog->ip = getIP();
                $passlog->ctime = time();
                $passlog->uid = $data['id'];
                $passlog->aid = $uid;
                $passlog->old_pass = $old_pass;
                $passlog->pass = md5(sha1($data['password']));
                $passlog->web = 0;
                $passlog->save();
            }
        }
        //修改微信和联系方式
        isset( $data['wx']) && $user->weichat =  $data['wx'];
        isset( $data['contact']) && $user->phone =  $data['contact'];

        //追加类型
        if(isset($data['card_type'])){
            $agent = User::where('id', $uid)->find();
            if($agent->money < $data['card_type']){
                $msg = Result::error('点数不足', null, ['token' => Request::token()]);
                return $msg;
            }
            $type   =   '0';
            switch ($data['card_type'])
            {
                case 0.5;
                    $time  =   7*60*60*24;
                    $day ='七天';
                    break;
                case 1;
                    $time  =   30*60*60*24;
                    $day ='一个月';
                    break;
                case 2;
                    $time  =   90*60*60*24;
                    $day ='三个月';
                    break;
                case 8;
                    $time  =   365*60*60*24;
                    $day ='一年';
                    break;
                case 10;
                    $type   =   1;
                    $day ='永久';
                    break;
            }
            if($type == '1'){
                $user->type = 1;
                $agent->money = $agent->money - $data['card_type'];
                $user->parentid = $uid;
                $agent->save();

                $log = new Timelog();
                $log->uid = $uid;
                $log->cid = $data['id'];
                $log->ctime = time();
                $log->day = 'all';
                $log->money = $data['card_type'];
                $log->lasttime = 'all';
                $log->save();
            }else{
                if($user->lasttime < time()){
                    $user->lasttime = time() + $time;
                }else{
                    $user->lasttime = $user->lasttime + $time;
                }
                $agent->money = $agent->money - $data['card_type'];
                $user->parentid = $uid;
                $agent->save();
                $day = timediff($time);
                $log = new Timelog();
                $log->uid = $uid;
                $log->cid = $data['id'];
                $log->ctime = time();
                $log->day = $day['day'];
                $log->money = $data['card_type'];
                $log->lasttime = $user->lasttime;
                $log->save();
            }
        }
        if($data['user_type'] == '1'){
            $money = User::where('id','=',$uid)->value('money');
            if($money < 20){
                $msg = Result::error('点数不足', null, ['token' => Request::token()]);
                return $msg;
            }else{
                $user->parentid = $uid;
                $user->power = 1;
                $user->share_ma =  rand('100000','999999');
                $sha_count = User::where('share_ma','=',$user->share_ma)->count();
                if($sha_count>0){
                    $msg = Result::error('修改失败', null, ['token' => Request::token()]);
                    return $msg;
                }
            }
        }

        $user->ctime =  time();
        $res = $user->save();
        if ($res) {
            if($data['user_type'] == '1'){
                User::where('id','=',$uid)->setDec('money',20);
                $moneylog = new Moneylog();
                $moneylog->uid = $uid;
                $moneylog->ctime = time();
                $moneylog->cid = $user->id;
                $moneylog->money = '20';
                $moneylog->save();
            }
            User::update(['money'=>20],['id'=>$data['id']]);
            $kai = new Kai();
            $kai->uid = $uid;
            $kai->cid = $data['id'];
            $kai->ctime = time();
            $kai->save();
            $msg = Result::success('修改成功', url('/admin/platmember'));
        }else{
            $msg = Result::error('修改失败', null, ['token' => Request::token()]);
        }
        return $msg;

    }

    public static function addMemberMore($data, $uid)
    {
        //验证数据合法性
        $validate = Validate::make([
            'num'  => 'require',
            'card_type' => 'require',
        ]);
        if (!$validate->check($data)) {
            $msg = Result::error($validate->getError(), null, ['token' => Request::token()]);
            return $msg;
        }

        $money = User::where('id','=',$uid)->value('money');
        if($money < $data['card_type'] * $data['num']){
            $msg = Result::error('点数不足', null, ['token' => Request::token()]);
            return $msg;
        }
        $type   =   '0';
        $time   =   '0';
        switch ($data['card_type'])
        {
            case 0.5;
                $time  =   7*60*60*24;
                $day ='七天';
                break;
            case 1;
                $time  =   30*60*60*24;
                $day ='一个月';
                break;
            case 2;
                $time  =   90*60*60*24;
                $day ='三个月';
                break;
            case 8;
                $time  =   365*60*60*24;
                $day ='一年';
                break;
            case 10;
                $type   =   1;
                $day ='永久';
                break;
        }
        $users   =   [];
        for ($i = 0; $i < $data['num']; $i++)
        {
            $username = strtolower(getRandomString(6));
            if(User::Where('username',$username)->count() == 0){
                $users[$i]['username'] = $username;
                $users[$i]['day'] = $day;
                $users[$i]['lasttime'] = date('Y-m-d H:i:s',time()+$time);;
                $user = new User;
                $user->parentid = $uid;
                $user->username = $username;
                $user->password =  md5(sha1('123456'));
                $user->power =  '2';
                $user->status =  '1';
                $user->ctime =  time();
                $user->money =  '0.00';
                if($type == '0'){
                    $user->lasttime =  time()+$time;
                }else{
                    $user->type =  '1';
                }
                $res = $user->save();
                User::Where('id',$uid)->setDec('money',$data['num']*$data['card_type']);
                $adduser = new Adduser();
                $adduser->uid = $uid;
                $adduser->cid = $user->id;
                $adduser->time = $day;
                $adduser->lasttime = time()+$time;
                $adduser->ctime = time();
                $adduser->save();
            }else{
                $i = $i -1;
            }
            unset($username);
            unset($user);
            unset($adduser);
        }
        $zi = '';
        foreach ($users as $value)
        {
            $zi .=$value['username'].'----123456----'.$value['day'].'----'.$value['lasttime']."\r\n";
        }
        session('down_userlist',$zi);
        $msg = Result::success('生成成功', url('/admin/platdown'));
        return $msg;
    }

}