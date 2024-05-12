<?php
namespace app\model;

use think\Model;

class ArticleReply extends Model
{
    // 开启自动时间戳
    protected $autoWriteTimestamp = true;
    // 设置字段信息
    protected $schema = [
        'id'          => 'int',
        'article_id'        => 'int',
        'superior_id' => 'int',
        'uid' => 'int',
        'content'      => 'text',
        'create_time' => 'timestamp',
    ];
}