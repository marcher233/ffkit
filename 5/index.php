<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>饭否五岁自曝转发排行榜 - 物以类聚</title>
<style>
body {font: 15px Verdana, Arial, Helvetica, sans-serif; width: 90%; margin: 35px;}
li {line-height: 2.1em;}
</style>
</head>

<body>
<?php include_once("../ga.php") ?>
<h2>自曝转发排行榜 / <span style="color:red;"><a href="following.php">查看好友自曝</a></span> / <a href="http://5.fanfou.com/">查看最新自曝</a> / <a href="/">返回物以类聚</a></h2>
<p style="font-weight: bold;">排行最近更新于: <?php
$mysql = new SaeMysql();
$mysql->setCharset('UTF8');

$query = "SELECT FROM_UNIXTIME(id) id FROM `ff5_id` WHERE name='time'";
$array = $mysql->getLine($query);

echo $array['id'];
?><br />数据来自<a href="http://5.fanfou.com/" target="_blank">饭否自曝图片墙</a>，统计结果仅供参考，最终获奖情况以饭否官方公布为准<br />找不到自己？按 Ctrl+F 输入你的饭否昵称。</p>
<ol>
<?php

$query = "SELECT * FROM `ff5_photo` WHERE 1 ORDER BY repost_count DESC";
$array = $mysql->getData($query);

foreach ( $array as $data ) {
	echo '<li><a href="' . $data['user_url'] . '" target="_blank">' . $data['user_screen_name'] . '</a> 的<a href="http://fanfou.com/statuses/' . $data['msg_id'] . '" target="_blank" title="帮 TA 转发！"><strong>自曝</strong></a>转发量为 ' . $data['repost_count'] . ' 次。</li>';
}

$mysql->closeDb();
?>
</ol>
</body>

</html>
