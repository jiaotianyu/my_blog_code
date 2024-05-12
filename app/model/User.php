<?php
namespace app\model;

use think\Model;

class User extends Model
{
    // 开启自动时间戳
    protected $autoWriteTimestamp = true;
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'username'        => 'string',
        'email' => 'string',
        'password' => 'string',
        'create_time' => 'timestamp',
    ];

}