CREATE TABLE `mydatabase`.`test_article`  (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(25) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text NOT NULL COMMENT '内容',
  `reply_num` int UNSIGNED NOT NULL DEFAULT 0 COMMENT '评论数',
  `create_time` timestamp NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_title`(`title`),
  INDEX `idx_reply_num`(`reply_num`)
) COMMENT = '帖子信息表';

CREATE TABLE `mydatabase`.`test_article_reply`  (
    `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
    `article_id` int UNSIGNED NOT NULL COMMENT '文章ID',
    `superior_id` int UNSIGNED NOT NULL COMMENT '上级ID',
    `uid` int UNSIGNED NOT NULL COMMENT '用户ID',
    `content` varchar(500) NOT NULL DEFAULT '' COMMENT '评论内容',
    `create_time` timestamp NULL COMMENT '评论时间',
    PRIMARY KEY (`id`)
) COMMENT = '文章评论表';