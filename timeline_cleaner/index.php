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

// get batch count, if exist
if ( isset($_COOKIE['ffbatch']) && $_COOKIE['ffbatch'] > 0 && !fmod($_COOKIE['ffbatch'], 50) )
	$batch = $_COOKIE['ffbatch'];
else
	$batch = 50;

$c = new SaeCounter;
$status_count = $c->get('cleaner_count');

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' and uid='$uid'";
$data = $mysql->getData( $query );

if ( !count($data) )
	header('Location: /');

// param "timemachine" is specified
$today_of_last_year = date('Y-m-d' , strtotime('-1 year'));
$today_of_last_month = date('Y-m-d' , strtotime('-1 month'));
$today_of_last_week = date('Y-m-d' , strtotime('-1 week'));
switch ( $_GET['timemachine'] ) {
	case 'lastyear':
		header('Location: /timeline_cleaner/?timeline=timerange&since=' . $today_of_last_year . '&until=' . $today_of_last_year);
		break;
	case 'lastmonth':
		header('Location: /timeline_cleaner/?timeline=timerange&since=' . $today_of_last_month . '&until=' . $today_of_last_month);
		break;
	case 'lastweek':
		header('Location: /timeline_cleaner/?timeline=timerange&since=' . $today_of_last_week . '&until=' . $today_of_last_week);
		break;
	default:
		break;
}

$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
// get user token and secret

$timestamp = time();
$update_logon = "UPDATE `$table` set last_check='$timestamp' WHERE id='$user'";
$mysql->runSql( $update_logon );

ini_set('max_execution_time', 0);
set_time_limit(0);

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$id = json_decode( $ff_user -> show_user(), true );
if ($id['status']['id']=='') $lastest_status = '';
else $lastest_status = $id['status']['id'];

// Calculating total message pages
$message_count = $id['statuses_count'];
if ( $message_count > 0 ) {
	$total_page = ceil( $message_count / 50 );
	$message_array = json_decode( $ff_user -> user_timeline($total_page, 50) );
	if ( count($message_array) ) {
		$first_status = $message_array[count($message_array)-1]->id;
	}
	else {
		do {
			$total_page -= 1;
			$message_array = json_decode( $ff_user -> user_timeline($total_page, 50) );
		}
		while (!count($message_array));
		$first_status = $message_array[count($message_array)-1]->id;
	}
	$message_total = count($message_array) + ($total_page - 1) * 50;
}
else {
	$total_page = 0;
	$message_total = 0;
	$first_status = null;
}

// Viewing other users' message
$fanfou_id = '';
$current_id = '我的';
if ( isset($_GET['id']) ) {
	$fanfou_id = '&id=' . $_GET['id'];
	$current_id = '<a href="http://fanfou.com/' . $_GET['id'] . '" target="_blank">' . $_GET['id'] . '</a>的';
}

// Checking if TL is reversed
if ( isset($_GET['timeline']) )
	$reversed = false;
else if ( $_GET['reversed'] == 'yes' )
	$reversed = 'yes';

// Checking if calendar is needed
if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['since']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['until']) && $_GET['timeline'] == 'timerange' ) {
	$calendar = '显示<strong>指定时间段</strong>内的消息：<form name="range_selector" action="" method="get"><input type="hidden" name="timeline" value="timerange" /><input type="text" class="date_picker" name="since" id="since" value="' . $_GET['since'] . '" readonly />至<input type="text" class="date_picker" name="until" id="until" value="' . $_GET['until'] . '" readonly /><input type="submit" value="确定" /></form> | ';
}
else {
	$calendar = false;
}

// Displaying stats
$reg_time = strtotime( $id['created_at'] );
$day_count = (int)((time() - $reg_time) / 3600 / 24);
$active_day = ( $reg_time < 1246982400 ) ? $day_count - 505 : $day_count;
$down_time = ( $reg_time < 1246982400 ) ? '（平均值未计算饭否离开的 505 天）' : '';
$avg_count = ( $message_total / $active_day < 1 ) ? '不足一条消息' : ' ' . number_format($message_total / $active_day, 2) . ' 条消息';

