<?php
/**
 * Created by PhpStorm.
 * User: dell
 * Date: 2018/9/7
 * Time: 10:00
 */

namespace app\admin\service;

use app\admin\model\Dianka;
use app\admin\model\User;
use app\admin\model\LoginLog;
use app\admin\model\AuthGroupAccess;
use think\facade\Request;
use app\admin\traits\Result;

class CardService
{
    use Result;

    /**
     * use for:
     * auth: Joql
     * @param $data
     * @param $uid
     * @return array|string
     * date:
     */
    public static function add($data, $uid)
    {
        $fen    =   ceil($data['num']);
        $ctime  =   $data['card_type'];
        $type   =   '0';
        switch ($ctime)
        {
            case 0.75;
                $time  =   7*60*60*24;
                $name   =   '七天';
                break;
            case 1.5;
                $time  =   30*60*60*24;
                $name   =   '一个月';
                break;
            case 4.5;
                $time  =   90*60*60*24;
                $name   =   '三个月';
                break;
            case 9;
                $time  =   180*60*60*24;
                $name   =   '六个月';
                break;
            case 18;
                $time  =   365*60*60*24;
                $name   =   '一年';
                break;
            case 150;
                $type   =   1;
                $time   =   0;
                $name   =   '永久';
                break;
        }
        $dian   =   '';

        $money = User::Where(array('id'=>$uid))->value('money');
        if($money<$fen*$ctime){
            return Result::error('余额不足');
        }
        for ($i=0;$i<$fen;$i++)
        {
            $M_dianka = new Dianka();
            $data =   randstring(20);
            $M_dianka->uid = $uid;
            $M_dianka->dianka = $data;
            $M_dianka->ctime = time();
            $M_dianka->y = 0;
            $M_dianka->yid = '';
            $M_dianka->time = $time;
            $M_dianka->type = $type;
            $M_dianka->name = $name;
            $M_dianka->save();
            $dian.=   $data."<br><hr>";
        }
        $res = User::update(array('money'=>$money-$fen*$ctime),array('id'=>$uid));

        if ($res) {
            $msg = Result::success('添加成功', url('/admin/add'));
        } else {
            $msg = Result::error('添加失败');
        }
        return $msg;
    }



}