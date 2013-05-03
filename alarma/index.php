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
$sql = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' AND uid='$uid'";
$data = $mysql->getData( $sql );

$c = new SaeCounter();
$alarma_count = $c->get('alarma_count');

if ( !count($data) ) header('Location: /');

$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
// get user token and secret

$timestamp = time();
$update_logon = "UPDATE `$table` set last_check='$timestamp' WHERE id='$user'";
$mysql->runSql( $update_logon );

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$profile = json_decode( $ff_user -> show_user(), true );

$string['login_user'] = '<p>你好哇，<a href="http://fanfou.com/' . $profile['id'] . '">' . $profile['screen_name'] . '</a>！';
$string['login_user'] .= ' | <a href="http://fanfou.com/home?status=%40marcher+" target="_blank">留言</a>或者通过<a href="http://fanfou.com/privatemsg.create/marcher" target="_blank">私信反馈</a>问题 | <a href="/?logout=' . time() . '">退出登录</a> | <a href="/">返回所有应用</a>';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>马闹钟 - 物以类聚</title>
<style type="text/css">
body {font: 12px Verdana, Arial, Helvetica, sans-serif;}
strong {color: #36B;}
h4 {font: 105%; margin: 11px 0;}
#information {width: 500px; display: block; margin: 10px 0; padding: 8px; border: 2px solid #C59;}
.item {width: 642px; margin: 9px 0; padding: 4px;}
.func {width: 40px; float: left;}
.desc {width: 600px; float: right;}
.clearfix {clear: both;}
</style>
<script type="text/javascript" src="../jquery-1.7.1.min.js"></script>
<script type="text/javascript">
<!--
$(document).ready(function(){
	$("a.remove").live("click", function(e){
		e.preventDefault();
		if ( !confirm("此操作会删除此提醒。\n如果这是循环提醒，你将不会再收到相关通知。\n\n是否继续？") )
			return false;
		var query_id = $(this).attr("href");
		$.ajax({
			type: 'POST',
			url: 'alarma.php',
			data: 'action=remove&id=' + query_id,
			success: function(data){
				var json = eval('(' + data + ')');
				if (json.id)
					$("#a" + json.id).slideUp("normal", function(){$(this).remove()});
			},
			error: function() {
				window.alert("抱歉，出错了。请重试。");
			}
		});
	});
});
-->
</script>
</head>
<body>
<?php include_once("../ga.php") ?>
<div id="control">
<h3><span title="自 2012-4-12 12:00 开始统计至今，已经有饭友使用马闹钟设置了 <?php echo $alarma_count ?> 条提醒。">马闹钟提醒管理</span></h3>
<?php
echo $string['login_user'];
?>
<div id="notification">
<h4>你当前定制的提醒</h4>
<?php
$weekday = array('', '周一', '周二', '周三', '周四', '周五', '周六', '周日');
$notification_query = "SELECT * FROM `app_ffontime` WHERE user='$user'";
$notification = $mysql->getData( $notification_query );
$profile_query = "SELECT mobile FROM `app_ffbirthday2` WHERE id='$user'";
$profile = $mysql->getLine( $profile_query );
$mobile_number = $profile['mobile'];
if ( count($notification) ) {
	foreach ( $notification as $item ) {
		$id = $item['id'];
		$timestamp = $item['timestamp'];
		$recurring = $item['recurring'];
		$sequence = $item['sequence'];
		$message = $item['message'];
		$type = $item['type'];
		$postfix = '';
		$time_text = '';
		switch ( $type ) {
			case 'update':
				$type_text = '定时发送消息';
				break;
			case 'mention':
				$type_text = ' @ 消息提醒';
				break;
			case 'private':
				$type_text = '私信提醒';
				break;
			case 'sendsms':
				$type_text = '移动飞信(免费短信)提醒；绑定的手机号为 ' . $mobile_number;
				break;
			default:
				break;
		}
		switch ( $recurring ) {
			case -1:
				$recur_text = '单次提醒，预定时间为 ' . date('Y-m-d H:i', $timestamp);
				break;
			case 0:
				$recur_text = '每日提醒，预定时间为 ' . date('H:i', $timestamp);
				break;
			case 1:
				$recur_text = '每' . $weekday[$sequence] . '提醒，预定时间为 ' . date('H:i', $timestamp);
				break;
			case 2:
				$recur_text = '每月 ' . $sequence . ' 日提醒，预定时间为 ' . date('H:i', $timestamp);
				break;
			default:
				break;
		}
		echo '<div class="item" id="a' . $id . '"><div class="func"><a href="' . $id . '" class="remove">删除</a></div><div class="desc"><strong>消息内容</strong> ' . $message . '<br /><strong>定时计划</strong> ' . $recur_text . '<br /><strong>提醒方式</strong> 使用' . $type_text . '</div><div class="clearfix"></div></div>';
	}
}
else {
	echo '你还没有定制过提醒，或者定制的提醒都已经过期。<br />需要建立新提醒吗？<a href="http://fanfou.com/privatemsg.create/alarma" target="_blank">发送私信</a>给马闹钟即可';
	// echo '<div id="create"></div>';
}

$mysql->closeDb();
?>
</div>
<h4>添加新提醒</h4>
<div id="create">
要创建一个新提醒吗？<a href="http://fanfou.com/privatemsg.create/alarma" target="_blank">发送私信</a>给马闹钟即可。<br />
不知道写些什么？试试这些：
<ul>
<li title="普通@提醒">每天，11:40，按时吃午饭！</li>
<li title="第一个字母s表示私信提醒">s下周六，14点40分，收拾房间。</li>
<li title="第一个字母f表示飞信提醒">f2012-12-12，20点整，给小王打电话。</li>
</ul>
还不了解使用方法？聪明的你看一看<a href="http://www.mibuo.com/blog/post?id=137367" target="_blank">帮助文档</a>就会用啦！
</div>
<div id="footer">
</div>
</div>
</body>
</html>
