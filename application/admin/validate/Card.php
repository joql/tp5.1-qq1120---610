<?php
/**
 * Created by PhpStorm.
 * User: yuandian
 * Date: 2016/9/9
 * Time: 15:39
 */

namespace app\admin\validate;

use think\Validate;

class Card extends Validate
{
    protected $rule = [
        'card_type' => 'require',
        'num' => 'require|number',
    ];

}