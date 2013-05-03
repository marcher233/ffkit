<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) {
	SetCookie('redir', '/avatar/', time()+1800, '/', '');
	header('Location: /');
}
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret, avatar_quota, avatar_filesize FROM `$table` WHERE id='$user' AND uid='$uid' LIMIT 1";
$data = $mysql->getData( $query );

if ( !count($data) )
	header('Location: /');

$timestamp = time();
$update_logon = "UPDATE `$table` set last_check='$timestamp' WHERE id='$user'";
$mysql->runSql( $update_logon );

$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
// get user token and secret

switch ( $_COOKIE['retcode'] ) {
	case 1:
		$information = '<div id="informational">操作成功 :)</div>';
		break;
	case 10:
		$information = '<div id="warning"><span id="warning_title">添加头像时出错。</span>你的头像仓库已满。</div>';
		break;
	case 11:
		$information = '<div id="warning"><span id="warning_title">添加头像时出错。</span>请确认文件格式是否为 PNG, JPG 或 GIF。</div>';
		break;
	case 12:
		$information = '<div id="warning"><span id="warning_title">添加头像时出错。</span>请重试。</div>';
		break;
	case 13:
		$information = '<div id="warning"><span id="warning_title">添加头像时出错。</span>你上传的文件过大。</div>';
		break;
	case 2:
		$information = '<div id="warning"><span id="warning_title">设置出错。</span>你的头像更换计划存在冲突，每天只能更换一次头像 :(</div>';
		break;
	case 20:
		$information = '<div id="informational">操作成功 :)<br /><strong>请注意</strong>，对于设置在同一天的每周和指定日期的任务，指定日期的任务有更高的优先级。</div>';
		break;
	case 3:
		$information = '<div id="warning"><span id="warning_title">操作出错！</span>请重试。</div>';
		break;
	case 4:
		$information = '<div id="warning"><span id="warning_title">下载头像时出错。</span>请重试。</div>';
		break;
	case 50:
		$information = '<div id="warning"><span id="warning_title">设置生日头像时出错。</span>你在饭否填写的生日无效，开启失败。</div>';
		break;
	default:
		break;
}
SetCookie('retcode', '', time());

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$id = json_decode( $ff_user -> show_user(), true );
$birthday = $id['birthday'];
$avatar_url = str_replace( '/s0/', '/l0/', $id['profile_image_url'] );

