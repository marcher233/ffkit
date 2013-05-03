<?php
require_once('../config.php');
require_once('../avatar/tmhOAuth.php');

header("Content-type: text/html; charset=utf-8");

function random() {
	$hash = '';
	$chars = 'abcdefghijklmnopqrstuvwxy';
	mt_srand( (double)microtime() * 1000000 );
	$hash = $chars[mt_rand(0, 24)];
	return $hash;
}

$tmhOAuth = new tmhOAuth(array(
	'consumer_key'    => FF_AKEY,
	'consumer_secret' => FF_SKEY,
	'user_token'      => 'TOKEN_OF_USER_@ggtt101', //$token
	'user_secret'     => 'SECRET_OF_USER_@ggtt101' //secret
));
$db = new SaeMysql();

// post a entry for every 10 minutes
$minute = date('i');
$mod = fmod( $minute, 10 );
if ( !$mod || $_GET['m5']=='ggtt' ) {
	$sql_prio = "SELECT * FROM `app_ggtt_words` WHERE prio>0 ORDER BY prio DESC";
	$word_prio = $db->getData( $sql_prio );
	if ( count($word_prio) ) {
		$code = trim($word_prio[0]['code']);
		$words = trim($word_prio[0]['words']);
		$times = $word_prio[0]['times'];
	}
	else {
		$key = random();
		$sql_desc = "SELECT * FROM `app_ggtt_words` WHERE code LIKE '$key%' ORDER BY times ASC LIMIT 1";
		$word_set = $db->getData( $sql_desc );
		$index = mt_rand(0, count( $word_set ) / 2 - 1); // select a word from first half
		$code = trim($word_set[$index]['code']);
		$words = trim($word_set[$index]['words']);
		$times = $word_set[$index]['times'];
	}
	if ( !$code || !$words ) break;
	echo 'Posting 5-minute words: '.$code.':'.$words.'。<br />';
	$tmhOAuth->request(
		'POST',
		'http://api.fanfou.com/statuses/update.json',
		array(
			'status'  => '单字/词组：' . $words . '，编码：' . $code . '。'
		),
		true, // use auth
		false  // multipart
	);
	if ( $tmhOAuth->response['code'] == 200 ) {
		$times++;
		$sql = "UPDATE `app_ggtt_words` SET times='$times', prio=0 WHERE code='$code'";
		$db->runSql( $sql );
	}
}

if ( isset($_GET['prio']) ) {
	$entry = $_GET['prio'];
	$sql = "UPDATE `app_ggtt_words` SET prio=1 WHERE code='$entry'";
	$db->runSql( $sql );
	echo 'Set prio for '.$entry.' to 1.';
}

// reply requests
$sql = "SELECT msg FROM `app_ggtt_log` WHERE id = ( SELECT MAX(id) FROM `app_ggtt_log` WHERE 1 LIMIT 1 )";
$query = $db->getLine($sql);
$since_id = $query['msg'];
if (!$since_id) die('error getting last_id!');
echo 'Getting since id: '.$since_id.', <br />';

$tmhOAuth->request(
	'GET',
	'http://api.fanfou.com/statuses/replies.json',
	array(
		'since_id'  => $since_id,
		'count' => 60,
		'page' => 1
	),
	true, // use auth
	false  // multipart
);
$req = array_reverse( json_decode( $tmhOAuth->response['response'], true ) );
echo 'Getting '.count($req).' messages; <br />';

foreach($req as $key => $msg) {
	$result = '';
	$id = $msg['id'];
	$text = explode(' ', $msg['text'], 2);
	$body = str_replace("@","@ ", $text[1]);
	$uid = $msg['user']['id'];
	$screen_name = $msg['user']['screen_name'];
	echo 'User '.$screen_name.' posted: '.$body.'; <br />';
	preg_match_all('/./u', $body, $r);
	foreach ( $r[0] as $character ) {
		$sql = "SELECT code FROM `app_ggtt_gbk` WHERE word LIKE '%$character%'";
		$query = $db->getData($sql);
		if (!count($query))
			$result .= $character;// . '『?』';
		else
			$result .= $character . '『' . $query[0]['code'] . '』';
	}
	$tmhOAuth->request(
		'POST',
		'http://api.fanfou.com/statuses/update.json',
		array(
			'status'  => '@' . $screen_name . ' ' . $result,
			'in_reply_to_status_id' => $id,
			'in_reply_to_user_id' => $uid
		),
		true, // use auth
		false  // multipart
	);
	if ( $tmhOAuth->response['code'] == 200 ) {
		$timestamp = time();
		$sql = "INSERT INTO `app_ggtt_log` (timestamp, user, msg, body) VALUES ('$timestamp', '$uid', '$id', '$body')";
		$db->runSql( $sql );
	}
}

$db->closeDb();

?>