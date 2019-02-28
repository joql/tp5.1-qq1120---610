<?php

namespace app\admin\controller;

use app\admin\model\Dianka;
use app\admin\service\CardService;
use app\admin\service\UserService;
use app\admin\traits\Result;
use auth\Auth;
use think\Validate;

class Card extends Common
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
     * use for:添加点卡
     * auth: Joql
     * @return array|mixed|string
     * date:2019-02-26 18:58
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate = validate('Card');
            if (!$validate->check($data)) {
                return Result::error($validate->getError());
            }
            $res = CardService::add($data,$this->uid);
            return $res;
        } else {
            return $this->fetch();
        }
    }

    public function down(){
        $list = session('card_list');
        if(!empty($list)){
            session('card_list',null);
            return download($list, 'cardlist.txt', true);
        }
    }
    public function uses(){
        if ($this->request->isAjax()) {
            $data = [
                'card_no' => $this->request->get('card_no', '', 'trim'),
                'starttime' => $this->request->get('starttime', '', 'trim'),
                'endtime' => $this->request->get('endtime', '', 'trim'),
                'card_type' => $this->request->get('card_type', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = Dianka::withSearch(['dianka', 'ctime', 'name'], [
                'dianka' => $data['card_no'],
                'ctime' => [$data['starttime'], $data['endtime']],
                'name' => $data['card_type'],
            ])->where(array('uid'=>$this->uid,'y'=>0))->paginate($data['limit'], false, ['query' => $data]);
            $cards = [];
            foreach ($list as $key => $val) {
                $cards[$key] = $val;
                $cards[$key]['mname'] = $this->user['username'];
                $cards[$key]['ctime'] = date('Y-m-d H:i:s',$cards[$key]['ctime']);
                //$cards[$key]['title'] = $val->group_titles;
            }
            $this->json($cards, 0, '', ['count' => $list->total()]);
        }
        return $this->fetch();
    }

    public function used(){
        if ($this->request->isAjax()) {
            $data = [
                'card_no' => $this->request->get('card_no', '', 'trim'),
                'limit' => $this->request->get('limit', 10, 'intval'),
            ];
            $list = Dianka::withSearch(['dianka'], [
                'dianka' => $data['card_no'],
            ])->where(array('uid'=>$this->uid,'y'=>1))->paginate($data['limit'], false, ['query' => $data]);
            $cards = [];
            foreach ($list as $key => $val) {
                $cards[$key] = $val;
                $cards[$key]['mname'] = $this->user['username'];
                $cards[$key]['yname'] = \app\admin\model\User::Where(array('id'=>$val['yid']))->value('nick_name');
                $cards[$key]['ctime'] = date('Y-m-d H:i:s',$cards[$key]['ctime']);
                !empty($cards[$key]['stime']) && $cards[$key]['stime'] = date('Y-m-d H:i:s',$cards[$key]['stime']);
                //$cards[$key]['title'] = $val->group_titles;
            }
            $this->json($cards, 0, '', ['count' => $list->total()]);
        }
        return $this->fetch();
    }

    public function exportTxt(){
        $data = [
            'card_no' => $this->request->get('card_no', '', 'trim'),
            'starttime' => $this->request->get('starttime', '', 'trim'),
            'endtime' => $this->request->get('endtime', '', 'trim'),
            'card_type' => $this->request->get('card_type', '', 'trim'),
            'limit' => $this->request->get('limit', 10, 'intval'),
        ];
        $list = Dianka::withSearch(['dianka', 'ctime', 'name'], [
            'dianka' => $data['card_no'],
            'ctime' => [$data['starttime'], $data['endtime']],
            'name' => $data['card_type'],
        ])->where(array('uid'=>$this->uid,'y'=>0))->select();
        $cards = '';
        foreach ($list as $key => $val) {
            $cards .= $val['name'].'----'.$val['dianka']."\r\n";
        }
        if(empty($cards)){
            return Result::error('无数据',url('/admin/uses'));
        }else{
            return download($cards, 'cards.txt', true);
        }
    }
    public function exportExcel(){
        $data = [
            'card_no' => $this->request->get('card_no', '', 'trim'),
            'starttime' => $this->request->get('starttime', '', 'trim'),
            'endtime' => $this->request->get('endtime', '', 'trim'),
            'card_type' => $this->request->get('card_type', '', 'trim'),
            'limit' => $this->request->get('limit', 10, 'intval'),
        ];
        $list = Dianka::withSearch(['dianka', 'ctime', 'name'], [
            'dianka' => $data['card_no'],
            'ctime' => [$data['starttime'], $data['endtime']],
            'name' => $data['card_type'],
        ])->where(array('uid'=>$this->uid,'y'=>0))->select();
        $cards = [];
        foreach ($list as $key => $val) {
            $cards[$key] = [$val['name'],$val['dianka']];
        }
        if(empty($cards)){
            return Result::error('无数据',url('/admin/uses'));
        }else{
            $name='cards';

            $header=['类型','卡密'];

            return excelExport($name,$header,$cards);
        }
    }
}
