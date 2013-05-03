<?php
require_once('config.php');
require_once('oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) {
	$state = false;
	$o = new OAuth( FF_AKEY , FF_SKEY );
	$keys = $o -> getRequestToken();
	$tmp_token = $keys['oauth_token'];
	$tmp_secret = $keys['oauth_token_secret'];
	$aurl = $o -> getAuthorizeURL( $tmp_token, false, FF_CALLBACK);
	if ( isset( $_GET['login'] ) ) { // Login Page for UYAN
		header('Location: ' . $aurl);
	}
	SetCookie('tmp[0]', $tmp_token, time()+1800, '/', '');
	SetCookie('tmp[1]', $tmp_secret, time()+1800, '/', '');
	$string = '<p id="fanfou_oauth"><img src="ff.png" /> <strong><a href="' . $aurl . '">去饭否为应用授权</a></strong><br />授权后，你就可以使用以下应用。授权时会显示此应用名称“物以类聚”；<br />授权操作在饭否网站完成，你的账户密码是安全的。<br />“物以类聚”只会获取必要的信息，绝不会随意操作无关内容。';
}
else {
	$user = $_COOKIE['ffid'];
	$uid = $_COOKIE['ffuid'];
	
	$mysql = new SaeMysql();
	$mysql->setCharset('UTF8');
	$query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' and uid='$uid'";
	$data = $mysql->getData( $query );
	$token = $data[0]['oauth_token'];
	$secret = $data[0]['oauth_token_secret'];
	
	//检查oauth token状态
	$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
	$check_oauth = json_decode( $ff_user -> verify_credentials(), true );

	if ( !count($data) || $check_oauth['error'] ) {
		$state = false;
		$o = new OAuth( FF_AKEY , FF_SKEY );
		$keys = $o -> getRequestToken();
		$tmp_token = $keys['oauth_token'];
		$tmp_secret = $keys['oauth_token_secret'];
		$aurl = $o -> getAuthorizeURL( $tmp_token ,false , FF_CALLBACK);
		SetCookie('tmp[0]', $tmp_token, time()+1800, '/', '');
		SetCookie('tmp[1]', $tmp_secret, time()+1800, '/', '');
		$string = '<p id="fanfou_oauth"><img src="ff.png" /> <strong><a href="' . $aurl . '">去饭否为应用授权</a></strong><br />授权后，你就可以使用以下应用。授权时会显示此应用名称“物以类聚”；<br />授权操作在饭否网站完成，你的账户密码是安全的。<br />“物以类聚”只会获取必要的信息，绝不会随意操作无关内容。';
		}
	else {
		$state = true;
		$timestamp = time();
		$update_logon = "UPDATE `$table` set last_check='$timestamp' WHERE id='$user'";
		$mysql->runSql( $update_logon );
		$happy_bday = '';
		$data = $mysql->getLine( $query );
		$id = json_decode( $ff_user -> show_user(), true );
		$reg_time = strtotime( $id['created_at'] );
		$day_count = (int)((time()-$reg_time)/3600/24);
		$reg_date = date('m-d', $reg_time);
		$weekday = date('w', $reg_time);
		$now_date = date('m-d');
		if ( $reg_date == $now_date ) {
			$fanfou_birthday = date('Y') - date('Y', $reg_time);
			$happy_bday .= '<br /><img src="/birthday/ff-you.gif" alt="饭否生日快乐！" />撒花~ 祝你 ' . $fanfou_birthday . ' 岁饭否生日快乐！';
		}
		else if ( substr($id['birthday'], 5) == $now_date )
			$happy_bday .= '<br /><img src="/birthday/ff-you.gif" alt="生日快乐！" />撒花~ 祝你生日快乐！';
		$week = array('周日', '周一', '周二', '周三', '周四', '周五', '周六');
		$string = '<p id="fanfou_oauth"><img src="ff.png" /> <strong>欢迎回来！</strong><br />你已经登录为 <a href="http://fanfou.com/' . $id['id'] . '">' . $id['screen_name'] . '</a>；所有小工具都已为你准备就绪。<br />自从你在' . date(' Y 年 n 月 j 号', $reg_time) . '那个美好的' . $week[$weekday] . '加入饭否以来，已经过去 ' . $day_count . ' 天啦。' . $happy_bday . '<br /><a href="?logout=' . time() . '">退出登录</a>';
	}
	
	$mysql->closeDb();
}

