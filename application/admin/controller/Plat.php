<?php

namespace app\admin\controller;

use app\admin\model\Dianka;
use app\admin\model\Moneylog;
use app\admin\model\Timelog;
use app\admin\model\User;
use app\admin\service\CardService;
use app\admin\service\UserService;
use app\admin\traits\Result;
use auth\Auth;
use think\Db;

class Plat extends Common
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


    public function money(){
        if ($this->request->isAjax()) {
            $data = [
                'vip' => $this->request->get('vip', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'card_type' => $this->request->get('card_type', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = Timelog::withSearch(['cid', 'ctime', 'day'], [
                'cid' => $data['vip'],
                'ctime' => [$data['starttime'], $data['endtime']],
                'day' => $data['card_type'],
            ])->where(array('uid'=>$this->uid))->paginate($data['limit'], false, ['query' => $data]);
            $cards = [];
            foreach ($list as $key => $val) {
                $cards[$key] = $val;
                //$cards[$key]['mname'] = $this->user['username'];
                $cards[$key]['ctime'] = date('Y-m-d H:i:s',$cards[$key]['ctime']);
                $cards[$key]['iname'] = \app\admin\model\User::Where(array('id'=>$val['cid']))->value('nick_name');
                $cards[$key]['dname'] = \app\admin\model\User::Where(array('id'=>$val['uid']))->value('nick_name');
                $val['day'] == 'all' && $cards[$key]['day'] = '永久';
            }
            $this->json($cards, 0, '', ['count' => $list->total()]);
        }
        return $this->fetch();
    }

    public function point(){
        if ($this->request->isAjax()) {
            $data = [
                'vip' => $this->request->get('vip', '', 'trim'),
                'name' => $this->request->get('name', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = Moneylog::withSearch(['cid', 'ctime', 'uid'], [
                'cid' => $data['vip'],
                'ctime' => [$data['starttime'], $data['endtime']],
                'uid' => $data['name'],
            ])->where(array('uid'=>$this->uid))->paginate($data['limit'], false, ['query' => $data]);
            $cards = [];
            foreach ($list as $key => $val) {
                $cards[$key] = $val;
                //$cards[$key]['mname'] = $this->user['username'];
                $cards[$key]['ctime'] = date('Y-m-d H:i:s',$cards[$key]['ctime']);
                $cards[$key]['iname'] = \app\admin\model\User::Where(array('id'=>$val['cid']))->value('nick_name');
                $cards[$key]['dname'] = \app\admin\model\User::Where(array('id'=>$val['uid']))->value('nick_name');
            }
            $this->json($cards, 0, '', ['count' => $list->total()]);
        }
        return $this->fetch();
    }

    public function agent(){
        if ($this->request->isAjax()) {
            $data = [
                'name' => $this->request->get('name', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = User::withSearch(['name', 'ctime'], [
                'name' => $data['name'],
                'ctime' => [$data['starttime'], $data['endtime']],
            ])->where(array('power'=>1,'parentid'=>$this->uid))->paginate($data['limit'], false, ['query' => $data]);
            $cards = [];
            foreach ($list as $key => $val) {
                $cards[$key] = $val;
                //$cards[$key]['mname'] = $this->user['username'];
                $cards[$key]['ctime'] = date('Y-m-d H:i:s',$cards[$key]['ctime']);
                $cards[$key]['logintime'] = $cards[$key]['logintime'] == 0
                    ? '未登录'
                    : date('Y-m-d H:i:s',$cards[$key]['logintime']);
                $cards[$key]['lasttime'] = $val['type'] == '1' ?
                    '永久' : (
                        $val['lasttime'] == '0' ? '未充值' :date('Y-m-d H:i:s',$cards[$key]['lasttime'])
                );
            }
            $this->json($cards, 0, '', ['count' => $list->total()]);
        }
        return $this->fetch();
    }

    public function agentEdit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //添加
            $data = UserService::add($data, $this->uid);
            return $data;
        } else {
            return $this->fetch();
        }
    }

    public function member(){
        if ($this->request->isAjax()) {
            $data = [
                'name' => $this->request->get('name', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = User::withSearch(['name', 'ctime'], [
                'name' => $data['name'],
                'ctime' => [$data['starttime'], $data['endtime']],
            ])->where(array('power'=>2,'parentid'=>$this->uid))->paginate($data['limit'], false, ['query' => $data]);
            $cards = [];
            foreach ($list as $key => $val) {
                $cards[$key] = $val;
                //$cards[$key]['mname'] = $this->user['username'];
                $cards[$key]['ctime'] = date('Y-m-d H:i:s',$cards[$key]['ctime']);
                $cards[$key]['logintime'] = $cards[$key]['logintime'] == 0
                    ? '未登录'
                    : date('Y-m-d H:i:s',$cards[$key]['logintime']);
                $cards[$key]['lasttime'] = $val['type'] == '1' ?
                    '永久' : (
                    $val['lasttime'] == '0' ? '未充值' :date('Y-m-d H:i:s',$cards[$key]['lasttime'])
                    );
            }
            $this->json($cards, 0, '', ['count' => $list->total()]);
        }

        return $this->fetch();
    }

    public function memberEdit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($data['id']) {
                //编辑
                $res = UserService::editMember($data, $this->uid);
                return $res;
            } else {
                //添加
                $data = UserService::addMember($data, $this->uid);
                return $data;
            }
        } else {
            $uid = $this->request->get('uid', 0, 'intval');
            if ($uid) {
                $list = User::where('id', '=', $uid)->find();
                $this->assign('userinfo', $list);
            }
            return $this->fetch();
        }
    }
    public function memberEditMore()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            //添加
            $data = UserService::addMemberMore($data, $this->uid);
            return $data;
        } else {
            return $this->fetch();
        }
    }
    public function memberDownload(){
        $download_user = session('down_userlist');
        if(!empty($download_user)){
            session('down_userlist',null);
            return download($download_user, 'userlist.txt', true);
        }
    }
}
