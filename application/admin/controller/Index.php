<?php

namespace app\admin\controller;

use app\admin\service\UserService;
use app\admin\traits\Result;
use auth\Auth;

class Index extends Common
{
    /**
     * 首页
     * @return mixed
     * @author 原点 <467490186@qq.com>
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * layui 首页
     * @return mixed
     * @author 原点 <467490186@qq.com>
     */
    public function home()
    {
        $advert =db('advert')->where('id',1)->value('content');;
        $this->assign('advert', $advert);
        return $this->fetch();
    }

    public function accountEdit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $res = UserService::editAccount($data,$this->user['username']);
            if($data['share_id'] && $res['msg'] == '编辑成功'){
                session('userid', $data['share_id']);
            }
            return $res;
        } else {
            return $this->fetch();
        }
    }
}
