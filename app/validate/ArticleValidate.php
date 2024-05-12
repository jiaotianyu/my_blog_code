<?php
namespace app\validate;

use think\Validate;

class ArticleValidate extends Validate
{
    protected $rule = [
        'title'  =>  'require|max:25',
        'content' =>  'max:5000',
    ];

}