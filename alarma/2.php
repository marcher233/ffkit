<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');
//require_once('parser.php');
require_once('PHPFetion.php');

ini_set('max_execution_time', 0);
set_time_limit(0);

header("Content-type: text/html; charset=utf-8");

/////////////////////////
/// send notification ///
/////////////////////////

$ff = new FFClient( FF_AKEY_ALARMA , FF_SKEY_ALARMA , OAUTH_TOKEN , OAUTH_TOKEN_SECRET );

$now = time();
$now_time = date('H:i');
$now_weekday = date('N');
$now_day = date('j');
$weekday = array('', '周一', '周二', '周三', '周四', '周五', '周六', '周日');
$fetion_msg = array();
$emoji = array('^_^', '\(^o^)/', '(=^_^=)', '(￣3￣)', ' ˙ε ˙', ' ˙ 3˙', '￣ω￣', '(′ェ`)', '(′∀`=)', '(￣▽￣)', '<(￣︶￣)>', ' ˇε ˇ', ' ˇ 3ˇ', '~(￣▽￣)~*', '(*¯︶¯*)', '(/≧▽≦)/');
// attach an emoji to retry message if duplicate 

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$ontime_query = "SELECT * FROM `app_ffontime` WHERE timestamp<='$now' OR retry=1";
$ontime_message = $mysql->getData( $ontime_query );
// filter out on_time and out_of_schedule messages

if ( count($ontime_message) ) {

	foreach ( $ontime_message as $ontime ) {
	
		$id = $ontime['id'];
		$user = $ontime['user'];
		$timestamp = $ontime['timestamp'];
		$message = $ontime['message'];
		$postfix = '';
		$screen_name = '';
		$on_schedule = true;
		$retry_emoji = '';
		$retry_name = 2; // get screen_name another time if failed
		
		if ( $ontime['retry'] ) {
			$on_schedule = false;
			$retry_emoji = $emoji[mt_rand(0, count($emoji) - 1)];
		}
		
		if ( $on_schedule ) {
			switch ( $ontime['recurring'] ) {
				case 0:
					if ( date('H:i', $timestamp) != $now_time )
						continue 2;
					else
						break;
				case 1:
					if ( date('H:i', $timestamp) != $now_time || $ontime['sequence'] != $now_weekday )
						continue 2;
					else
						break;
				case 2:
					if ( date('H:i', $timestamp) != $now_time || $ontime['sequence'] != $now_day )
						continue 2;
					else
						break;
				default:
					break;
			}
		}
		
		if ( $ontime['recurring'] != -1 )
			$postfix = '（这是循环提醒，要取消，请回复私信【#' . $id . '】）';
		
		if ( $ontime['type'] == 'private' ) {
			$updateinfo = json_decode( $ff -> send_dm( $user, '马闹钟提醒你：' . $message . '。别忘记啦！' . $postfix, $id ), true );
			if ( $updateinfo['id'] ) {
				$ff -> delete_dm( $updateinfo['id'] ); // del sent dm
				if ( $ontime['recurring'] == -1 ) {
					$delete_query = "DELETE FROM `app_ffontime` WHERE id='$id'";
					$mysql->runSql( $delete_query );
				}
				else {
					$update_query = "UPDATE `app_ffontime` SET retry=0 WHERE id='$id'";
					$mysql->runSql( $update_query );
				}
				$count_dm++;
			}
			else {
				$update_query = "UPDATE `app_ffontime` SET retry=1 WHERE id='$id'";
				$mysql->runSql( $update_query );
			}
		}
		else if ( $ontime['type'] == 'sendsms' ) {
			$check_query = "SELECT mobile FROM `app_ffbirthday2` WHERE id='$user'";
			$mobile_result = $mysql->getLine( $check_query );
			$mobile = $mobile_result['mobile'];
			if ( $mobile != '' ) {
				$fetion_msg[$mobile] = $message;
                          //$updateinfo = json_decode( $ff -> send_dm( $user, '你使用马闹钟设置了飞信短信提醒：' . $message . '。为防止飞信提醒发送失败，此私信供参考之用。', $id ), true );
                          //$ff -> delete_dm( $updateinfo['id'] ); // del sent dm
			}
			else {
				$updateinfo = json_decode( $ff -> send_dm( $user, '马闹钟提醒你：' . $message . '。别忘记啦！（由于你取消了飞信绑定，此提醒只会通过私信发送）', $id ), true );
				$ff -> delete_dm( $updateinfo['id'] ); // del sent dm
			}
			if ( $ontime['recurring'] == -1 )
				$mysql->runSql("DELETE FROM `app_ffontime` WHERE id='$id'");
			else
				$mysql->runSql("UPDATE `app_ffontime` SET retry=0 WHERE id='$id'");
			$count_fx++;
		}
		else if ( $ontime['type'] == 'update' ) {
			$oauth_query = "SELECT oauth_token, oauth_token_secret FROM `app_ffbirthday2` WHERE id='$user'";
			$user_info = $mysql->getLine( $oauth_query );
			$token = $user_info['oauth_token'];
			$secret = $user_info['oauth_token_secret'];
			$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
			$updateinfo = json_decode( $ff_user -> update( $message . $retry_emoji ), true );
			if ( $updateinfo['id'] ) {
				if ( $ontime['recurring'] == -1 )
					$mysql->runSql("DELETE FROM `app_ffontime` WHERE id='$id'");
				else
					$mysql->runSql("UPDATE `app_ffontime` SET retry=0 WHERE id='$id'");
				$count_status++;
			}
			else {
				$on_schedule = false;
				$mysql->runSql("UPDATE `app_ffontime` SET retry=1 WHERE id='$id'");
			}
		}
		else {
			while ( !$screen_name && $retry_name ) {
				$userinfo = json_decode( $ff -> show_user( $user ), true );
				$screen_name = $userinfo['screen_name'];
				$retry_name--;
			}
			if ( $screen_name == '' ) break 2;  // exit if screen_name is not available
			$status_message = '@' . $screen_name . ' ' . $message . $retry_emoji;
			$updateinfo = json_decode( $ff -> update( $status_message ), true );
			if ( $updateinfo['id'] ) {
				if ( $ontime['recurring'] == -1 )
					$mysql->runSql("DELETE FROM `app_ffontime` WHERE id='$id'");
				else
					$mysql->runSql("UPDATE `app_ffontime` SET retry=0 WHERE id='$id'");
				$count_at++;
			}
			else {
				$on_schedule = false;
				$mysql->runSql("UPDATE `app_ffontime` SET retry=1 WHERE id='$id'");
			}
		}
		
	}
	
}

$mysql->closeDb();

if ( count( $fetion_msg ) ) {
	$fetion = new PHPFetion(FETION_MOBILE, FETION_PASSWORD);
	foreach ( $fetion_msg as $mobile => $message ) {
		$fetion->send( $mobile, '马闹钟提醒你：' . $message . '。别忘记啦！' );
		sleep(2);	// prevent fetion sending frequency limitation
	}
}

echo 'at:'.$count_at.',dm:'.$count_dm.',fx:'.$count_fx.',status:'.$count_status;

?>