$string['login_user'] = '<p>你好哇，<a href="http://fanfou.com/' . $id['id'] . '">' . $id['screen_name'] . '</a>！';
$string['login_user'] .= ' | <a href="http://fanfou.com/home?status=%40marcher+" target="_blank">留言</a>或者通过<a href="http://fanfou.com/privatemsg.create/marcher" target="_blank">私信反馈</a>问题 | <a href="/?logout=' . time() . '">退出登录</a> | <a href="/">返回所有应用</a>';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="pragma" content="no-cache" />
<title>头像仓库 - 物以类聚</title>
<link rel="stylesheet" href="../datePicker.css" type="text/css" />
<link rel="stylesheet" href="../nivo-zoom.css" type="text/css" media="screen" />
<style type="text/css">
body {font: 12px Verdana, Arial, Helvetica, sans-serif;}
h4 {margin: 20px 0;}
sup {color: red;}
img {border: 0;}
ul {margin-left: -15px;}
#uploader {margin: 8px 0;}
#downloader {margin: 8px 0;}
#add_new, #tweak {list-style-type: square;}
#list_all {list-style-type: none;}
#current_avatar {padding: 12px; text-align: center; position: absolute; top: 15px; right: 15px; font-weight: bold; border: 1px solid #def;}
#informational {width: 450px; margin: 10px 0; padding: 8px; border: 2px solid #9de;}
#warning {width: 450px; margin: 10px 0; padding: 8px; border: 2px solid #fc0;}
#warning_title {font-weight: bold; color: red;}
#footer {margin-top: 35px; font: 11px;}
#top_bar {position: fixed; top: 2px; left: 25%; z-index: 3000; width: 530px; text-align: center; padding: 6px; background: #CDF; -moz-border-radius: 5px; border-radius: 5px; -webkit-border-radius: 5px; -moz-box-shadow: 6px 6px 5px #def; -webkit-box-shadow: 6px 6px 5px #def; box-shadow: 6px 6px 5px #def;}
.avatar_preview {width: 68px; height: 85px; margin: 0; overflow: hidden;}
.avatar_image {width: 68px; border: 0;}
.uploaded {width: 260px; height: 85px; margin: 1px; padding: 5px; float: left;}
.scheduled {color: red;}
.set_date, .show_weekly {display: none;}
.avatar {width: 70px; float: left;}
.option {width: 180px; float: right; line-height: 1.1em;}
.clear {clear: both;}
.odd {background: #def;}
.even {background: #eee;}
.date_picker {font-weight: bold; border: 0; background: transparent; width: 75px;}
.nivoLarge {-moz-box-shadow: 6px 6px 5px #bbb; -webkit-box-shadow: 6px 6px 5px #bbb; box-shadow: 6px 6px 5px #bbb;}
</style>
<script type="text/javascript" src="../jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="../date.js"></script>
<script type="text/javascript" src="../jquery.datePicker.min.js"></script>
<script type="text/javascript" src="../jquery.nivo.zoom.pack.js"></script>
<script type="text/javascript">
<!--
$(window).load(function() {
	$('body').nivoZoom();
});
$(document).ready(function(){
	$('.date_picker').datePicker({clickInput:true});
	$('.uploaded:odd').addClass('odd');
	$('.uploaded:even').addClass('even');
	$('.show_schedule').click(function(e){
		e.preventDefault();
		var cid = $(this).attr('href');
		$('#'+cid).slideDown();
		$(this.parentElement).slideUp();
	});
	$('.confirm_schedule').click(function(e){
		e.preventDefault();
		$(this).closest("form").submit();
	});
	$('.set_recurring').click(function(e){
		e.preventDefault();
		var cid = $(this).attr('href');
		var r = $('#r'+cid).val();
		switch (r) {
			case 'd':
				$(this).text('设定指定日期更换');
				$('#d'+cid).slideUp();
				$('#w'+cid).slideDown();
				$('#r'+cid).val('w');
				break;
			case 'w':
				$(this).text('设定每周自动更换');
				$('#w'+cid).slideUp();
				$('#d'+cid).slideDown();
				$('#r'+cid).val('d');
				break;
		}
	});
	$('.submit_btn').click(function(){
		$(this).attr("value", "请稍等...");
		$(this).closest("form").submit();
		$('.submit_btn').attr("disabled", "disabled");
	});
});
-->
</script>
</head>
<body>
<?php include_once("../ga.php") ?>
<?php
$avatar_query = "SELECT count(1) AS count FROM `$as_table` WHERE id='$user' AND tweak=''";
$avatar_count = $mysql->getLine( $avatar_query );
$avatar_query = "SELECT count(1) AS count FROM `$as_table` WHERE tweak=''";
$avatar_total_count = $mysql->getLine( $avatar_query );
?>
<div id="top_bar">
使用时遇到了问题？请<a href="http://fanfou.com/privatemsg.create/marcher" target="_blank">告诉我发生了什么</a>。谢谢！<br />当前空间限额 <?php echo $data[0]['avatar_quota']; ?> 个，已使用 <?php echo $avatar_count['count']; ?> 个；单个头像大小限制 <?php echo $data[0]['avatar_filesize']/1024; ?>KB。
</div>
<div id="current_avatar"><p>你当前的饭否头像<p><img src="<?php echo $avatar_url; ?>" /></p></div>
<div id="main">
<h3 title="截至目前共托管着 <?php echo $avatar_total_count['count']; ?> 个头像文件！">头像仓库<sup>测试</sup></h3>

<?php
echo $string['login_user'];

if ( $data[0]['avatar_quota'] == 0 )
	die('<h4><strong>抱歉！<strong>“头像仓库”还在开发中，并没有对所有人开放，请过些天再来看看。</h4></div></body></html>');

echo $information;
?>

<h4>添加你的新头像</h4>
<ul id="add_new">
<li>
<p class="add_avatar">从你的电脑<strong>上传图片</strong> (支持 JPG, PNG, GIF 格式)；
<form id="uploader" action="avatar.php?action=upload" method="POST" enctype="multipart/form-data">
<input type="file" name="avatar" />
<input type="submit" value="上传" class="submit_btn" />
</form>
</li>
<li>
或者，输入图片网址来直接获取<strong>网络上的图片</strong>；
<form id="downloader" action="avatar.php?action=download" method="POST">
<input type="text" name="url" size="35" value="http://" title="输入图片的网址" />
<input type="submit" value="获取" class="submit_btn" />
</form>
</li>
<li>
也可以<a href="avatar.php?action=backup" onclick="if(!confirm('是否要备份你现在的饭否头像？'))return false;"><strong>备份正在使用的头像</strong></a>。
</li>
</ul>

<h4>已经上传的头像</h4>
<?php
$query = "SELECT stor.aid aid, stor.avatar avatar, sche.schedule schedule, sche.recurring recurring, stor.tweak tweak FROM `$aschedule_table` sche RIGHT JOIN `$as_table` stor ON sche.aid = stor.aid WHERE stor.id = '$user' ORDER BY stor.aid DESC";
$avatars = $mysql->getData( $query );

if ( !count($avatars) ) {
	echo '<p class="none_uploaded">你还没有向仓库里上传过头像。<br />赶快动手吧！</p>';
}
else {
	echo '<ul id="list_all">';
	foreach ( $avatars as $key => $avatar ) {
		$aid = $avatar['aid'];
		$url = $avatar['avatar'];
		$time = $avatar['schedule'];
		$recurring = $avatar['recurring'];
		if ( !$time ) {
			$schedule_form = '<form action="avatar.php?action=schedule&aid=' . $aid . '" method="POST" name="s' . $aid . '">';
			$schedule_form .= '<p><a class="show_schedule" href="a' . $aid . '">定时更换此头像</a>?<br /></p><p class="set_date" id="a' . $aid . '">';
			$schedule_form .= '<span id="d' . $aid . '">在 <input type="text" readonly name="date" class="date_picker" value="' . date('Y-m-d', strtotime('tomorrow')) . '" />自动更换。</span>';
			$schedule_form .= '<span class="show_weekly" id="w' . $aid . '">在每周<select name="weekday"><option value="1">一</option><option value="2">二</option><option value="3">三</option><option value="4">四</option><option value="5">五</option><option value="6">六</option><option value="0">日</option></select>自动更换。</span>';
			$schedule_form .= '<input type="hidden" id="r' . $aid . '" name="recurring" value="d" /><br />';
			$schedule_form .= '<strong><a href="' . $aid . '" class="confirm_schedule">保存设置</a></strong>或<strong><a href="' . $aid . '" class="set_recurring">设定每周自动更换</a></strong></p></form>';
		}
		else {
			switch ( $recurring ) {
				case 'd':
					$date_string = date('Y-m-d', $time);
					break;
				case 'w':
					$weekday_string = array('日', '一', '二', '三', '四', '五', '六');
					$date_string = '每周' . $weekday_string[date('w', $time)];
					break;
			}
			$schedule_form = '<p><span class="scheduled">已经定于 ' . $date_string . ' 自动更换此头像。</span><br />';
			$schedule_form .= '需要<strong><a href="avatar.php?action=cancel&aid=' . $aid . '">取消此定时计划</a></strong>?';
		}
		if ( $avatar['tweak'] )
			echo '<li class="uploaded"><div class="avatar"><a href="' . $url . '" class="nivoZoom"><div class="avatar_preview"><img src="' . $url . '" class="avatar_image" /></div></a></div><div class="option">' . $schedule_form . '<p><a href="http://www.picself.cn/service/?ClientName=1001016&ClientFrom=sae&UploadType=common&ImageFileName=avatar&UploadUrl=http://marcher.sinaapp.com/avatar/avatar.php?source=picself%26action=upload&PicUrl=' . $url . '" onclick="if(!confirm(\'你将前往 PicSelf 对头像进行丰富的美化操作。\n修改完成后请点击上方的“保存与分享”将美化的头像上传回头像仓库。\n\n此功能需要 Flash 支持。是否继续？\'))return false;"><strong>编辑</strong>头像</a> | <a href="avatar.php?action=set&aid=' . $aid . '" onclick="if(!confirm(\'是否把这个图片立即设置为饭否头像？\'))return false;"><strong>设置</strong>头像</a> | <span title="这是应用生成的自动备份文件，不会占用你的仓库空间。">自动备份</a></p></div><div class="clear"></div></li>';
		else
			echo '<li class="uploaded"><div class="avatar"><a href="' . $url . '" class="nivoZoom"><div class="avatar_preview"><img src="' . $url . '" class="avatar_image" /></div></a></div><div class="option">' . $schedule_form . '<p><a href="http://www.picself.cn/service/?ClientName=1001016&ClientFrom=sae&UploadType=common&ImageFileName=avatar&UploadUrl=http://marcher.sinaapp.com/avatar/avatar.php?source=picself%26action=upload&PicUrl=' . $url . '" onclick="if(!confirm(\'你将前往 PicSelf 对头像进行丰富的美化操作。\n修改完成后请点击上方的“保存与分享”将美化的头像上传回头像仓库。\n\n此功能需要 Flash 支持。是否继续？\'))return false;"><strong>编辑</strong>头像</a> | <a href="avatar.php?action=set&aid=' . $aid . '" onclick="if(!confirm(\'是否把这个图片立即设置为饭否头像？\'))return false;"><strong>设置</strong>头像</a> | <a href="avatar.php?action=delete&aid=' . $aid . '" onclick="if(!confirm(\'是否要把这个图片从仓库里删除？\'))return false;"><strong>删除</strong>头像</a></p></div><div class="clear"></div></li>';
	}
	echo '</ul><div class="clear"></div>';
}
?>

<h4>头像特殊效果</h4>
<ul id="tweak">
<li>
<?php
$query = "SELECT birthday FROM `$ab_table` WHERE id='$user' LIMIT 1";
$check_user = $mysql->getData( $query );
if ( count($check_user) ) {
	echo '<p class="scheduled">已设置生日头像功能!</p>';
	echo '<p>在 ' . substr($check_user[0]['birthday'],0,2) . ' 月 ' . substr($check_user[0]['birthday'],3,2) . ' 日，你的头像会自动成为生日头像！次日将为你还原头像。<br />之前的生日有误？请在饭否更新生日后，取消此功能再重新开启即可。</p>';
	echo '<p><a href="avatar.php?tweak=bday&a=disable" onclick="javascript:if(!confirm(\'如果今天恰好是你的生日，你的头像在取消后会自动恢复到之前的样子。\n你确定要取消生日头像功能吗？\'))return false;">要取消生日头像功能吗</a>?';
}
else {
	if ( (int)substr($birthday,5,2) && (int)substr($birthday,8,2) ) {
		$month = substr($birthday,5,2);
		$day = substr($birthday,8,2);
		echo '<p><a href="avatar.php?tweak=bday&a=enable">开启生日头像功能</a>';
		echo '<p>在 ' . $month . ' 月 ' . $day . ' 日，你的头像会自动成为生日头像！次日将为你还原头像。</p>';
	}
	else {
		echo '<p>饭否生日头像</p>';
		echo '<p>在你的生日当天，为你生成一个生日头像。因为你没有在饭否<a href="http://fanfou.com/settings">设置生日</a>，所以本功能不可用。</p>';
	}
}
echo '<p><img src="birthday.png" style="border:0;" title="生日头像效果示例" /></p>';
?>
</li>
<li>
<p><a href="http://avatar.fanfouapps.com/" target="_blank">爱饭样式头像生成器</a>
<p>生成爱饭样式的饭否头像，直接作为你的头像使用。
<p><img src="http://avatar4.fanfou.com/l0/00/b4/fm.jpg" style="border:0;" title="效果示例" /></p>
</li>
<li>
敬请期待...
</li>
</ul>
<h4>免责声明</h4>
<p class="info">据<a href="http://fanfou.com/home#q=频繁%20头像">人民群众反映</a>，频繁地更换饭否头像可能引起他人不适，导致不可预知的后果。<br />对此引起的任何问题，本工具不负任何责任。</p>
</div>
<div id="footer">
<hr />
<p>特别感谢 @<a href="http://fanfou.com/mogita">mogita</a> 的技术支持 :)<br />推荐给 Instagram 玩家的饭否同步应用: <a href="http://instafan.mogita.com/">instaFan</a></p>
</div>
</body>
</html>
<?php
$mysql->closeDb();
?>