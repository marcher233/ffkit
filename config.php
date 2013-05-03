<?php
error_reporting(0);

define( "FF_AKEY" , 'YOUR_KEY' );	// oauth consumer key, default api
define( "FF_SKEY" , 'YOUR_KEY' );	// oauth consumer secret key, default api
define( "FF_AKEY_ALARMA" , 'YOUR_KEY' );	// oauth consumer key, api: alarma
define( "FF_SKEY_ALARMA" , 'YOUR_KEY' );	// oauth consumer secret key, api: alarma

define( "FF_CALLBACK", 'http://' . $_SERVER ['HTTP_HOST'] . '/callback.php' );

define( "OAUTH_TOKEN_ALARMA" , 'YOUR_KEY' );	// oauth token for /alarma, api: alarma
define( "OAUTH_TOKEN_SECRET_ALARMA" , 'YOUR_KEY' );	// oauth token secret for /alarma, api: alarma
//define( "FF_CALLBACK", 'http://localhost/sae/4/callback.php' );

define( "OAUTH_TOKEN" , 'YOUR_KEY' );	// oauth token for /alarma
define( "OAUTH_TOKEN_SECRET" , 'YOUR_KEY' );	// oauth token secret for /alarma

define( "FETION_MOBILE" , 'YOUR_FETION_MOBILE' );	// fetion username for /alarma sms
define( "FETION_PASSWORD" , 'YOUR_FETION_PASSWORD' );	// fetion password for /alarma sms

define( "DOMAIN", 'YOUR_SAE_STORAGE_DOMAIN' ); // user avatar storage

// default tables, see /db for details
$table = 'app_ffbirthday2';	// default table stores user oauth and secret
$hit_table = 'app_ffhitlist';	// table stores user search keywords
$ot_table = 'app_ffontime';		// table for alarma schedule
$as_table = 'ffavatar_storage';	// table for avatar info
$aschedule_table = 'ffavatar_schedule';	// table for avatar schedule
$ab_table = 'ffavatar_birthday';	// table for birhthday storage

// timeline cleaner blacklist
$black_list = array();
if ( in_array($_COOKIE['ffid'], $black_list) )
	die('Access denied.');

?>