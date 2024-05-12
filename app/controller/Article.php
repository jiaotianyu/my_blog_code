<?php
namespace app\controller;

use app\BaseController;
use think\facade\Request;
use app\service\ArticleService;

class Article extends BaseController
{
    private $service;

    public function initialize() {
        $this->service = new ArticleService();
    }

    /**
     * 写入文章
     * @return \think\response\Json
     */
    public function createArticle() {
        $title = Request::param('title');
        $content = Request::param('content');

        $res = $this->service->createArticleService($title, $content);
        return json($res);
    }

    /**
     * 搜索文章
     * @return \think\response\Json
     */
    public function searchArticle() {
        $keyword = Request::param('keyword');
        $res = $this->service->searchArticleService($keyword);
        return json($res);
    }

    /**
     * 帖子评论
     */
    public function replyArticle() {
        $articleId = Request::param('articleId');
        $uid = Request::param('uid');
        $content = Request::param('content');
        $superiorId = Request::param('superiorId', 0);

        $res = $this->service->replyArticleService($articleId, $uid, $content, $superiorId);
        return json($res);
    }

    /**
     * 帖子详情
     */
    public function getArticleInfo() {
        $articleId = Request::param('articleId');

        $res = $this->service->getArticleInfoService($articleId);
        return json($res);
    }

    /**
     * 获取指定评论回复 & 分页（iterator分页游标）
     */
    public function getArticleReplyList() {
        $articleId = Request::param('articleId');
        $superiorId = Request::param('superiorId', 0);
        $iterator = Request::param('iterator', 0);
        $res = $this->service->getArticleReplyListService($articleId, $superiorId, $iterator);
        return json($res);
    }
}