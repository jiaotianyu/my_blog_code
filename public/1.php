<?php

$redis = new Redis();
$redis->connect($host, $port);
$redis->auth('my pass'); //密码验证