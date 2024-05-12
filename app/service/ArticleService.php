<?php
namespace app\service;

use app\model\Article;
use app\model\ArticleReply;
use app\validate\ArticleValidate;
use rabbitmq\rabbitmq;
use think\exception\ValidateException;
use elasticsearch\Elastic;
use think\facade\Cache;
use think\facade\Db;

class ArticleService
{
    /**
     * 发布文章
     * @param $title    string  文章标题
     * @param $content  string  文章内容
     * @return array
     */
    public function createArticleService(string $title, string $content) {
        $articleModel = new Article;
        $data = ['title' => $title, 'content' => $content];
        try {
            // 验证数据是否符合要求
            validate(ArticleValidate::class)->check($data);
        } catch (ValidateException $e) {
            // 验证失败 输出错误信息
            $res = dataReturn(PARAM_ERROR, [], $e->getMessage());
            goto ret;
        }

        // 通过RabbitMQ实现异步三写
        try {
            // 写入数据库，开启事务
            $articleModel->startTrans();
            $articleModel->save($data);

            // 写入RabbitMQ使用简单模式，完成Redis和ES写入
            // 将文章ID放入数组
            $data['id'] = $articleModel->id;
            $amqp = new rabbitmq();
            $amqp->simpleSend('Article', json_encode($data));
            $amqp->close();

            // 事务提交，完成MySQL和RabbitMQ双写
            $articleModel->commit();
            $res = dataReturn(SUCCESS, [], '文章写入成功');
        }catch (\Exception $e) {
            $res = dataReturn(SYSTEM_ERROR, [], $e->getMessage());
        }

        ret:
        return $res;
    }

    /**
     * RabbitMQ Article消费者，写入Redis和ES
     */
    public function createArticleInfoToRedis() {
        $amqp = new rabbitmq();
        $amqp->simpleReceiveArticle('Article');
    }

    /**
     * 搜索贴子
     * @param $keyword
     * @return array
     */
    public function searchArticleService($keyword) {
        // 去ES搜索
        $es = new Elastic('article');
        $body = array(
            'query' => array(
                'match' => array(
                    'all' => $keyword
                )
            )
        );
        $res = $es->searchDoc($body);
        if ($res['code'] == 200) {
            $res['message'] = '命中数据';
        } else {
            $res['data'] = [];
        }
        return $res;
    }

    /**
     * 添加评论
     * @param $articleId
     * @param $uid
     * @param $content
     * @param $superiorId
     * @return array
     */
    public function replyArticleService($articleId, $uid, $content, $superiorId) {
        // 数据格式化
        $data = array(
            'article_id' => $articleId,
            'superior_id' => $superiorId,
            'content' => $content,
            'uid' => $uid,
        );
        $articleReplyModel = new ArticleReply();
        $articleModel = new Article();
        $redis = Cache::store('redis')->handler();

        // 开启事务
        $articleReplyModel->startTrans();
        $articleReplyModel->save($data);
        $articleReplyId = $articleReplyModel->id;

        // 文章评论数+1
        $articleModel->where(['id' => $articleId])->update(['reply_num' => Db::raw('reply_num+1')]);

        // 添加redis hash记录回复ID(根据上级分组)
        $res = $redis->hset('ArticleReplyList:'.$articleId.':'.$superiorId, 'Reply:'.$articleReplyId, $articleReplyId);
        if (!$res) {
            $articleReplyModel->rollback();
            return dataReturn(SYSTEM_ERROR, [], 'redis数据插入失败');
        }

        $articleReplyModel->commit();
        return dataReturn(SUCCESS, [], '评论成功');
    }

    /**
     * 帖子详情
     * @param $articleId
     * @return array
     */
    public function getArticleInfoService($articleId) {
        $redis = Cache::store('redis')->handler();
        $articleReplyModel = new ArticleReply();
        $rediskey = 'Article:'.$articleId;
        // 获取文章是否存在
        $res = getBoolmFilter($redis, $rediskey);
        if (!$res) {
            return dataReturn(NOT_FIND, [], '找不到文章');
        }

        // 获取文章信息
        $articleInfo = $redis->get($rediskey);
        $articleInfo = $articleInfo ? json_decode($articleInfo, true) : '';
        if (!$articleInfo) {
            $articleModel = new Article();
            $articleInfo = $articleModel->field('title,content')->find()->toArray();
        }
        $data['info'] = $articleInfo;

        // 获取评论一级信息
        // 游标引用参数，不可变更
        $iterator = null;
        $replyList = $redis->hscan('ArticleReplyList:'.$articleId.':0', $iterator, '*', 20);
        if ($replyList) {
            // 评论回复ID集
            $ids = [];
            foreach ($replyList as $v) {
                $ids[] = $v;
            }

            // 获取评论信息
            $data['replyList'] = $articleReplyModel
                ->alias('t1')
                ->join('user t2', 't1.uid = t2.id')
                ->field('t1.id,uid,content,t1.create_time,t2.username')
                ->where('t1.id', 'in', $ids)
                ->order('t1.create_time desc')
                ->select()
                ->toArray();
        } else {
            $data['replyList'] = [];
        }
        // 返回redis游标，做分页使用
        $data['iterator'] = $iterator;
        return dataReturn(SUCCESS, $data, '帖子信息');
    }

    /**
     * 获取指定楼层的回复 & 分页
     * @param $articleId        int     文章ID
     * @param $superiorId       int     评论ID
     * @param $iterator         mixed   redis游标，用来分页使用
     * @return array
     */
    public function getArticleReplyListService($articleId, $superiorId, $iterator) {
        $redis = Cache::store('redis')->handler();
        $articleReplyModel = new ArticleReply();
        // 获取指定楼层回复
        // 游标引用参数，不可变更
        if (!$iterator) {
            $iterator = null;
        }
        $replyList = $redis->hscan('ArticleReplyList:'.$articleId.':'.$superiorId, $iterator, '*', 20);
        if ($replyList) {
            // 评论回复ID集
            $ids = [];
            foreach ($replyList as $v) {
                $ids[] = $v;
            }

            // 获取评论信息
            $data = $articleReplyModel
                ->alias('t1')
                ->join('user t2', 't1.uid = t2.id')
                ->field('t1.id,uid,content,t1.create_time,t2.username')
                ->where('t1.id', 'in', $ids)
                ->order('t1.create_time desc')
                ->select()
                ->toArray();
        } else {
            $data = [];
        }
        return dataReturn(SUCCESS, $data, '回复列表');
    }
}