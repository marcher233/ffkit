<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) {
	$state = false;
	$o = new OAuth( FF_AKEY , FF_SKEY );
	$keys = $o -> getRequestToken();
	$tmp_token = $keys['oauth_token'];
	$tmp_secret = $keys['oauth_token_secret'];
	$aurl = $o -> getAuthorizeURL( $tmp_token ,false , FF_CALLBACK);
 SetCookie('tmp[0]', $tmp_token, time()+1800, '/');
	SetCookie('tmp[1]', $tmp_secret, time()+1800, '/');
	SetCookie('redir', '/5/following.php', time()+1800, '/');
	header("Content-type: text/html; charset=utf-8");
	echo '<p id="fanfou_oauth">你需要<a href="'.$aurl.'">去饭否为应用授权</strong></a>才可以继续使用本功能。<br /><br />授权时会显示此应用名称“物以类聚”；授权操作在饭否网站完成，你的账户密码是安全的。<br />“物以类聚”只会获取必要的信息，绝不会随意操作无关内容。<br /><strong>授权完成后，请点击页面上方的自曝排行榜重新回来。</strong>';
	die();
}
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret, birthday, uid, last_check FROM `$table` WHERE id='$user' AND uid='$uid' LIMIT 1";
$data = $mysql->getLine( $query );

if ( !count($data) )
	header('Location: /');

$token = $data['oauth_token'];
$secret = $data['oauth_token_secret'];
$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );

if ( $_GET['t'] == 'follower' ) {
	$friend = json_decode( $ff_user -> followers( $user ), true );
	foreach ( $friend as $name ) {
		if ( $j == count($friend) - 1 )
			$sql .= '\'' . $name . '\'';
		else
			$sql .= '\'' . $name . '\', ';
		$j++;
	}
	$string = '关注你的人';
	$string2 = '你关注的人';
	$param = 'following';
}
else {
	$friend = json_decode( $ff_user -> friends( $user ), true );
	foreach ( $friend as $name ) {
		if ( $j == count($friend) - 1 )
			$sql .= '\'' . $name . '\'';
		else
			$sql .= '\'' . $name . '\', ';
		$j++;
	}
	$string = '你关注的人';
	$string2 = '关注你的人';
	$param = 'follower';
}

?>
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
<h2>自曝转发排行榜 / <a href="/5/">查看完整排行</a> / <a href="http://5.fanfou.com/">查看最新自曝</a> / <a href="/">返回物以类聚</a></h2>
<p>排行最近更新于: <?php
$mysql = new SaeMysql();
$mysql->setCharset('UTF8');

$query = "SELECT FROM_UNIXTIME(id) id FROM `ff5_id` WHERE name='time'";
$array = $mysql->getLine($query);

echo $array['id'];
?><br />数据来自<a href="http://5.fanfou.com/" target="_blank">饭否自曝图片墙</a>，统计结果仅供参考，最终获奖情况以饭否官方公布为准<br />正在显示<strong><?php echo $string; ?></strong>的排行；查看<a href="?t=<?php echo $param; ?>"><?php echo $string2; ?></a>的排行情况</p>
<ol>
<?php

$query = "SELECT * FROM `ff5_photo` WHERE user_id IN ($sql) ORDER BY repost_count DESC";
$array = $mysql->getData($query);

foreach ( $array as $data ) {
	echo '<li><a href="' . $data['user_url'] . '" target="_blank">' . $data['user_screen_name'] . '</a> 的<a href="http://fanfou.com/statuses/' . $data['msg_id'] . '" target="_blank" title="帮 TA 转发！"><strong>自曝</strong></a>转发量为 ' . $data['repost_count'] . ' 次。</li>';
}

$mysql->closeDb();
?>
</ol>
</body>

</html>
