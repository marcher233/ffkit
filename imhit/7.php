<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

header("Content-type: text/html; charset=utf-8");

ini_set('max_execution_time', 0);
set_time_limit(0);

$robot = new FFClient( FF_AKEY , FF_SKEY , OAUTH_TOKEN_ALARMA , OAUTH_TOKEN_SECRET_ALARMA );
// use token and secret of @alarma, default api

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$users_query = "SELECT id FROM `$hit_table` WHERE 1 GROUP BY id";
$users_array = $mysql->getData( $users_query );
echo count($users_array).'usr!!';

foreach ( $users_array as $key => $search_query ) {

	$user_id = $search_query['id'];	// get user id
	echo ' >>usr:['.$user_id.'],';
	$query_updated = array();	// init updated queries
	$updated_query_list = '';	// init updated query names

	$user_query = "SELECT oauth_token, oauth_token_secret, dm_freq FROM `app_ffbirthday2` WHERE id='$user_id'";
	$user_array = $mysql->getLine($user_query);
	$token = $user_array['oauth_token'];
	$secret = $user_array['oauth_token_secret'];
	$dm_freq = ( $user_array['dm_freq'] == -1 ) ? 3600 : $user_array['dm_freq'];
	// added customized sending interval setting, 3600 (1hr) if not defined
	$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
	
	$word_query = "SELECT * FROM `$hit_table` WHERE id='$user_id'";
	$word_array = $mysql->getData( $word_query );
	
	foreach ( $word_array as $key => $query_item ) {
	
		$query = $query_item['query'];
		echo 'q:<'.$query.'>';
		$query_id = $query_item['query_id'];
		$last_id = $query_item['lastid'];
		$last_match = $query_item['lastmatch'];
		$last_dm = $query_item['lastdm'];
		$last_query = $query_item['lastquery'];
		$now_time = time();
		$latest_id = '';	// init...
		
		if ( $now_time - $last_query < $dm_freq ) {
			echo '<-skip; ';
			continue;
		}
		
		$query_check = json_decode( $ff_user -> show_saved_search( $query_id ), true );
		if ( ! $query_check['id'] ) {
			$delete_query = "DELETE FROM `$hit_table` WHERE id='$user_id' AND query_id='$query_id'";
			$mysql->runSql( $delete_query );
			$send_dm = json_decode($robot -> send_dm( $user_id, '我发现你好像从饭否删除了关注的话题【' . $query . '】。我不会再给你发送与此相关的提醒了。谢谢使用～' ), true);
			$robot -> delete_dm( $send_dm['id'] );
			echo '<-del; ';
			continue;
		}
		// remove user deleted query
		
		$search_result = json_decode( $ff_user -> universal_search( $query ), true );
		echo '<-done; ';
		$latest_id = $search_result[0]['rawid'];
		
		if ( $latest_id > $last_id ) {
			$update_query = "UPDATE `$hit_table` SET lastid='$latest_id', lastmatch=unix_timestamp(), lastquery=unix_timestamp() WHERE id='$user_id' AND query_id='$query_id'";
			$mysql->runSql( $update_query );
			echo '<-upd; ';
			if ( $now_time - $last_dm > $dm_freq ) 
				array_push($query_updated, $query);
		}
		else {
			$update_query = "UPDATE `$hit_table` SET lastquery=unix_timestamp() WHERE query_id='$query_id'";
			$mysql->runSql( $update_query );
		}
		
	}
	
	$updated_count = count( $query_updated );
	if ( $updated_count ) {
	
		$i = 0;
		for ( $i = 0; $i < $updated_count; $i++ ) {
			if ( $i+1 == 3 || $i+1 == $updated_count ) {
				$updated_query_list .= '「' . $query_updated[$i] . '」等' . $updated_count . '条话题';
				break;
			}
			else
				$updated_query_list .= '「' . $query_updated[$i] . '」、';
		}
		$send_dm = json_decode( $robot -> send_dm( $user_id, '注意啦！你关注的' . $updated_query_list . '有了新消息。去饭否首页“关注的话题”看一眼吧！【此提醒每' . $dm_freq / 60 . '分钟最多发送一次，修改设置请至http://chen.ma/zq】' ), true );
		echo '<-sent; ';
		$robot -> delete_dm( $send_dm['id'] );
		$update_query = "UPDATE `$hit_table` SET lastdm=unix_timestamp() WHERE id='$user_id'";
		$mysql->runSql( $update_query );
		// update latest direct message sending time
		
	}
	
}

$mysql->closeDb();
?>