<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');
require_once('tmhOAuth.php');

header("Content-type: text/html; charset=utf-8");

ini_set('max_execution_time', 0);
set_time_limit(0);
$storage = DOMAIN;
$bday_fg = array('bday_fg0.png', 'bday_fg1.png');

function set_avatar( $avatar, $type, $storage ) {
	global $token, $secret;
	$tmhOAuth = new tmhOAuth(array(
	'consumer_key'    => FF_AKEY,
	'consumer_secret' => FF_SKEY,
	'user_token'      => $token,
	'user_secret'     => $secret
	));
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

$today = date('m-d');
$users_query = "SELECT id FROM `$ab_table` WHERE birthday='$today' AND state!=1";
$users_array = $mysql->getData( $users_query );
echo count($users_array).'usr=';

foreach ( $users_array as $key => $user_set ) {
	
	$user = $user_set['id'];
	$oauth_query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user'";
	$oauth_array = $mysql->getLine( $oauth_query );
	$token = $oauth_array['oauth_token'];
	$secret = $oauth_array['oauth_token_secret'];
	
	$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
	$id = json_decode( $ff_user -> show_user(), true );
	$avatar_url = str_replace( '/s0/', '/l0/', $id['profile_image_url'] );
	file_put_contents( SAE_TMP_PATH . $user . '_backup.jpg' , file_get_contents($avatar_url) );
	$s = new SaeStorage();
	$backup_avatar = $s->upload( $storage, $user . '_bak_' . time() . '.jpg', SAE_TMP_PATH . $user . '_backup.jpg' );
	$query = "INSERT INTO `$as_table` (id, avatar, type, storage, tweak) VALUES ('$user', '$backup_avatar', 'image/jpeg', '$storage', 'bday')";
	$mysql->runSql( $query );
	$query = "SELECT aid FROM `$as_table` WHERE avatar = '$backup_avatar'";
	$backup_data = $mysql->getLine( $query );
	$aid = $backup_data['aid'];
	
	// make avatar
	$template = $bday_fg[array_rand($bday_fg)];
	$o_avatar = $s->read($storage, basename($backup_avatar));
	$tweak = file_get_contents($template);
	$img = new SaeImage();
	$img->setData( array(
		array( $o_avatar, 0, 0, 1, SAE_TOP_LEFT ),
		array( $tweak, 0, 0, 1, SAE_TOP_LEFT )
	) );
	$img->composite(96, 96);
	file_put_contents( SAE_TMP_PATH . $user . '_bday.jpg' , $img->exec('jpg') );
	$filename = $user . '_bday.jpg';
	$set_avatar = set_avatar( $filename, 'image/jpeg', $storage );
	if ( $set_avatar == 200 ) { // status unknown?
		echo $user . '-ok,' . $set_avatar . '|';
		$query = "UPDATE `$ab_table` SET aid=$aid, state=1 WHERE id='$user'";
		$mysql->runSql($query);
	}
	else
		echo $user . '-err,' . $set_avatar . '|';
	
}

$users_query = "SELECT id, aid FROM `$ab_table` WHERE birthday!='$today' AND state=1";
$users_array = $mysql->getData( $users_query );
echo ' %% '.count($users_array).'usr=';

foreach ( $users_array as $key => $user_set ) {
	
	$user = $user_set['id'];
	$aid = $user_set['aid'];
	$oauth_query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user'";
	$oauth_array = $mysql->getLine( $oauth_query );
	$token = $oauth_array['oauth_token'];
	$secret = $oauth_array['oauth_token_secret'];
	$avatar_query = "SELECT avatar, storage FROM `$as_table` WHERE aid=$aid";
	$avatar_array = $mysql->getLine( $avatar_query );
	if (count($avatar_array)) {
		$avatar = basename($avatar_array['avatar']);
		$storage = $avatar_array['storage'];
	}
        else
        	continue;
	
	// read image for datastore
	$s = new SaeStorage();
	file_put_contents( SAE_TMP_PATH . $avatar, $s->read($storage, $avatar) );
	
	$set_avatar = set_avatar( $avatar, 'image/jpeg', $storage );
	if ( $set_avatar < 400 ) {
		echo $user . '-ok,' . $set_avatar . '|';
		$update_query = "UPDATE `$ab_table` SET aid=0, state=0 WHERE id='$user'";
		$mysql->runSql($update_query);
		$delete_query = "DELETE FROM `$as_table` WHERE aid=$aid";
		$mysql->runSql($delete_query);
		$del_avatar = $s->delete($storage, $avatar);
	}
	else
		echo $user . '-err,' . $set_avatar . '|';
		
}

$mysql->closeDb();
?>