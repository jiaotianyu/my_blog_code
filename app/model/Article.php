<?php
namespace app\model;

use think\Model;

class Article extends Model
{
    // 开启自动时间戳
    protected $autoWriteTimestamp = true;
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'title'        => 'string',
        'content'      => 'text',
        'reply_num' => 'int',
        'create_time' => 'timestamp',
    ];
}