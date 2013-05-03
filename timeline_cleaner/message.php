<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) echo json_encode(array());
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' and uid='$uid'";
$data = $mysql->getLine( $query );

if ( !count($data) )
	echo json_encode(array());

$token = $data['oauth_token'];
$secret = $data['oauth_token_secret'];
// get user token and secret

ini_set('max_execution_time', 0);
set_time_limit(0);

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );

$user_id = false;
if ( isset($_GET['id']) )
	$user_id = $_GET['id'];
if ( isset($_GET['since']) && isset($_GET['until']) )
	$query = 'since:' . $_GET['since'] . ' until:' . $_GET['until'];
else if ( isset($_GET['query']) )
	$query = $_GET['query'];

$type = $_GET['type'];
function get_timeline($page, $count, $max_id) {
	global $type, $ff_user, $user_id, $query;
	switch ( $type ) {
		case 'photo':
			return $ff_user -> photo_timeline($page, $count, $max_id, $user_id);
			break;
		case 'fav':
			return $ff_user -> fav_timeline($page, $count, $max_id, $user_id);
			break;
		case 'mention':
			return $ff_user -> mentions($page, $count, $max_id);
			break;
		case 'dmr':
			return $ff_user -> dmr_timeline($page, $count, $max_id);
			break;
		case 'dms':
			return $ff_user -> dms_timeline($page, $count, $max_id);
			break;
		case 'current':
			return $ff_user -> home_timeline($page, $count, $max_id, $user_id);
			break;
		case 'search':
		case 'timerange':
			return $ff_user -> search_timeline($page, $count, $max_id, $user_id, $query);
			break;
		case '':
		default:
			return $ff_user -> user_timeline($page, $count, $max_id, $user_id);
	}
}

if ( isset($_GET['page']) && isset($_GET['pcount']) ) {
	$page = $_GET['page'];
	$pcount = $_GET['pcount'];
	// Page number and how many pages should retrieve
	if ( $_GET['order'] == 'reversed' && $pcount > $page) $pcount = $page;
	$message_array = array();
	if ( isset($_GET['max_id']) ) {
		$max_id = $_GET['max_id'];
		for ( $i = 0; $i < $pcount; $i++) {
			$messages = json_decode( get_timeline(1, 50, $max_id) );
			if ( !count($messages) ) break;
			$last_index = count($messages) - 1;
			$max_id = $messages[$last_index]->id;
			$message_array = array_merge($message_array, $messages);
		}
		// To get messages via $max_id
	}
	else {
		while ( $pcount ) {
			$messages = json_decode( get_timeline($page, 50, false) );
			$pcount--;
			if ( $_GET['order'] == 'reversed' ) {
				$page--;
				$messages = array_reverse($messages);
			}
			else {
				$page++;
			}
			if ( !count($messages) ) break;
			$message_array = array_merge($message_array, $messages);
		}
		// To get messages via page count
	}
	$json_return = json_encode($message_array);
	if ($json_return == 'null') echo json_encode(array());
	else echo $json_return;
}
else if ( $_GET['action'] == 'destroy' && isset($_POST['status']) ) {
	$destroyed_message = array();
	$destroy_message = array();
	$c = new SaeCounter();
	$status_id = $_POST['status'];
	if ( $_GET['type'] == 'fav' )
		$destroy = json_decode( $ff_user -> destroy_favorites($status_id), true );
	else if ( in_array($_GET['type'], array('dmr', 'dms')) )
		$destroy = json_decode( $ff_user -> delete_dm($status_id), true );
	else
		$destroy = json_decode( $ff_user -> destroy($status_id), true );
	if ( $destroy['id'] ) {
		array_push( $destroyed_message, array($destroy['id']) );
		$c->incr('cleaner_count');
	}
	echo json_encode($destroyed_message);
}

?>