$string['login_user'] = '你好哇，<a href="http://fanfou.com/' . $id['id'] . '">' . $id['screen_name'] . '</a>！ | ';

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>时间线清理工具 - 献给 timeline 洁癖的饭友们 - 物以类聚</title>
<style type="text/css">
body {font: 12px Verdana, Arial, Helvetica, sans-serif; margin-top: 100px;}
label {width: 750px; height: 1.6em; display: block; overflow: hidden;  white-space: nowrap; margin: 2px 0; padding: 2px;}
sup {color: red;}
img {border: 0;}
form {margin: 0; display: inline;}
#loading {display: none;}
#tooltip {position: absolute; z-index: 3000; border: 1px solid #111; background-color: #eee; padding: 5px;}
#tooltip h3, #tooltip div {margin: 0;}
#limit {font-weight: bold; display: inline-block; width: 26px; text-align: center;}
#control {width: 100%; position: fixed; top: 0; _position: absolute; _top: 0; z-index: 1; background: #FFF;}
#total_count {font-weight: bold;}
#top_bar {position: fixed; top: 2px; left: 360px; z-index: 3000; width: 420px; text-align: center; padding: 6px; background: #CDF; -moz-border-radius: 5px; border-radius: 5px; -webkit-border-radius: 5px; -moz-box-shadow: 6px 6px 5px #def; -webkit-box-shadow: 6px 6px 5px #def; box-shadow: 6px 6px 5px #def;}
.limit_c {font-weight: bold; cursor: pointer;}
<?php if ( !isset($_GET['monochrome']) ) { ?>.re {background: #9CC;}
.rr {background: #FC6;}
.ot {background: #FCF;}
.am {background: #FEC;}
<?php } else echo '.re, .rr, .ot, .am, '; ?>.me {background: #CCC;}
.timestamp {display: none;}
.month_tag {font-size: 230%; color: #BBB; position: absolute; left: 770px;}
.control_line {margin: 7px 0; padding: 0; white-space: nowrap;}
.date_picker {width: 70px;}
</style>
<link rel="stylesheet" href="../datePicker.css" type="text/css" />
<link rel="stylesheet" href="../jquery.jCallout.css" type="text/css" />
<script type="text/javascript" src="../jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="../jquery.livequery.min.js"></script>
<script type="text/javascript" src="../jquery.tooltip.min.js"></script>
<script type="text/javascript" src="../jquery.cookie.js"></script>
<script type="text/javascript" src="../date.min.js"></script>
<script type="text/javascript" src="../jquery.datePicker.min.js"></script>
<script type="text/javascript" src="../jquery.jCallout.min.js"></script>
<script type="text/javascript" src="../jquery.contextmenu.min.js"></script>
<script type="text/javascript">
<!--
var lastChecked = null;
var firstStatus = '<?php echo $first_status; ?>';
var lastStatus = '<?php echo $lastest_status; ?>';

<?php
if ( $reversed == 'yes' ) echo 'var page = ' . $total_page . ';';
else echo 'var page = 1;
var totalPage = ' . $total_page . ';
';
?>
Date.format = 'yyyy-mm-dd';
$(document).ready(function(){
	$.ajaxSetup({
		timeout: 20000
	});
	$('#since').datePicker(
		{
			clickInput: true,
			startDate: '<?php echo date('Y-m-d', strtotime($id['created_at'])); ?>',
			endDate: '<?php echo date('Y-m-d'); ?>'
		}
	).bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var c = new Date($('#until').attr('value'));
			var d = new Date(selectedDates[0]);
			if (c < d) {
				d = new Date(d);
				$('#until').attr('value', d.asString());
				$('#until').dpSetSelected(d.asString());
			}
		}
	);
	$('#until').datePicker(
		{
			clickInput: true,
			startDate: '<?php echo date('Y-m-d', strtotime($id['created_at'])); ?>',
			endDate: '<?php echo date('Y-m-d'); ?>'
		}
	).bind(
		'dpClosed',
		function(e, selectedDates)
		{
			var c = new Date($('#since').attr('value'));
			var d = selectedDates[0];
			if (d < c) {
				d = new Date(d);
				$('#since').attr('value', d.asString());
				$('#since').dpSetSelected(d.asString());
			}
		}
	);
<?php if ( !in_array($_GET['timeline'], array('dmr', 'dms')) ) { ?>	$('label').livequery(function(){
		$(this).contextMenu('post_status', {
			bindings: {
				'op': function(t) {
					window.open('http://fanfou.com/statuses/'+t.getAttribute('id').substr(6));
				},
				're': function(t) {
					window.open('http://fanfou.com/home?status=%40'+encodeURIComponent(t.getAttribute('screen_name'))+'%20&in_reply_to_status_id='+t.getAttribute('id').substr(6));
				},
				'fv': function(t) {
					window.open('http://fanfou.com/favorite.add/'+t.getAttribute('id').substr(6));
				},
				'rt': function(t) {
					if ( (t.getAttribute('screen_name') == '<?php echo $id['screen_name']; ?>') ? true : (t.getAttribute('protected') == 'true') ? confirm('此饭友的消息设置了仅对关注者可见。\n请确认消息中是否存在隐私信息；建议直接回复，而不是转发。\n\n如果您转发了此消息，可能会有更多的人看到它，请谨慎决定。是否继续？') : true )
						window.open('http://fanfou.com/home?status=%20%E8%BD%AC%40'+encodeURIComponent(t.getAttribute('screen_name'))+' '+encodeURIComponent(t.getAttribute('status'))+'&repost_status_id='+t.getAttribute('id').substr(6));
				}
			}
		});
	});<?php } ?>
	$("#anim").click(function(){
		if ($(this).attr("class")=="anim_on") {
			$("label").live("mouseenter", function(){
				$(this).animate({width:"100%"});
			});
			$("label").live("mouseleave",function(){
				$(this).animate({width:"750px"});
			});
			$(this).attr("class", "anim_off").attr("style", "font-weight:bold;");
		}
		else {
			$("label").die("mouseenter");
			$("label").die("mouseleave");
			$(this).attr("class", "anim_on").attr("style", "");
		}
	});
	$('span, a').livequery(function(){
		$(this).tooltip({
			track: true,
			delay: 0,
			showURL: false,
			showBody: " - "
		});
	});
	$('a.photo_sign').livequery(function(){
		$(this).tooltip({
			track: true,
			delay: 0,
			showURL: false,
			bodyHandler: function() {
				return $("<img/>").attr("src", this.href.replace("n0","s0")); // Thumbnail preview
			}
		});
	});
	$("#more").click(function(){
		$("#more").css("display", "none");
		$("#loading").css("display", "inline");
		var t = Date.parse(new Date());
		var limit = $("#limit").text();
		var pcount = limit / 50;
<?php
switch ( $_GET['timeline'] ) {
	case 'photo':
		$timeline_type = '&type=photo';
		$exp_postfix = 'photo';
		$tl_title = $current_id . '照片[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'fav':
		$timeline_type = '&type=fav';
		$exp_postfix = 'favorite';
		$tl_title = $current_id . '收藏[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'mention':
		$timeline_type = '&type=mention';
		$exp_postfix = 'mention';
		$tl_title = '提到我的消息[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'dmr':
		$timeline_type = '&type=dmr';
		$exp_postfix = 'dmr';
		$tl_title = '我收到的私信[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'dms':
		$timeline_type = '&type=dms';
		$exp_postfix = 'dms';
		$tl_title = '我发出的私信[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'current':
		$timeline_type = '&type=current&max_id=" + lastStatus + "';
		$exp_postfix = 'current';
		$tl_title = $current_id . '实时饭否[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'search':
		$timeline_type = '&type=search&query=' . $_GET['query'] . '&max_id=" + lastStatus + "';
		$exp_postfix = 'search';
		$tl_title = '搜索' . $current_id . '消息[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	case 'timerange':
		$timeline_type = '&type=timerange&since=' . $_GET['since'] . '&until=' . $_GET['until'] . '&max_id=" + lastStatus + "';
		$exp_postfix = 'timerange';
		$tl_title = '指定时间段内' . $current_id . '消息[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		break;
	default:
		if ( !$reversed ) {
			$timeline_type = '&max_id=" + lastStatus + "';
			if (isset($_GET['id'])) $timeline_type = '';
			$exp_postfix = 'timeline';
			$tl_title = $current_id . '消息[<a href="#" onclick="javascript:$(\'#status\').empty();$(\'#more\').click();">刷新</a>]';
		}
		else {
			$timeline_type = '';
			$exp_postfix = 'timeline_reversed';
			$tl_title = $current_id . '逆序饭否消息';
		}
}
switch ( $reversed ) {
	case 'yes':
?>
		if (page < 1) {
			window.alert("已经没有更多消息可以显示。");
			$("#loading").css("display", "none");
			$("#more").css("display", "inline");
			return;
		}
		$.getJSON("message.php?page="+page+"&pcount="+pcount+"<?php echo $timeline_type; ?>&order=reversed<?php echo $fanfou_id; ?>&t="+t, function(result){
<?php
		break;
	default:
?>
		var lastStatus = $(":checkbox:last").attr("value");
		if (lastStatus == firstStatus) {
			window.alert("已经没有更多消息可以显示。");
			$("#loading").css("display", "none");
			$("#more").css("display", "inline");
			return;
		}
		$.getJSON("message.php?page="+page+"&pcount="+pcount+"<?php echo $timeline_type . $fanfou_id; ?>&t="+t, function(result){
<?php
		break;
}
$dm_context = '';
if ( in_array($_GET['timeline'], array('mention', 'fav', 'current')) )
	$message_author = " + message.user.screen_name + ' (' + message.user.id + ') 说: '";
else if ( $_GET['timeline'] == 'dmr' )
	$message_author = " + message.sender_screen_name + ' (' + message.sender_id + ') 对我说: '";
else if ( $_GET['timeline'] == 'dms' )
	$message_author = " + '我对 ' + message.recipient_screen_name + ' (' + message.recipient_id + ') 说: '";
else
	$message_author = "";
?>
			if (!result.length) {
				window.alert("已经没有更多消息可以显示。");
				$("#loading").css("display", "none");
				$("#more").css("display", "inline");
				return;
			}
			$.each(result, function(i, message){
				var photo_sign = '';
				var photo_url = '';
				var context = '';
				if (message.photo) {
					photo_sign = '<a href="' + message.photo.largeurl + '" class="photo_sign" target="_blank"><img src="ff-img.png" /></a> ';
					photo_url = ' ' + message.photo.largeurl;
				}
				var location_sign = '';
				if (/^(-)?\d+\.\d+\,(-)?\d+\.\d+$/.test(message.location)) {
					location_sign = '<a href="http://ditu.google.cn/maps?q=' + message.location + '" class="location_sign" title="包含精确的地理位置 - 地理位置：' + message.location + '。 - 发送此消息时的地理位置信息可以精确定位到街区；点击查看地图。" target="_blank"><img src="ff-loc.png" /></a> ';
				}
				if (message.in_reply_to_user_id<?php if ( in_array($_GET['timeline'], array('dmr', 'dms')) ) echo ' || message.recipient_id == \'' . $user . '\''; ?>)
					var type = "re";
				else if (message.repost_user_id)
					var type = "rr";
				else
					var type = "me";
				if (message.in_reply_to)
					var context = ' - 私信上文：' + message.in_reply_to.sender_screen_name + ' (' + message.in_reply_to.sender_id + ') 对 ' + message.in_reply_to.recipient_screen_name + ' (' + message.in_reply_to.recipient_id + ') 说: ' + message.in_reply_to.text;
				var t0 = new Date(message.created_at<?php if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) { ?>.replace("+0000", "UTC+0000")<?php } ?>); // IE
				var timestamp = t0.toLocaleString();
				var content = message.text.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");
				var month_str = t0.getFullYear() + ' 年 ' + (t0.getMonth()+1) + ' 月';
				var month_tag = '<span class="month_tag">' + month_str + '</span>';
				if ($(".month_tag:last").text() == month_str)
					month_tag = '';
				$("#status").append(month_tag + '<label class="' + type + '" id="label_' + message.id + '"<?php if ( !in_array($_GET['timeline'], array('dmr', 'dms')) ) { ?> screen_name="' + message.user.screen_name + '" status="' + message.text.replace(/<[\/]*[a-b][^>]*>/ig,'') + '" protected="' + message.user.protected + '"<?php } ?>><input type="checkbox" class="' + type + '" name="status[]" value="' + message.id + '" id="checkbox_' + message.id + '" />' + location_sign + photo_sign + '<span title="消息详情 / 右键点击操作消息 - 消息内容：' + content + context + ' - 发送时间：' + timestamp + '" class="message" <?php if ( !in_array($_GET['timeline'], array('dmr', 'dms')) ) { ?>ondblclick="window.open(\'http://fanfou.com/statuses/' + message.id + '\');" <?php } ?>id="status_' + message.id + '">'<?php echo $message_author; ?> + message.text + photo_url + '</span><span class="timestamp">' + message.created_at + '</span></label>');
			});
			$("#loading").css("display", "none");
			$("#more").css("display", "inline");
		});
<?php if ( $reversed == 'yes' ) { ?>
		page -= pcount;
<?php } else { ?>
		page += pcount;
<?php } ?>
	});
	$("#delete").click(function(){
		var t = Date.parse(new Date());
		var c = $(":checked").length;
		if (c == 0) {
			window.alert("没有任何选中的消息。请重新选择。");
			return false;
		}
		if (!confirm('<?php echo $del_prom; ?>确认要删除所有选中的 ' + c + ' 条消息么？\n\n删除前，建议你点击“取消”，并使用“导出选中的消息”功能对消息进行备份！\n（只支持消息备份，照片请手动备份！）\n\n如果此时仍然继续，所有选中的消息将从饭否删除，并且无法还原！'))
			return false;
		else {
			$(":checked").each(function(){
				$.ajax({
					type: "POST",
					url: "message.php?action=destroy<?php echo $timeline_type; ?>&t="+t,
					data: 'status=' + $(this).val(),
					success: function(ret) {
						var destroyedMessage = eval('(' + ret + ')');
						$("#checkbox_" + destroyedMessage[0]).removeAttr("checked");
						$("#label_" + destroyedMessage[0]).slideUp("normal", function() {$(this).remove()});
						$(".selected_count").text($(":checked").length);
					}
				});
			});
		}
	});
	$("#limit_p").live("click", function(){
		var limit = parseInt($("#limit").text());
		if (limit < 500)
			var batch = limit + 50;
		else
			var batch = 50;
		$("#limit").text( batch );
		$.cookie('ffbatch', batch, { expires: 10, path: '/' });
	});
	$("#limit_m").live("click", function(){
		var limit = parseInt($("#limit").text());
		if (limit > 50)
			var batch = limit - 50;
		else
			var batch = 500;
		$("#limit").text( batch );
		$.cookie('ffbatch', batch, { expires: 10, path: '/' });
	});
	$("#check_re").click(function(){
		$(":checkbox[class='re']").each(function(){
			$(this).attr("checked",!($(this).attr("checked")));
		});
		$(".selected_count").text($(":checked").length);
	});
	$("#check_rr").click(function(){
		$(":checkbox[class='rr']").each(function(){
			$(this).attr("checked",!($(this).attr("checked")));
		});
		$(".selected_count").text($(":checked").length);
	});
	$("#check_none").click(function(){
		$(":checkbox").each(function(){
			$(this).removeAttr("checked");
		});
		$(".selected_count").text($(":checked").length);
	});
	$("#check_rall").click(function(){
		$(":checkbox").each(function(){
			$(this).attr("checked",!($(this).attr("checked")));
		});
		$(".selected_count").text($(":checked").length);
	});
	$("#check_all").click(function(){
		$(":checkbox").each(function(){
			$(this).attr("checked","checked");
		});
		$(".selected_count").text($(":checked").length);
	});
	$(":checkbox").live("click", function(event){
		if(!lastChecked){
			lastChecked = this;
			return;
		}
		if(event.shiftKey){
			var start = $(":checkbox").index(this);
			var end = $(":checkbox").index(lastChecked);
			$(":checkbox").slice(Math.min(start, end), Math.max(start, end)+ 1).attr('checked', lastChecked.checked);
		}
		lastChecked = this;
		window.getSelection ? window.getSelection().removeAllRanges() : document.selection.empty();
		$(".selected_count").text($(":checked").length);<?php echo $del_pay; ?>
	});
	$("#export").click(function(){
		if ($(":checked").length == 0) {
			window.alert("没有任何选中的消息可以导出。\n请选择要导出的消息，然后重试。");
			return false;
		}
		else if ($(":checked").length >= 500)
			if (!confirm('注意！\n\n你选中了超过 500 条消息。\n导出大量消息可能会导致你的浏览器反应缓慢，这是正常的。 \n\n你可以考虑分批次导出消息，\n这样可以保证导出的文件完整有效。\n\n是否确认继续导出？'))
				return false;
		var exp_status = new Array();
		var exp_timestamp = new Array();
		$(":checked").each(function(){
			var thisIndex = $(":checkbox").index(this);
			var c0 = $(".message:eq(" + thisIndex + ")").text();
			var c1 = $(".timestamp:eq(" + thisIndex + ")").text();
			var c2 = new Date(c1<?php if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) { ?>.replace("+0000", "UTC+0000")<?php } ?>);
			c1 = c2.toLocaleString();
			c0 = encodeURIComponent(c0);
			exp_status.push(c0);
			exp_timestamp.push(c1);
		});
		$("#export").append('<form target="_blank" name="exportmsg" id="exportmsg" action="export.php" method="post"><input name="status" type="hidden" value="' + exp_status + '" /><input name="timestamp" type="hidden" value="' + exp_timestamp + '" /><input name="exp" type="hidden" value="<?php echo $exp_postfix; ?>" /></form>');
		$("#exportmsg").submit().remove();
	});
	$("#timeline").click(function(){
<?php
if ( !$reversed && isset($_GET['timeline']) ) $option_item = '<option value="?">正序显示消息</option><option value="?reversed=yes">逆序显示消息</option>';
else if ( $reversed == 'yes' || isset($_GET['timeline']) ) $option_item = '<option value="?">正序显示消息</option>';
else $option_item = '<option value="?reversed=yes">逆序显示消息</option>';
?>
		var dropdown = '<select name="timeline_changer" onchange="javascript:var a=this.options[this.selectedIndex].value;if(a){window.location.href=a;this.disabled=true;return true;}var b=prompt(\'请输入搜索内容：\',\'\');if(b){window.location.href=\'?timeline=search&query=\'+b;this.disabled=true;return true;}this.options[0].selected=true;return false;"><option value="?timeline=default" disabled selected>&gt;选择时间线</option><?php echo $option_item; ?><option value="?timeline=photo">我发布的照片</option><option value="?timeline=fav">我收藏的消息</option><option value="?timeline=mention">提到我的消息</option><option value="?timeline=dmr">我收到的私信</option><option value="?timeline=dms">我发送的私信</option><option value="?timeline=current">饭否实时消息</option><option value="">搜索我的饭否</option><option value="?timeline=timerange&since=<?php echo $today_of_last_year; ?>&until=<?php echo $today_of_last_year; ?>">回到去年今日</option></select>';
		$("#timeline_dropdown").html(dropdown);
		$(this).remove();
	});
});
-->
</script>
</head>
<body onload="jQuery('#more').click();">
<?php include_once("../ga.php") ?>
<?php
$topMsg = '当前显示的是<strong>' . $tl_title . '</strong>。 | <a href="http://fanfou.com/home?status=%40marcher+" target="_blank">留言</a>或者通过<a href="http://fanfou.com/privatemsg.create/marcher" target="_blank">私信反馈</a>问题';
?>
<div id="top_bar"><?php echo $topMsg; ?></div>
<div id="control">
<h3><span title="时间线清理工具：不止清理，更有多种功能 - 可以借助多种批量选择方式，快速方便地导出消息备份并删除饭否消息。支持多种方式查看时间线。 - 自 2012-4-12 12:00 开始统计至今，已经累计帮助各位饭友清理了 <?php echo $status_count; ?> 条消息。">时间线清理工具</span></h3>
<div class="control_line"><?php echo $string['login_user']; ?><a id="anim" class="anim_on" title="切换动画 - 打开动画后，鼠标移到消息上时，相应消息会自动拉长。" href="javascript:void(0);" >切换动画</a> | <span title="我的饭否消息统计 - 饭否显示的消息数为 <?php echo $message_count; ?> 条，经计算真实消息数为 <?php echo $message_total; ?> 条。 - 由于饭否隐藏了通知等系统消息，导致消息数相差了 <?php echo abs($message_count - $message_total); ?> 条。 - 自注册以来的 <?php echo $day_count; ?> 天里，平均每天我发送了<?php echo $avg_count . $down_time; ?>。" id="total_message">消息总数 <span id="total_count"><?php echo $message_total; ?></span> 条</span> | <?php if ($calendar) echo $calendar; ?><span id="timeline"><a title="显示其他类型的消息 - 点击这里，显示其他类型消息的时间线。" href="javascript:void(0);"><strong>选择时间线</strong></a><sup>新</sup></span><span id="timeline_dropdown"></span> | <a title="点击查看详细帮助信息 - · 调整每次加载的消息数量，点击“加载更多消息”开始查看消息。 - · 选择第一条消息，按住 Shift，选择最后一条，两条之间的消息会全选。 - · 通过点击上方消息类型批量选择。 - · 点击“显示更多消息”查看你的更多饭否消息类型。 - · 双击消息，可以直接在饭否页面打开。右键点击消息，可以选择回复/收藏/转发消息。 - · 想阅读别人的消息？在地址栏添加“?id=对方ID”。 - · 彩色消息太刺眼？在地址栏添加“?monochrome=1”。" href="http://www.mibuo.com/blog/post?id=142021" target="_blank">帮助</a> | <a href="/?logout=<?php echo time(); ?>">退出登录</a> | <a href="/">返回所有应用</a></div>
<div class="control_line"><span title="每次加载消息的数量 - 可选范围为 50~500 条。 - 选择的加载数量越多，加载时速度可能会越慢。请耐心等候。">每次加载<span class="limit_c" id="limit_m"><a href="javascript:void(0);" style="text-decoration: none;"> &#x25C0; </a></span><span id="limit"><?php echo $batch; ?></span><span class="limit_c" id="limit_p"><a href="javascript:void(0);" style="text-decoration: none;"> &#x25B6; </a></span> 条消息</span> | <span id="more"><a href="javascript:void(0);" title="继续载入 - 载入后续消息。可先调整载入消息的数量。"><strong>加载更多消息</strong></a></span><span id="loading"><img style="height: 12px; width: 13px; border: 0;" title="加载中" alt="加载中" src="ff-loading.gif" /><strong>加载消息...</strong></span> | <span id="check_re"><a href="javascript:void(0);" title="回复的消息 - 选择所有/反选我回复给别人的消息">选择<strong>回复</strong>的消息</a></span> | <span id="check_rr"><a href="javascript:void(0);" title="转发的消息 - 选择所有/反选我转发自别人的消息">选择<strong>转发</strong>的消息</a></span> | <span id="check_all"><a href="javascript:void(0);" title="选择所有 - 选择当前已经载入的所有消息。">选择所有</a></span> | <span id="check_rall"><a href="javascript:void(0);" title="反向选择 - 取消选中的消息，并选中其他消息。">反向选择</a></span> | <span id="check_none"><a href="javascript:void(0);" title="取消所有 - 取消所有选择的消息">取消所有</a></span> | <span id="export"><a href="javascript:void(0);" title="留下回忆，时不再来！ - 删除选中的饭否消息前，请考虑备份它们！ - （注意！上传的照片文件需要手动进行备份！）" style="font-weight: bold;">备份消息</a></span>(<span class="selected_count">0</span>)<?php if ( !in_array($_GET['timeline'], array('mention', 'current')) && !isset($_GET['id']) ) { ?> | <span id="delete"><a href="javascript:void(0);" title="删除选中的所有消息 - 删除的消息将无法还原，建议在删除前使用导出功能备份" style="font-weight: bold; color: red;">删除消息</a></span>(<span class="selected_count" id="del_count">0</span>)<?php } ?></div>
</div>
<noscript><h2>...等等！你的浏览器没有开启 JavaScript！</h2><div>不管你知不知道什么是 JavaScript，但是你的浏览器必须启用它才能继续使用这个小工具。<a href="https://support.google.com/adsense/bin/answer.py?hl=zh-CN&answer=12654" target="_blank">了解如何开始 JavaScript</a></div></noscript>
<div id="status"></div>
<?php if ( !in_array($_GET['timeline'], array('dmr', 'dms')) ) { ?><div class="contextMenu" id="post_status"><ul><li id="op"><img src="ff-open.png" alt="RE" /> 打开消息</li><li id="re"><img src="ff-reply.png" alt="RE" /> 回复消息</li><li id="fv"><img src="ff-fav.png" alt="FAV" /> 收藏消息</li><li id="rt"><img src="ff-repost.png" alt="RT" /> 转发消息</li></ul></div><?php } ?>
</body>
</html>
