<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) {
	SetCookie('redir', $_SERVER['REQUEST_URI'], time()+1800, '/', '');
	header('Location: /');
}
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret, birthday, uid, last_check FROM `$table` WHERE id='$user' AND uid='$uid' LIMIT 1";
$data = $mysql->getData( $query );

if ( !count($data) )
	header('Location: /');

$uid = $data[0]['uid'];
$last_check = $data[0]['last_check'];
$my_birthday = $data[0]['birthday'];
$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
// get user token and secret

$today = date('m-d');
$now_time = time();

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$id = json_decode( $ff_user -> show_user(), true );
$api = json_decode( $ff_user -> rate_limit_status(), true );
$api_remaining = $api['remaining_hits'];
$api_reset_time = $api['reset_time_in_seconds'];

if ( $_GET['type'] == 'follower' ) {
	$friend = json_decode( $ff_user -> followers( $id['id'] ), true );
	$type = array('0' => array('follower', '关注你的人'), '1' => array('following', '你关注的人'));
	$total = $id['followers_count'];
}
else {
	$friend = json_decode( $ff_user -> friends( $id['id'] ), true );
	$type = array('0' => array('following', '你关注的人'), '1' => array('follower', '关注你的人'));
	$total = $id['friends_count'];
}

$birthday_user = array();
$not_in_db_user = array();
$string = array();
$string['seperator'] = '<hr />';
$string['login_time'] = '<p>当前登录为 <a href="http://fanfou.com/' . $id['id'] . '">' . $id['screen_name'] . '</a>。<br />当前北京时间：<i>' . date('Y-m-d H:i:s') . '</i>。<p>';
$birthday_number = 0;
$recent_birthday_number = 0;
$checked_in_db = 0;
$not_in_db_user_number = 0;
$empty = 0;
$empty_updated = 0;

if ( substr($my_birthday, 5) == $today )
	$string['birthday_list'] = '<img style="border: 0;" src="ff-you.gif" />亲！今天是你的生日哎！<strong>生日快乐~ 撒花~</strong><br />';

foreach ( $friend as $key=>$value ) {
	
	$userid = $value;
	$friend_query = "SELECT birthday FROM `$table` WHERE id='$userid' LIMIT 1";
	$friend_data = $mysql->getData( $friend_query );
	
	if ( !count($friend_data) ) {
		array_push($not_in_db_user, $userid);
		$not_in_db_user_number++;
	}
	else {
		$data = $mysql->getLine( $friend_query );
		$birthday = $data['birthday'];
		$checked_in_db++;
		if ( $birthday == '' )
			$empty++;
		if ( $birthday == '' && $_GET['retry'] == 'empty' ) {
			array_push($not_in_db_user, $userid);
			$not_in_db_user_number++;
		}
	}
	
	if ( substr($birthday, 5) == $today ) {
		$userinfo = json_decode( $ff_user -> show_user( $userid ), true );
		$screen_name = $userinfo['screen_name'];
		$profile_image_url = $userinfo['profile_image_url'];
		$string['birthday_list'] .= '<img style="border: 0;" src="' . $profile_image_url . '" /><strong>' . $screen_name . '</strong> (<a target="_blank" title=" - 链接会在新窗口打开 - " href="http://fanfou.com/' . $userid . '">' . $userid . '</a>) &lt;- 这货今儿个过生日<br />';
		array_push($birthday_user, $screen_name);
		$birthday_number++;
	}
	else if ( $birthday != '' ) {
		$today_timestamp = mktime( 0, 0, 0, date("m"), date("d"), date("Y") );
		$birthday_timestamp = mktime( 0, 0, 0, substr($birthday, 5, 2), substr($birthday, 8, 2), date("Y") );
		if ( $birthday_timestamp - $today_timestamp <= 3600 * 24 * 7 && $birthday_timestamp > $today_timestamp ) {
			$userinfo = json_decode( $ff_user -> show_user( $userid ), true );
			$screen_name = $userinfo['screen_name'];
			$recent_birthday[$recent_birthday_number] = '<span class="recent_birthday" title="将在 ' . date('Y') . '-' . substr($birthday, 5) . ' 过生日"><strong>' . $screen_name . '</strong> (<a href="http://fanfou.com/' . $userid . '" title=" - 链接会在新窗口打开 - " target="_blank">' . $userid . '</a>)</span>';
			$recent_birthday_number++;
		}
	}

}

if ( $birthday_number == 0 )
	$string['birthday_number'] = '<p>查找了 ' . $checked_in_db . ' 位' . $type[0][1] . '，发现根本没有在今儿过生日的！';
else
	$string['birthday_number'] = '<p>查找了 ' . $checked_in_db . ' 位' . $type[0][1] . '，发现有 ' . $birthday_number . ' 位在今儿过生日的。';

if ( $not_in_db_user_number && $_GET['retry'] != 'empty' ) {
	$string['birthday_number'] .= '<br /><span id="count_status"><img src="ff-loading.gif" style="border: 0;" />还有 ' . $not_in_db_user_number . ' 人的生日需要从饭否获取；当前获取 <span id="count_querying">0</span> / ' . $not_in_db_user_number . '。</span>';
}

if ( $recent_birthday_number ) {
	for ( $i = 0; $i < $recent_birthday_number; $i++ )
		$string['recent_birthday_user'] .= $recent_birthday[$i] . '，';
	$string['birthday_birthday'] = '<p>在以后的一周内过生日的还有 ' . $string['recent_birthday_user'] . '别忘了到时候祝福他们！';
}

