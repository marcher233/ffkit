<?php
require_once('../config.php');
require_once('../oauth.php');
require_once('client.php');

if ( !isset( $_COOKIE['ffid'] ) || !isset( $_COOKIE['ffuid'] ) ) die('Illegal request.');

$user = $_COOKIE['ffid'];
$uid = $_COOKIE['ffuid'];
$status = $_POST['content'];

$mysql = new SaeMysql();
$mysql->setCharset('UTF8');
$query = "SELECT oauth_token, oauth_token_secret FROM `$table` WHERE id='$user' and uid='$uid'";
$data = $mysql->getLine( $query );

if ( !count($data) ) die('Bad request. <a href="javascript:history.back();">back</a>');

$uid = $data['uid'];
$token = $data['oauth_token'];
$secret = $data['oauth_token_secret'];

$ff_user = new FFClient( FF_AKEY , FF_SKEY , $token , $secret );
$return = json_decode( $ff_user -> update( $status ), true );

if ( $return['id'] )
	header('Location: http://fanfou.com/statuses/' . $return['id'] . '/');
else
	die('Error while sending Fanfou status.');

$mysql->closeDb();
?>