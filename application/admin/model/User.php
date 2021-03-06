<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/16
 * Time: 15:38
 */

namespace app\admin\model;

use think\Model;

class User extends Model
{
    protected $autoWriteTimestamp = true;
    protected $type = [
        'last_login_time' => 'timestamp',
    ];


    /**
     * 搜索器
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('username|nick_name', 'like', '%' . $value . '%');
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