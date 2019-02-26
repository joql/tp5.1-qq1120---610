<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:38
 */

namespace app\admin\model;

use think\Model;

class Dianka extends Model
{
    protected $autoWriteTimestamp = true;

    public function searchDiankaAttr($query, $value)
    {
        if ($value) {
            $query->where('dianka', 'like', '%' . $value . '%');
        }
    }
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', '=',  $value);
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