if ( isset($_GET['logout']) ) {
	SetCookie("ffid", '', time(), '/', '');
	SetCookie("ffuid", '', time(), '/', '');
	setcookie('syncuyan', '', time(), '/', '');
	header('Location: /');
}

setcookie('syncuyan', '', time()-999, '/', '.marcher.sinaapp.com');

?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>饭否小工具 - 物以类聚</title>
<style type="text/css">
body {font: 12px Verdana, Arial, Helvetica, sans-serif; width: 90%;}
sup {color: red;}
h3 {line-height: 0.8em;}
.disabled {text-decoration:line-through; color: #8f8f8f;}
.tool-item {width: 325px; height: 90px; margin: 3px; padding: 6px 13px; float: left; cursor: pointer; overflow: hidden;}
.tool-item p {line-height: 1.7em;}
.odd {background: #def;}
.even {background: #eee;}
.clear {clear: both;}
#tool-list {list-style-type: none; margin: 0; padding: 0;}
#fanfou_oauth {border: 5px solid #def; width: 555px; margin: 15px 0; padding: 10px;}
#revoke-info {display: none;}
#footer {margin-top: 25px; font-size: 11px;}
#ff5 {position: absolute; top: 3px; left: 33%; width: 380px; text-align: center; padding: 6px; border: solid 1px #EEE; background: #CDF; -moz-border-radius: 5px; border-radius: 5px; -webkit-border-radius: 5px; -moz-box-shadow: 6px 6px 5px #EEE; -webkit-box-shadow: 6px 6px 5px #EEE; box-shadow: 6px 6px 5px #EEE;}
</style>
<script type="text/javascript" src="jquery-1.7.1.min.js"></script>
<script type="text/javascript">
<!--
$(document).ready(function(){
	$('.tool-item:odd').addClass('odd');
	$('.tool-item:even').addClass('even');
	$('#revoke').click(function(){
		$('#revoke-info').slideToggle();
	});
	$('li').click(function(){
		window.location.href = $(this).find('a:first').attr('href');
	});
});
-->
</script>
</head>
<body>
<?php include_once("ga.php") ?>
<h1>
<img alt="物以类聚·" title="物以类聚" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAMAAAAM7l6QAAAAA3NCSVQICAjb4U/gAAAApVBMVEUAlMSE0OUirtLg9Pm05PBFvNoApMxnx+CY2+v4/f4Qpc3N7fU5tdZTwd0AnsqT2Okxtdbp9/vC5/IQrc6r4O4Jpc0Am8f///91zuSM1uhRwNzV8PcIoMtixt8qs9Wl3u3J7PQfrNGU3uZCu9nv+fzl9vqE0+bc8vh7zuQpsNPH6vRvyuFZv9u85/JCtdae2OobqtBKvtuc3OtbxN6c3uYTn8przt52aX8sAAAACXBIWXMAAAsSAAALEgHS3X78AAAAH3RFWHRTb2Z0d2FyZQBNYWNyb21lZGlhIEZpcmV3b3JrcyA4tWjSeAAAAVJJREFUKJG9U9tygjAUDMVSQjUkCGehEpDh7mUEnf7/rxWtWnDavrX7kJnMJie7Z0+Y9SvY39Pihm/ppbxhSdNzZ1r0ADjnw/pyWJ+x951x8a6oRBAk6OPQTtP0YCKYSFuZnuetIYW2GBGZPk1oc6a1biAdxRdEEZ7ElPZ3u90WcwpS2BoGTX2v1OsAtGTRoNO7sVeaVMgsp4efs85GCUOMi0sXvDw7W4XhYC52sWnFl++3Mq103rVwch4FTAhd5GL8tuM4WfIcaW1X6brcU/Ag7da3ut740YkelCfyaX7U7xEWQ2km7eltosxQXLMKyFkQoWjHytuohmriOK7UEUYNIxgnZsnCz4l6ZTeFsMETds/9Og5M6Bp+VnGLtrwwsklbuj4FfDlkwQcd7QzYNKNxOKFsssEM2bgITVzljtsirYtTsXA/Lz1EcgdNt//xDX7EB02gLFadoTycAAAAAElFTkSuQmCC" />
饭否小工具
</h1>

<div id="ff5">
<!--<strong>饭否 5 岁饭友自曝活动。<a target="_blank" href="http://blog.fanfou.com/2012/05/16/5th-list/">查看中奖名单！</a></strong>-->
海外用户可以通过 <a href="http://fanfou.chen.ma/">fanfou.chen.ma</a> 访问饭否小工具。<br />如有任何疑问，欢迎随时<a href="#uyan_frame">留言</a>或者通过<a href="http://fanfou.com/privatemsg.create/marcher">私信吐槽</a>。
</div>

<?php
echo $string;
function echo_url($url) {
	global $state, $aurl;
	if ( $state ) return $url;
	else return $aurl;
}
?>

<h2 title="由“物以类聚”提供的饭否应用">应用列表</h2>
<ul id="tool-list">
<li class="tool-item">
<h3><a href="<?php echo echo_url('/birthday/');  ?>">饭友生日</a></h3>
<p>获取你所有关注的人以及关注你的人的生日。
</li>
<li class="tool-item">
<h3><a href="<?php echo echo_url('/alarma/'); ?>">马闹钟</a></h3>
<p>向@<a href="http://fanfou.com/alarma">马闹钟</a>发送私信一枚，如<span style="border: 1px solid #ccc; margin: 2px; padding: 4px;">每天，18:45, 按时吃饭</span>。时间到了，它会用不同方式提醒你。<a href="http://www.mibuo.com/blog/post?id=137367" style="cursor:help;">查看使用帮助</a>。
</li>
<li class="tool-item">
<h3><a href="<?php echo echo_url('/timeline_cleaner/'); ?>">时间线清理工具</a></h3>
<p>以多种方式查看你的时间线，轻松备份或者删除消息。现已支持即时查看你<a href="<?php echo echo_url('/timeline_cleaner/?timemachine=lastyear'); ?>">去年今日</a>、<a href="<?php echo echo_url('/timeline_cleaner/?timemachine=lastmonth'); ?>">上月今日</a>和<a href="<?php echo echo_url('/timeline_cleaner/?timemachine=lastweek'); ?>">上周今日</a>的饭否消息。
</li>
<li class="tool-item">
<h3><a href="<?php echo echo_url('/avatar/'); ?>">头像仓库</a></h3>
<p>统一管理你的多个饭否头像，一键更换。把自己制作好的头像上传，仓库会按时帮你更换。
</li>
<li class="tool-item">
<h3><a href="http://fanfou.com/ggtt101">五笔机器人</a></h3>
<p>使用五笔输入法？看到不会打的字句，可以顺手 <a href="http://fanfou.com/home?status=@%E4%BA%94%E7%AC%94+">@五笔</a> ，它会立刻告诉你如何拆字。
</li>
<li class="tool-item">
<h3><a href="<?php echo echo_url('/imhit/'); ?>">中枪提醒</a></h3>
<p>在饭否关注了一些感兴趣的话题……和自己的名字？别人提到你的时候，你可以让机器人发私信通知你！
</li>
</ul>
<div class="clear"></div>

<h2 title="其他第三方饭否应用">热乎应用</h2>
<ul id="tool-list">
<li class="tool-item">
<h3><a href="http://fanfou.com/mouse0424">饭否婚介所</a></h3>
<p>小拐子同学的饭否福利。单身男女欢迎提交资料。
</li>
<li class="tool-item">
<h3><a href="http://is.gd/sfanfou">太空饭否</a></h3>
<p>给饭否添加回复和转发展开、浮动输入框、多用户切换等功能，并使饭否页面的变得更赏心悦目。<a href="http://anegie.com/blog/spacefanfou/">使用手册</a>
</li>
<li class="tool-item">
<h3><a href="https://addons.opera.com/zh-cn/extensions/details/fantom/?display=zh">Fantom for Opera</a></h3>
<p>内置太空饭否，Fantom 为使用 Opera 的饭否提供了强大的浏览器扩展。
</li>
<li class="tool-item">
<h3><a href="http://is.gd/fanjoy">有饭同享</a></h3>
<p>超方便的饭否分享应用。在 Chrome 中右键点击文字和图片，或者直接按住右键拖拽即可完成。
</li>
<li class="tool-item">
<h3><a href="http://fanfou.com/statuses/oba1JNpxq04">微信饭否</a></h3>
<p>通过微信更新饭否。进入微信搜索 weifanfou 或者直接点击这里并扫描二维码即可添加微信饭否。<a href="http://www.douban.com/photos/album/83309056/">查看使用帮助</a>。
</li>
<li class="tool-item">
<h3><a href="http://imach.me/gohanapp">御饭 iOS</a></h3>
<p>御飯 iOS 适用于 iPad、iPhone 和 iPod touch，并使用 iCloud 在这三部设备之间同步你的 Timeline 阅读位置。
</li>
<li class="tool-item">
<h3><a href="https://itunes.apple.com/app/fan-lao/id541110403">饭唠 for iPhone</a></h3>
<p>适用于 iPhone 的小清新饭否客户端。
</li>
<li class="tool-item">
<h3><a href="http://www.windowsphone.com/en-us/store/app/%E7%88%B1%E9%A5%AD%E5%84%BF/65a3fee8-97ca-403f-a889-c0b402d049cd">爱饭儿</a></h3>
<p>爱饭儿是饭否Windows Phone平台专用的客户端，涵盖了饭否绝大部分的功能。
</li>
<li class="tool-item">
<h3><a href="http://bbfan.diandian.com/">莓吃饭</a></h3>
<p>饭否黑莓客户端。<br />界面简洁美观，浏览体验优于浏览器。
</li>
<li class="tool-item">
<h3><a href="https://github.com/fanfoudroid/fanfoudroid/blob/master/README.md">安能饭否</a></h3>
<p>安能饭否是一款开源的饭否 Android 客户端。拥有拍照/图片上传，后台自动提醒，桌面 Widget 等功能。
</li>
<li class="tool-item">
<h3><a href="http://fanfouer.sinaapp.com/">饭友</a></h3>
<p>饭否好友管理工具。<br />海外用户可<a href="http://fanyou.chen.ma/">点击这里</a>访问。
</li>
<li class="tool-item">
<h3><a href="http://www.aoisnow.net/blog/fanhe">饭盒</a></h3>
<p>饭盒是一个Windows下的饭否本地小工具集，提供消息备份功能。
</li>
<li class="tool-item">
<h3><a href="http://fq.vc/archives/180">饭否消息导出工具</a></h3>
<p>通过饭否API导出消息，支持导出为.csv、.xml、.html和.txt四种格式，导出的数据可读性更强。
</li>
<li class="tool-item">
<h3><a href="http://avatar.fanfouapps.com/">爱饭样式头像生成器</a></h3>
<p>选择一个字，选择几个样式，就可以生成你想要的饭否样式的头像。
</li>
<li class="tool-item">
<h3><a href="http://instafan.mogita.com/">InstaFan</a></h3>
<p>将你的 Instagram 新照片同步到饭否上。
</li>
<li class="tool-item">
<h3><a href="http://leeon.net/weixin/robot/">微波炉</a></h3>
<p>在微信中关注“微波炉”，然后就可以通过微信直接发送/查看饭否信息了！
</li>
<li class="tool-item">
<h3><a href="http://setf.sinaapp.com/">自定义 API 的饭否手机版</a></h3>
<p>到<a href="http://fanfou.com/apps">饭否 API</a> 申请一个独特的名称，你就可以发送来自诸如“小霸王学习机”、“金立语音王”的消息啦！
</li>
<li class="tool-item">
<h3><a href="http://fanfou.com/home?status=%40marcher+">推荐应用</a></h3>
<p>告诉我你喜欢的饭否应用，不限类型，让它出现在这里吧。
</li>
</ul>
<div class="clear"></div>

<!--<h2>有话要说</h2>

<div id="uyan_frame"></div>
<script type="text/javascript" id="UYScript" src="http://v1.uyan.cc/js/iframe.js?UYUserId=1725535" async=""></script>

-->

<p id="footer">编写及维护：@marcher<br />
个人页面：<a href="http://chen.ma/">物以类聚</a>；饭否页面：<a href="http://fanfou.com/marcher">@marcher</a>
<p class="quote" style="color: #555;"><strong>王兴：在饭否，每一条消息是平等的，没有主贴和跟贴的差别；另外，用户也是平等的，没有嘉宾和不是嘉宾的区别。</strong>
<p>Powered by <a href="http://sae.sina.com.cn/">Sina App Engine</a></p>

</body>
</html>