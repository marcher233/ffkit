<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');
require_once('tmhOAuth.php');

header("Content-type: text/html; charset=utf-8");

ini_set('max_execution_time', 0);
set_time_limit(0);

$timestamp = time();
$weekday = date('w');

echo 'weekday_'.$weekday.'||';

// update avatar image
function set_avatar( $avatar, $type, $storage ) {
	global $token, $secret;
	$tmhOAuth = new tmhOAuth(array(
	'consumer_key'    => FF_AKEY,
	'consumer_secret' => FF_SKEY,
	'user_token'      => $token,
	'user_secret'     => $secret
	));
	$s = new SaeStorage();
	file_put_contents( SAE_TMP_PATH . $avatar, $s->read($storage, $avatar) );
	$filename = SAE_TMP_PATH . $avatar;
	$image = "{$filename};type={$type};filename={$filename}";
	return $tmhOAuth->request(
		'POST',
		'http://api.fanfou.com/account/update_profile_image.json',
		array(
			'image'  => "@{$image}"
		),
		true, // use auth
		true  // multipart
	);
}

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');

// select users
$users_query = "SELECT stor.id user FROM `$aschedule_table` sche LEFT JOIN `$as_table` stor ON sche.aid = stor.aid WHERE 1 GROUP BY stor.id";
$users_array = $mysql->getData( $users_query );
echo 'scheduled_'.count($users_array).'_usr||';

foreach ( $users_array as $key => $user_set ) {
	
	$user = $user_set['user'];
	$oauth_query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user'";
	$oauth_array = $mysql->getLine( $oauth_query );
	$token = $oauth_array['oauth_token'];
	$secret = $oauth_array['oauth_token_secret'];
	
	// search date-specified job [0]
	$jobs_query = "SELECT sche.aid aid, sche.schedule schedule, stor.avatar avatar, stor.type type, stor.storage storage FROM `$aschedule_table` sche LEFT JOIN `$as_table` stor ON sche.aid = stor.aid WHERE stor.id = '$user' AND sche.schedule <= $timestamp AND sche.recurring = 'd'";
	$jobs_array = $mysql->getData( $jobs_query );
	if ( count($jobs_array) ) {
		$aid = $jobs_array[0]['aid'];
		$avatar = basename($jobs_array[0]['avatar']);
		$storage = $jobs_array[0]['storage'];
		$type = $jobs_array[0]['type'];
		$schedule = $jobs_array[0]['schedule'];
		$set_avatar = set_avatar( $avatar, $type, $storage );
		if ( $set_avatar == 200 ) {
			echo $user.'_date_'.$aid.'_ok|';
			// delete finished date-specified job
			$query = "DELETE FROM `$aschedule_table` WHERE aid=$aid";
			$mysql->runSql($query);
			// delete date-specified job
			// if date-specified job is finished, ignore any weekly job ...
			// add 7-day to postpone the weekly job
			$schedule += 604800;
			$query = "UPDATE `$aschedule_table` sche LEFT JOIN `$as_table` stor ON sche.aid=stor.aid SET sche.status = $weekday, sche.schedule = $schedule WHERE stor.id = '$user' AND FROM_UNIXTIME(sche.schedule,'%w') = $weekday AND sche.recurring = 'w'";
			$mysql->runSql($query);
		}
		else
			echo $user.'_date_'.$aid.'_'.$set_avatar.'_fail|';
	}
	else {
		// search weekly job
		$jobs_query = "SELECT sche.aid aid, sche.schedule schedule, stor.avatar avatar, stor.type type, stor.storage storage, sche.recurring recurring FROM `$aschedule_table` sche LEFT JOIN `$as_table` stor ON sche.aid = stor.aid WHERE stor.id = '$user' AND sche.schedule <= $timestamp AND FROM_UNIXTIME(sche.schedule,'%w') = $weekday AND sche.status != $weekday AND sche.recurring = 'w'";
		$jobs_array = $mysql->getData( $jobs_query );
		if ( count($jobs_array) ) {
			$aid = $jobs_array[0]['aid'];
			$avatar = basename($jobs_array[0]['avatar']);
			$storage = $jobs_array[0]['storage'];
			$type = $jobs_array[0]['type'];
			$schedule = $jobs_array[0]['schedule'];
			$set_avatar = set_avatar( $avatar, $type, $storage );
			if ( $set_avatar == 200 ) {
				echo $user.'_week_'.$aid.'_ok|';
				// add 7-day to schedule the next weekly job, set status to weekday today
				$schedule += 604800;
				$query = "UPDATE `$aschedule_table` SET schedule = $schedule, status = $weekday WHERE aid = $aid";
				$mysql->runSql($query);
			}
			else
				echo $user.'_week_'.$aid.'_'.$set_avatar.'_fail|';
		}
		$reset_query = "UPDATE `$aschedule_table` SET status = -1 WHERE status NOT IN (-1, $weekday) AND recurring = 'w'";
		$mysql->runSql($reset_query);
	}
}

$mysql->closeDb();
echo '|finished';

?>