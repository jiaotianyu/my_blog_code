<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\service\ArticleService;

class Comer extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Comer')
            ->setDescription('the Comer command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $consumer = new ArticleService();
        $consumer->createArticleInfoToRedis();
    }
}
