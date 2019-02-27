<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:38
 */

namespace app\admin\model;

use think\Model;

class Moneylog extends Model
{
    protected $autoWriteTimestamp = true;

    public function searchCidAttr($query, $value)
    {
        if ($value) {
            $ids = User::Where('username','like',"%{$value}%")->column('id');
            if($ids){
                $query->where('cid', 'in', implode(',',$ids));
            }else{
                $query->where('cid', '=', '99999999999999999999999999999');
            }
        }
    }
    public function searchUidAttr($query, $value)
    {
        if ($value) {
            $ids = User::Where('username','like',"%{$value}%")->column('id');
            if($ids){
                $query->where('uid', 'in', implode(',',$ids));
            }else{
                $query->where('uid', '=', '99999999999999999999999999999');
            }
        }
    }
    public function searchCtimeAttr($query, $value, $data)
    {
        if ($value[0] && $value[1]) {
            $query->whereBetweenTime('ctime', strtotime($value[0]), strtotime($value[1]));
        }elseif ($value[0]){
            $query->where('ctime' , '>', strtotime($value[0]));
        }elseif  ($value[1]){
            $query->where('ctime' , '<', strtotime($value[1]));
        }
    }
}