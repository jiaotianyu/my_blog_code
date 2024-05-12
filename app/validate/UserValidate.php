<?php
namespace app\validate;

use think\Validate;

class UserValidate extends Validate
{
    protected $rule = [
        'username'  =>  'require|max:10|chsDash',
        'password' =>  'require|max:16|alphaDash',
        'code' => 'require|alphaNum',
        'email' => 'email',
    ];

}