<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');
require_once('parser.php');
require_once('PHPFetion.php');

ini_set('max_execution_time', 0);
set_time_limit(0);

header("Content-type: text/html; charset=utf-8");

function random($length) {
	$hash = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz-_';
	$max = strlen($chars) - 1;
	mt_srand( (double)microtime() * 1000000 );
	for ( $i = 0; $i < $length; $i++ )
		$hash .= $chars[mt_rand(0, $max)];
	return $hash;
}

$ff = new FFClient( FF_AKEY_ALARMA , FF_SKEY_ALARMA , OAUTH_TOKEN , OAUTH_TOKEN_SECRET );
$c = new SaeCounter();
$mysql = new SaeMysql();
$mysql->setCharset('UTF8');

////////////////////////////
/// read direct messages ///
////////////////////////////

$fetion_request = array();
$fetion_test = array();
$weekday = array('', '周一', '周二', '周三', '周四', '周五', '周六', '周日');
$dm_array = json_decode( $ff -> list_dm(), true );

foreach ( $dm_array as $key => $dm_body ) {
	$id = $dm_body['id'];
	$user = $dm_body['sender_id'];
	$body = $dm_body['text'];
	$message_array = json_decode( time_parser($body), true );
	$postfix = '';
	$checkuser_query = "SELECT * FROM `app_ffbirthday2` WHERE id='$user'";
	$checkuser = $mysql->getData( $checkuser_query );
	
	if ( $message_array['status'] == 'success' && $message_array['action'] == 'mobile' ) {
		if ( $message_array['type'] == 'cmcc' ) {
			$reply_dm_message = '恭喜，手机号已绑定！如果这是首次绑定，请接受马闹钟的好友申请。（换号了？重新绑定新手机号即可。要解除绑定，回复私信【b0】）';
			$mobile_phone = $message_array['mobile'];
			array_push($fetion_request, $mobile_phone);
			if ( !count($checkuser) ) {
				$uid = random(10);
				$insert_query = "INSERT INTO `app_ffbirthday2` (id, uid, mobile) VALUES ('$user', '$uid', '$mobile_phone')";
				$mysql->runSql( $insert_query );
			}
			else {
				$update_query = "UPDATE `app_ffbirthday2` SET mobile='$mobile_phone' WHERE id='$user'";
				$mysql->runSql( $update_query );
			}
		}
		else if ( $message_array['type'] == 'unreg' ) {
			$reply_dm_message = '恭喜，手机号已经解除绑定！如果要重新绑定，发送【b你的手机号】即可。如果你还有未到期的飞信提醒，我会改用私信通知你。';
			$mobile_phone = '';
			$update_query = "UPDATE `app_ffbirthday2` SET mobile='$mobile_phone' WHERE id='$user'";
			$mysql->runSql( $update_query );
		}
		else {
			$reply_dm_message = '抱歉，你是不是输错了手机号？飞信提醒只能供中国移动手机用户使用。如果你使用的确实是中国移动号码，请私信 @marcher 告知，谢谢！';
			$count_err++;
		}
		
		$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
		$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
		$ff -> delete_dm( $id ); // del received dm
		$count_mob++;
	}
	
	if ( $message_array['status'] == 'success' && $message_array['action'] == 'add' ) {
		$timestamp = $message_array['timestamp'];
		$message = urldecode($message_array['message']);
		$type = $message_array['type'];
		$recurring = $message_array['recurring'];
		$sequence = $message_array['sequence'];

		if ( $type == 'private' ) {
			$notification_type = '悄悄私信你';
			$count_dm++;
		}
		else if ( $type == 'sendsms' ) {
			$mobile_query = "SELECT mobile FROM `app_ffbirthday2` WHERE id='$user'";
			$mobile_result = $mysql->getLine( $mobile_query );
			if ( $mobile_result['mobile'] == '' ) {
				$reply_dm_message = '提醒设置失败，因为你没有绑定手机号。请回复私信【b你的手机号】进行绑定。';
				$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
				$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
				$ff -> delete_dm( $id ); // del received dm
				$count_err++;
				break 3;
			}
			$notification_type = '发飞信给你';
			$postfix = '（已绑定' . $mobile_result['mobile'] . '。换号了？发送私信【b新号码】重新绑定）';
			$count_fx++;
		}
		else if ( $type == 'update' ) {
			$uid_query = "SELECT uid FROM `app_ffbirthday2` WHERE id='$user'";
			$user_info = $mysql->getLine( $uid_query );
			if ( $user_info['uid'] ) {
				$count_query = "SELECT timestamp FROM `app_ffontime` WHERE user='$user' AND type='update'";
				$update_array = $mysql->getData( $count_query );
				if ( count($update_array) < 3 ) {
					$notification_type = '向你的TL发送消息';
					$count_status++;
				}
				else {
					$reply_dm_message = '定时消息设置失败。定时消息会发布到你的个人时间线上，为避免打扰其他饭友，每人只能同时设置三条定时消息。（其他形式的提醒功能不受条数限制）';
					$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
					$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
					$ff -> delete_dm( $id ); // del received dm
					$count_err++;
					break 4;
				}
			}
			else {
				$reply_dm_message = '提醒设置失败，发送定时消息需要应用授权。前往http://chen.ma/alarma/，点击【去饭否为应用授权】，然后再重新设置按时消息。';
				$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
				$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
				$ff -> delete_dm( $id ); // del received dm
				$count_err++;
				break 3;
			}
		}
		else {
			$notification_type = '@你';
			$count_at++;
		}
		
		$save_query = "INSERT INTO `app_ffontime` (id, user, timestamp, recurring, sequence, message, type) VALUES ('$id', '$user', '$timestamp', '$recurring', '$sequence', '$message', '$type')";
		$mysql->runSql( $save_query );
		if ( $mysql->affectedRows() ) {
			switch ( $recurring ) {
				case 0: $recurring_type = '每天' . date('H:i', $timestamp); break;
				case 1: $recurring_type = '每' . $weekday[$sequence] . date('H:i', $timestamp); break;
				case 2: $recurring_type = '每月' . $sequence . '日 ' . date('H:i', $timestamp); break;
				default: $recurring_type = date('Y-m-d H:i', $timestamp);
			}
			$reply_dm_message = '亲，你的提醒我已收到～我会在' . $recurring_type . '时' . $notification_type . '的~（回复【#' . $id . '】取消此提醒；到http://chen.ma/alarma/管理所有提醒）' . $postfix;
			$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
			$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
			$ff -> delete_dm( $id ); // del received dm
                        $c->incr('alarma_count');
		}
		else {
			$reply_dm_message = '抱歉，添加提醒时出错了！麻烦告诉@marcher一声，谢谢 :)';
			$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
			$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
			$ff -> delete_dm( $id ); // del received dm
			$count_err++;
		}
	}
	else if ( $message_array['status'] == 'success' && $message_array['action'] == 'delete' ) {
		$message_id = $message_array['message'];
		$message_query = "SELECT timestamp, recurring, sequence, message FROM `app_ffontime` WHERE id='$message_id'";
		$message_info = $mysql->getLine( $message_query );
		$delete_query = "DELETE FROM `app_ffontime` WHERE id='$message_id' AND user='$user'";
		$mysql->runSql( $delete_query );
		if ( $mysql->affectedRows() ) {
			switch ( $message_info['recurring'] ) {
				case 0:
					$recurring_type = '每天';
					break;
				case 1:
					$index = $message_info['sequence'];
					$recurring_type = '每' . $weekday[$index];
					break;
				case 2:
					$recurring_type = '每月' . $message_info['sequence'] . '日';
					break;
				default:
					$recurring_type = '';
			}
			$reply_dm_message = '原定' . $recurring_type . ' ' . date('H:i', $message_info['timestamp']) . '的提醒“' . substr($message_info['message'], 0, 15) . '……”已经删除了。欢迎下次使用～';
			$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
			$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
			$ff -> delete_dm( $id ); // del received dm
		}
		else {
			$reply_dm_message = '哎，删除提醒时出错了！可能没有正确回复删除信息？可能提醒已经被你删除过？为了安全，只能使用设置提醒的饭否账号进行取消操作。';
			$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
			$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
			$ff -> delete_dm( $id ); // del received dm
			$count_err++;
		}
	}
	else if ( $message_array['status'] == 'error' ) {
		$reply_dm_message = '我没懂哦…要像这样：“2012-03-04,9:30,写作业”、“明天，10:00，去图书馆”。日期和时间请用阿拉伯数字。写好了私信我哟~ 有不明白的地方可以看http://is.gd/alarma/。';
		$sent_dm = json_decode( $ff -> send_dm( $user, $reply_dm_message, $id ), true );
		$timestamp = time();
		$query = "INSERT INTO `app_alarma` (user, timestamp, body) VALUES ('$user', '$timestamp', '$body')";
		$mysql->runSql($query);
		$ff -> delete_dm( $sent_dm['id'] ); // del sent dm
		$ff -> delete_dm( $id ); // del received dm
		$count_err++;
	}
}

$mysql->closeDb();

if ( count( $fetion_request ) ) {
	$fetion = new PHPFetion(FETION_MOBILE, FETION_PASSWORD);
	foreach ( $fetion_request as $key => $mobile ) {
		$fetion->addFriend($mobile, '马闹钟');
		sleep(2);
	}
}

echo 'at:'.$count_at.',dm:'.$count_dm.',fx:'.$count_fx.',err:'.$count_err.',mob:'.$count_mob++;;

?>