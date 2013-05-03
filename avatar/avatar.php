<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');
require_once('tmhOAuth.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) die('no cookie');
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret, avatar_quota, avatar_filesize FROM `$table` WHERE id='$user' AND uid='$uid' LIMIT 1";
$data = $mysql->getData( $query );
if ( !count($data) )
	die("Invalid user.");

$storage = DOMAIN;
$token = $data[0]['oauth_token'];
$secret = $data[0]['oauth_token_secret'];
$quota = $data[0]['avatar_quota'];
$filesize = $data[0]['avatar_filesize'];
// get user token and secret

$avatar_query = "SELECT count(1) AS count FROM `$as_table` WHERE id='$user' AND tweak=''";
$avatar_count = $mysql->getLine( $avatar_query );

function random( $length ) {
	$hash = '';
	$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	$max = strlen($chars) - 1;
	mt_srand( (double)microtime() * 1000000 );
	for ( $i = 0; $i < $length; $i++ )
		$hash .= $chars[mt_rand(0, $max)];
	return $hash;
}

function redirect( $retcode ) {
	SetCookie('retcode', $retcode, time()+36000);
	header('Location:' . $_SERVER['HTTP_REFERER']);
	$mysql->closeDb();
	exit();
	die();
}

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

if ( $_GET['action'] == 'upload' && $_FILES['avatar']['tmp_name'] ) {
	// uploading new avatar
	$typelist = array('image/jpeg', 'image/pjpeg', 'image/gif', 'image/png');
	//$finfo = new finfo(FILEINFO_MIME);
	//$mimetype = $finfo->file($_FILES['avatar']['tmp_name']);
	if ( $avatar_count['count'] >= $quota )
		redirect(10);
	if ( !in_array( $_FILES['avatar']['type'], $typelist ) )
	//if ( !in_array($mimetype, $typelist) )
		redirect(11);
	if ( $_FILES['avatar']['error'] )
		redirect(12);
	if ( $_FILES['avatar']['size'] > $filesize )
		redirect(13);
	switch ( $_FILES['avatar']['type'] ) {
		case 'image/gif':
			$filetype = '.gif';
			break;
		case 'image/png':
			$filetype = '.png';
			break;
		default:
			$filetype = '.jpg';
	}
	$s = new SaeStorage();
	$upload_result = $s->upload( $storage, $user . '_' . random(6) . '_' . time() . $filetype, $_FILES['avatar']['tmp_name'] );
	if ( $upload_result ) {
		$type = $_FILES['avatar']['type'];
		$query = "INSERT INTO `$as_table` (id, avatar, type, storage) VALUES ('$user', '$upload_result', '$type', '$storage');";
		$mysql->runSql( $query );
		if ( $_GET['source'] == 'picself' ){
			SetCookie('retcode', 1, time()+36000);
			echo '{"code":"A00006","message":"头像美化成功！","data":"http://marcher.sinaapp.com/avatar/"}';
			exit();
		}
		redirect(1);
	}
	else {
		redirect(3);
		if ( $_GET['source'] == 'picself' ){
			SetCookie('retcode', 3, time()+36000);
			echo '{"code":"-A00001","message":"头像美化出错……","data":"http://marcher.sinaapp.com/avatar/"}';
			exit();
		}
	}
}
else if ( $_GET['action'] == 'download' && isset($_POST['url']) ) {
	// getting new avatar
	$url = $_POST['url'];
	$f = new SaeFetchurl();
	$file = $f->fetch( $url );
	$header = $f->responseHeaders(true);
	$filelen = $header['Content-Length'];
	$filetype = $header['Content-Type'];
	$typelist = array('image/jpeg', 'image/pjpeg', 'image/gif', 'image/png');
	if ( $avatar_count['count'] >= $quota )
		redirect(10);
	if ( !in_array( $filetype, $typelist ) )
		redirect(11);
	if ( $filelen > $filesize )
		redirect(13);
	switch ( $filetype ) {
		case 'image/gif':
			$fileext = '.gif';
			break;
		case 'image/png':
			$fileext = '.png';
			break;
		default:
			$fileext = '.jpg';
	}
	file_put_contents( SAE_TMP_PATH . $user . '_downloaded' . $fileext, $file );
	$download_file = SAE_TMP_PATH . $user . '_downloaded' . $fileext;
	$s = new SaeStorage();
	$download_result = $s->upload( $storage, $user . '_' . random(6) . '_' . time() . $fileext, $download_file );
	if ( $download_result ) {
		$query = "INSERT INTO `$as_table` (id, avatar, type, storage) VALUES ('$user', '$download_result', '$filetype', '$storage');";
		$mysql->runSql( $query );
		redirect(1);
	}
	else
		redirect(3);
}
else if ( $_GET['action'] == 'schedule' && isset( $_GET['aid'] ) && isset( $_POST['recurring'] ) && ( isset( $_POST['date'] ) || isset( $_POST['weekday'] ) ) ) {
	$aid = $_GET['aid'];
	$recurring = $_POST['recurring'];
	$date = strtotime($_POST['date']);
	$query = "SELECT * FROM `$as_table` WHERE id='$user' AND aid='$aid'";
	$avatar_prop = $mysql->getLine( $query );
	if ( count($avatar_prop) ) {
		switch ( $recurring ) {
			case 'd':
				$time_format = "FROM_UNIXTIME(sche.schedule,'%Y-%m-%d')";
				$comp_format = $_POST['date'];
				$timestamp = mktime(0, rand(0,10), 0, date('m', $date), date('d', $date), date('Y', $date));
				break;
			case 'w':
				$time_format = "FROM_UNIXTIME(sche.schedule,'%w')";
				$comp_format = $_POST['weekday'];
				$weekday_string = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
				$date = strtotime('last ' . $weekday_string[$comp_format] . ' - 2 week');
				$timestamp = mktime(0, rand(0,10), 0, date('m', $date), date('d', $date), date('Y', $date));
				break;
		}
		$dup_query = "SELECT $time_format date FROM `$as_table` stor LEFT JOIN `$aschedule_table` sche ON stor.aid=sche.aid WHERE stor.id='$user' AND sche.recurring='$recurring' AND sche.schedule IS NOT NULL";
		$dup_array = $mysql->getData($dup_query);
		foreach ( $dup_array as $key => $dup_item ) {
			if ( $comp_format == $dup_item['date'] )
				redirect(2);
		}
		$query = "INSERT INTO `$aschedule_table` (aid, schedule, recurring) VALUES ('$aid', '$timestamp', '$recurring')";
		if ( $mysql->runSql($query) ) {
			redirect(20);	// success, but warn user on dup jobs
		}
	}
	else
		redirect(3);
}
else if ( $_GET['action'] == 'cancel' && isset( $_GET['aid'] ) ) {
	$aid = $_GET['aid'];
	$query = "SELECT * FROM `$as_table` WHERE id='$user' AND aid='$aid'";
	$avatar_prop = $mysql->getLine( $query );
	if ( count($avatar_prop) ) {
		$query = "DELETE FROM `$aschedule_table` WHERE aid='$aid'";
		if ( $mysql->runSql($query) )
			redirect(1);
	}
	else
		redirect(3);
}
else if ( $_GET['action'] == 'set' && isset( $_GET['aid'] ) ) {
	$aid = $_GET['aid'];
	$query = "SELECT * FROM `$as_table` WHERE id='$user' AND aid='$aid'";
	$avatar_prop = $mysql->getLine( $query );
	if ( count($avatar_prop) ) {
		$avatar = basename( $avatar_prop['avatar'] );
		$type = $avatar_prop['type'];
		$datastore = $avatar_prop['storage'];
		$set_avatar = set_avatar( $avatar, $type, $datastore );
		if ( $set_avatar == 200 )
			redirect(1);
	}
	else
		redirect(3);
}
else if ( $_GET['action'] == 'delete' && isset( $_GET['aid'] ) ) {
	$aid = $_GET['aid'];
	$query = "SELECT avatar, storage FROM `$as_table` WHERE id='$user' AND aid='$aid'";
	$avatar_prop = $mysql->getLine( $query );
	if ( count($avatar_prop) ) {
		$avatar = basename( $avatar_prop['avatar'] );
		$datastore = $avatar_prop['storage'];
		$query = "DELETE FROM `$as_table` WHERE aid='$aid'";
		$mysql->runSql($query);
		$query = "DELETE FROM `$aschedule_table` WHERE aid='$aid'";
		$mysql->runSql($query);
		$s = new SaeStorage();
		$del_avatar = $s->delete($datastore, $avatar);
		if ( $del_avatar )
			redirect(1);
	}
	else
		redirect(3);
}
else if ( $_GET['action'] == 'backup' ) {
	if ( $avatar_count['count'] >= $quota )
		die('_QUOTA_EXCEEDED');
	$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
	$api = json_decode( $ff_user -> show_user(), true );
	$avatar_url = str_replace( '/s0/', '/l0/', $api['profile_image_url'] );
	file_put_contents( SAE_TMP_PATH . $user . '_backup.jpg' , file_get_contents($avatar_url) );
	$s = new SaeStorage();
	$backup_avatar = $s->upload( $storage, $user . '_' . random(6) . '_' . time() . '.jpg', SAE_TMP_PATH . $user . '_backup.jpg' );
	if ( $backup_avatar ) {
		$query = "INSERT INTO `$as_table` (id, avatar, type, storage) VALUES ('$user', '$backup_avatar', 'image/jpeg', '$storage');";
		$mysql->runSql( $query );
		redirect(1);
	}
	else {
		redirect(4);
	}
}
else if ( $_GET['tweak'] == 'bday' ) {
	switch ( $_GET['a'] ) {
		case 'enable':
			$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
			$api = json_decode( $ff_user -> show_user(), true );
			$birthday = $api['birthday'];
			if ( (int)substr($birthday,5,2) && (int)substr($birthday,8,2) ) {
				$birthday = substr($birthday,5);
				$query = "INSERT INTO `$ab_table` (id, birthday) VALUES ('$user', '$birthday')";
				$mysql->runSql($query);
				redirect(1);
			}
			else {
				redirect(50);
			}
			break;
		case 'disable':
			$query = "SELECT aid FROM `$ab_table` WHERE id='$user' AND state=1";
			$result = $mysql->getData( $query );
			if ( count($result) ) {
				$aid = $result[0]['aid'];
				$avatar_query = "SELECT avatar, storage FROM `$as_table` WHERE aid='$aid'";
				$avatar_array = $mysql->getLine( $avatar_query );
				$avatar = basename($avatar_array['avatar']);
				$datastore = $avatar_array['storage'];
				$set_avatar = set_avatar( $avatar, 'image/jpeg', $datastore);
				if ( $set_avatar == 200 ) {
					$query = "DELETE FROM `$as_table` WHERE aid='$aid'";
					$mysql->runSql($query);
					$s = new SaeStorage();
					$s->delete( $datastore, $avatar );
				}
				else
					redirect(3);
			}
			$query = "DELETE FROM `$ab_table` WHERE id='$user'";
			$mysql->runSql($query);
			redirect(1);
			break;
		default:
			redirect(3);
	}
}
else {
	redirect(3);
}

redirect(3);
?>
