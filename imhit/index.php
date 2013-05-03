<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) {
	SetCookie('redir', '/imhit/', time()+1800, '/', '');
	header('Location: /');
}
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret, dm_freq FROM `$table` WHERE id='$user' AND uid='$uid'";
$data = $mysql->getData( $query );

if ( !count($data) )
	header('Location: /');

//$data = $mysql->getLine( $query );
$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
// get user token and secret

$dm_freq = ( $data[0]['dm_freq'] == -1 ) ? 3600 : $data[0]['dm_freq'];
// default value for dm frequency: -1 / 1hr

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$id = json_decode( $ff_user -> show_user(), true );
$search_list = json_decode( $ff_user -> get_saved_search(), true );

$string['login_user'] = '<p>你好哇，<a href="http://fanfou.com/' . $id['id'] . '">' . $id['screen_name'] . '</a>！';
$string['login_user'] .= ' | <a href="/?logout=' . time() . '">退出登录</a> | <a href="/">返回所有应用</a>';
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>中枪了！ - 物以类聚</title>
<link rel="stylesheet" href="../jslider.css" type="text/css" />
<style type="text/css">
body {font: 12px Verdana, Arial, Helvetica, sans-serif;}
strong {color: #233;}
h4 {margin: 14px 0;}
ul li {line-height: 1.6em;}
label {display: inline; padding: 0; margin: 8px 0;}
p.search_item {width: 600px; margin: 3px; padding: 5px;}
.add_dm {color: #0c0;}
.del_dm {color: #f00;}
.an strong {color: red;}
.an {display: none; width: 420px; padding: 8px; border: 2px solid #00f;}
.search_link {cursor: pointer; font-weight: bold;}
#searches {margin-top: 25px;}
#freq_slider {padding: 30px 0;  width: 400px;}
#freq_result {padding: 5px 0; width: 200px;}
#tip {margin-top: 20px;}
#top_bar {position: fixed; top: 2px; left: 30%; z-index: 3000; width: 430px; text-align: center; padding: 6px; background: #CDF; -moz-border-radius: 5px; border-radius: 5px; -webkit-border-radius: 5px; -moz-box-shadow: 6px 6px 5px #def; -webkit-box-shadow: 6px 6px 5px #def; box-shadow: 6px 6px 5px #def;}
</style>
<script type="text/javascript" src="../jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="../jshashtable-2.1_src.js"></script>
<script type="text/javascript" src="../jquery.numberformatter-1.2.3.js"></script>
<script type="text/javascript" src="../tmpl.js"></script>
<script type="text/javascript" src="../jquery.dependClass-0.1.js"></script>
<script type="text/javascript" src="../draggable-0.1.js"></script>
<script type="text/javascript" src="../jquery.slider.js"></script>
<script type="text/javascript">
<!--
var search_queries = new Array(<?php
$list_size = count($search_list);
foreach ( $search_list as $key => $search_item ) {
	if ( $j == $list_size - 1 ) echo $search_item['id'];
	else echo $search_item['id'] . ', ';
	$j++;
}
?>);
function in_array(value){
    var i;
    for (i=0; i < search_queries.length; i++){
        if (search_queries[i] == value){
            return true;
        }
    }
    return false;
}
function highlight(element){
	var target = $("#" + element);
	if(target.length > 0){
		var originalColor = target.css('backgroundColor');
		var changeColor = 'yellow';
		target.css({backgroundColor:changeColor, opacity: 0}).animate({
			opacity: 1
		},500,function(){$(this).animate({
			opacity: 0.25
		},500,function(){
			$(this).css({backgroundColor:originalColor, opacity: 1})
		});
		});
	}
}
$(document).ready(function(){
	$("#create").click(function(){
		$(this).select();
	});
	$("#create").keydown(function(e){
		if (e.keyCode == 13){
			if ( $("#match").attr("checked") ) {
				var user_query = "\"" + $("#create").val().replace(/\|/g, "\"|\"") + "\"";
			}
			else {
				var user_query = $("#create").val();
			}
			$.ajax({
				type: 'POST',
				url: 'query.php',
				data: 'action=create&query=' + user_query,
				success: function(data){
					var json = eval('(' + data + ')');
					if (json.id && !in_array(json.id)) {
						$("#searches").append('<p class="search_item" id="q' + json.id + '"> [ <a href="' + json.id + '" class="add_dm" id="d' + json.id + '">添加提醒</a> | <a href="' + json.id + '" class="rm_query">删除话题</a> ] <strong>' + json.query + '</strong></p>');
						search_queries.push(json.id);
					}
					highlight('q' + json.id);
				},
				error: function(){
					window.alert("抱歉，出错了。请重试。");
				}
			});
			$("#create").blur();
			
		}
	});
	$("a.add_dm").live("click", function(e){
		e.preventDefault();
		var query_id = $(this).attr("href");
		var element_id = $(this).attr("id");
		$.ajax({
			type: 'POST',
			url: 'query.php',
			data: 'action=add_dm&query_id=' + query_id,
			success: function(data){
				var json = eval('(' + data + ')');
				if (json.id){
					$("a#" + element_id).attr("class", "del_dm");
					$("a#" + element_id).text("取消提醒");
				}
			},
			error: function(){
				window.alert("抱歉，出错了。请重试。");
			}
		});
	});
	$("a.del_dm").live("click", function(e){
		e.preventDefault();
		var query_id = $(this).attr("href");
		var element_id = $(this).attr("id");
		$.ajax({
			type: 'POST',
			url: 'query.php',
			data: 'action=del_dm&query_id=' + query_id,
			success: function(data){
				var json = eval('(' + data + ')');
				if (json.id){
					$("a#" + element_id).attr("class", "add_dm");
					$("a#" + element_id).text("添加提醒");
				}
			},
			error: function() {
				window.alert("抱歉，出错了。请重试。");
			}
		});
	});
	$("a.rm_query").live("click", function(e){
		e.preventDefault();
		if ( !confirm("此话题将被从你关注的话题中删除。\n\n是否继续？") )
			return false;
		var query_id = $(this).attr("href");
		$.ajax({
			type: 'POST',
			url: 'query.php',
			data: 'action=remove&query_id=' + query_id,
			success: function(data){
				var json = eval('(' + data + ')');
				if (json.id)
					$("#q" + json.id).slideUp("normal", function(){$(this).remove()});
			},
			error: function() {
				window.alert("抱歉，出错了。请重试。");
			}
		});
	});
	$("#freq_bar").slider({
		from: 5,
		to: 360,
		step: 5,
		dimension: ' 分钟',
		scale: ['5', '|', '75', '|', '145', '|', '220', '|', '290', '|', '360'],
		callback: function(value) {
			$.ajax({
				type: 'POST',
				url: 'query.php',
				data: 'action=set_freq&freq_val=' + value,
				success: function(data){
					var json = eval('(' + data + ')');
					if (json.id){
						$("#freq_val").text(value);
						highlight("freq_result");
					}
				},
				error: function() {
					window.alert("抱歉，出错了。请重试。");
				}
			});
		}
	});
});
-->
</script>
</head>
<body>
<?php include_once("../ga.php") ?>
<div id="top_bar">
使用时遇到了问题？请<a href="http://fanfou.com/home?status=%40marcher+" target="_blank">留言</a>或者通过<a href="http://fanfou.com/privatemsg.create/marcher" target="_blank">私信反馈</a>问题。谢谢！
</div>
<div id="control">
<h3>中枪了！</h3>
<p class="an"><strong>抱歉！</strong>“中枪！”应用已经下线，您在这里的设置将不再有效。感谢您的使用。</p>
<?php
echo $string['login_user'];
?>
<div id="searches">
<h4>你当前关注的话题</h4>
<?php
if ( !count($search_list) )
	echo '还没有关注的话题，可以从这里添加一个！';
foreach ( $search_list as $key => $search_item ) {
	$query_id = $search_item['id'];
	$check_query = "SELECT query_id FROM `$hit_table` WHERE id='$user' and query_id='$query_id'";
	$check_array = $mysql->getData( $check_query );
	if ( count($check_array) )
		$dm_string = '<a href="' . $query_id . '" class="del_dm" id="d' . $query_id . '">取消提醒</a>';
	else
		$dm_string = '<a href="' . $query_id . '" class="add_dm" id="d' . $query_id . '">添加提醒</a>';
	echo '<p class="search_item" id="q' . $query_id . '"> [ ' . $dm_string . ' | <a href="' . $query_id . '" class="rm_query">删除话题</a> ] <strong class="search_link" title="点击前往饭否查看搜索结果" onclick="window.open(\'http://fanfou.com/home#search?q=' . urlencode($search_item['query']) . '\')">' . $search_item['query'] . '</strong></p>';
}
?>
</div>
<div id="new_search">
<input name="create" id="create" type="text" size="35" value="输入新的关注话题，按回车确认。" /> <input name="match" id="match" type="checkbox" /><label for="match" title="精确匹配你输入的词组。比如填写“饭否应用”，则不会提醒你仅包含“饭否”或“应用”关键词之一的消息。">精确匹配词组</label>
</div>
<h4>接收私信的频率</h4>
<div id="freq_slider"><input id="freq_bar" type="slider" name="freq_bar" value="<?php echo $dm_freq / 60; ?>" /></div>
<div id="freq_result">私信提醒的频率已设置为 <span id="freq_val"><?php echo $dm_freq / 60; ?></span> 分钟。</div>
<div id="tip">
<strong>提示：</strong>
<ul><li>
通过“添加提醒”，机器人会在有人提到你关注的话题时私信提醒你。不想再关注了，“取消提醒”，或者直接“删除话题”即可。
</li><li>
目前可以自动提醒你的好友与其他未设置关注者可见的人是否提到了你关注的话题。
</li><li>
默认情况下，私信提醒最多每小时发送一次；如果这仍然对你造成困扰，可以随时来这里更改提醒频率。
</li><li>
要关注几个相关的关键字，可以用“|”分隔，如：自曝|照片|秒删。
</li><li>
如果要得到某个特定词组的提醒，请选中“精确匹配词组”。
</li></ul>
</div>
</body>
</html>
