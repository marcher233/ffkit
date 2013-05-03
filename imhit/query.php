<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

//header('Content-Type: application/json');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) die('no cookie');
// not authorized

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' AND uid='$uid'";
$data = $mysql->getData( $query );

if ( !count($data) )
	die("no data");

$data = $mysql->getLine( $query );
$token = $data['oauth_token'];
$secret = $data['oauth_token_secret'];
// get user token and secret

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );

if ( $_POST['action'] == 'create' && isset( $_POST['query'] ) ) {
	echo $ff_user -> add_saved_search( $_POST['query'] );
}
else if ( $_POST['action'] == 'remove' && isset( $_POST['query_id'] ) ) {
	$query_id = $_POST['query_id'];
	$delete_query = "DELETE FROM `$hit_table` WHERE id='$user' AND query_id='$query_id'";
	$mysql->runSql( $delete_query );
	echo $ff_user -> del_saved_search( $_POST['query_id'] );
}
else if ( $_POST['action'] == 'add_dm' && isset( $_POST['query_id'] ) ) {
	$query_id = $_POST['query_id'];
	echo $query_response = $ff_user -> show_saved_search( $query_id );
	$search_query = json_decode( $query_response, true );
	$query = $search_query['query'];
	$search_result = json_decode( $ff_user -> universal_search( $query ), true );
	$last_id = $search_result[0]['rawid'];
	$insert_query = "INSERT INTO `$hit_table` (id, query_id, query, lastid, lastmatch, lastquery) VALUES ('$user', '$query_id', '$query', '$last_id', unix_timestamp(), unix_timestamp())";
	$mysql->runSql( $insert_query );
}
else if ( $_POST['action'] == 'del_dm' && isset( $_POST['query_id'] ) ) {
	$query_id = $_POST['query_id'];
	$delete_query = "DELETE FROM `$hit_table` WHERE id='$user' AND query_id='$query_id'";
	$mysql->runSql( $delete_query );
	echo $query_response = $ff_user -> show_saved_search( $query_id );
}
else if ( $_POST['action'] == 'set_freq' && isset( $_POST['freq_val'] ) ) {
	$freq_val = $_POST['freq_val'] * 60;
	$update_query = "UPDATE `$table` SET dm_freq='$freq_val' WHERE id='$user' AND uid='$uid'";
	$mysql->runSql( $update_query );
	echo '{"id":' . $freq_val . '}';
}

$mysql->closeDb();
?>