$string['search_other'] = '<p>已经查找<strong>' . $type[0][1] . '</strong>的生日，是否需要<a href="?type=' . $type[1][0] . '">查找' . $type[1][1] . '的生日</a>？';

if ( !isset($_GET['retry']) && $empty ) {
	if ( isset($_GET['type']) )
		$retry_param = 'type=' . $_GET['type'] . '&';
	$string['birthday_number'] .= '<br /><br />在上回查找时有 ' . $empty . ' 人没有提供他们自己的生日。去看看他们有没有<a href="?' . $retry_param . 'retry=empty" onclick="if(!confirm(\'这将开始获取 ' . $empty . ' 位好友的生日，可能需要的时间较长。\n\n是否继续？\')) return false;">更新生日信息</a>？或者，<a href="?' . $retry_param . 'retry=all" onclick="if(!confirm(\'这将开始获取你所有 ' . count($friend) . ' 位好友的生日，可能需要的时间较长。\n\n是否继续？\')) return false;">重新获取所有 ' . count($friend) . ' 位好友的生日</a>？';
}
else if ( $_GET['retry'] == 'empty' || $_GET['retry'] == 'all' ) {
	if ( $_GET['retry'] == 'all' )
		$not_in_db_user_number = count($friend);
	$string['birthday_number'] .= '<br /><br /><span id="count_status"><img src="ff-loading.gif" style="border: 0;" />有 ' . $not_in_db_user_number . ' 人的生日需要从饭否获取；当前获取 <span id="count_querying">0</span> / ' . $not_in_db_user_number . '。</span>';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Fanfou Birthday - 物以类聚</title>
<style type="text/css">body {font: 12px Verdana, Arial, Helvetica, sans-serif; width: 95%;}</style>
<?php
if ( count($not_in_db_user) != 0 || $_GET['retry'] == 'all' ) {
	$now_check = time();
	$update_query = "UPDATE `$table` SET last_check='$now_check' WHERE id='$user'";
	$mysql->runSql( $update_query );
?>
<script type="text/javascript">
<!--
var count = 0;
var friends = new Array(<?php
if ($_GET['retry'] == 'all') {
	$not_in_db_user = $friend;
	$not_in_db_user_number = count($friend);
}
foreach ( $not_in_db_user as $name ) {
	if ( $j == $not_in_db_user_number - 1 ) echo '"' . $name . '"';
	else echo '"' . $name . '", ';
	$j++;
}
?>);
var xmlhttp;
function createXMLHttpRequest() {
	if (window.ActiveXObject)
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest)
		xmlhttp = new XMLHttpRequest();
}
function sendRequest() {
	if (friends.length == 0) {
		document.getElementById("count_status").innerHTML = '<img src="ff-right.png" style="border: 0;" /><strong>已完成！</strong><a href="?type=<?php echo $type[0][0]; ?>">点击这里</a>重新检查所有饭友的生日。';
		return;
	}
	createXMLHttpRequest();
	xmlhttp.onreadystatechange = callback;
	xmlhttp.open('GET', 'api.php?user=' + friends.shift()<?php if ($_GET['retry'] == 'empty' || $_GET['retry'] == 'all') echo ' + \'&update=force\''; ?>, true);
	xmlhttp.send();
}
function callback() {
	if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
		var ret = eval( "(" + xmlhttp.responseText + ")" );
		if (ret.status == 'success') {
			count++;
			document.getElementById("count_querying").innerHTML = count;
			sendRequest();
			return;
		}
		else if (ret.status == 'error') {
			document.getElementById("api_return").innerHTML = ret.message;
		}
	}
}
-->
</script>
</head>
<body onload="sendRequest();">
<?php
}
else {
?>
</head>
<body>
<?php
}
?>
<?php include_once("../ga.php") ?>
<h3>获取饭否好友的生日</h3>
<?php
echo $string['login_time'];
echo '<p>' . $string['search_other'] . '</p>';
echo $string['seperator'];
echo $string['birthday_list'];
echo $string['seperator'];
echo $string['birthday_number'];

if ( $birthday_number != 0 ) {
?>
<form name="send_message" method="POST" target="_blank" action="send.php" style="border: 1px solid #f00; width: 521px; margin: 3px; padding: 8px;">
<strong>送上祝福</strong><br />
<textarea rows="3" name="content" style="width: 98%;"><?php foreach ( $birthday_user as $key => $name ) echo '@' . $name . ' '; ?>生日快乐~</textarea><br />
<input type="submit" name="submit" value="发送" />
</form>
<?php
}

$mysql->closeDb();

echo $string['birthday_birthday'];
?>
<p id="api_return"></p>

<p><a title="欢迎反馈! 感激不尽! (T_T)" href="http://fanfou.com/home?status=@marcher+%23ffbirthday%23+">问题?反馈?</a> | <a href="/?logout=<?php echo time(); ?>">退出登录</a><br />
<span id="api_info" style="font-style: italic;">API 信息：<span id="api_remaining"><?php echo $api_remaining; ?></span> / 1500; 将在 <span id="api_reset_after"><?php echo date('i:s', $api_reset_time) ?></span> 之后重置次数</span>
<hr />
<p><a href="/">返回所有应用</a></p>
</body>
</html>
