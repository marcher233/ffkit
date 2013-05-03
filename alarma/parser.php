<?php
header("Content-type: text/html; charset=utf-8");

function time_parser( $string ) {

	date_default_timezone_set('Asia/Shanghai');
	$today = date('Y-m-d');
	$tomorrow = date('Y-m-d', time() + 3600 * 24);
	$day_after_tomorrow = date('Y-m-d', time() + 3600 * 24 * 2);
	
	// presets
	$recurring = -1;	// default for none; 0 - daily, 1 - weekly, 2 - monthly
	$sequence = -1;	// default for none (one-time, daily); for weekday and date
	$action = 'add';	// default action
	$notification_type = 'mention';	// default notification method

	// full/half-width character parsing...
	$string = str_replace('，', ',   ', $string);
	$string = str_replace('！', '   !', $string);
	$string_array = explode(',', $string, 3);
	$string_array[2] = str_replace(',   ', '，', $string_array[2]);
	$string_array[2] = str_replace('   !', '！', $string_array[2]);
	
	// $string_array[0]: date parsing
	$string_array[0] = trim($string_array[0]);
	$indicator = substr($string_array[0], 0, 1);
	
	switch ( $indicator ) {
		case '#':	// deleting a ontime message
			$parsed_message = array(
				'action'	=> 'delete',
				'status'	=> 'success',
				'type'		=> 0,
				'timestamp'	=> 0,
				'message'	=> substr($string_array[0], 1)
			);
			return json_encode($parsed_message);
			break;
		case 's':
		case 'S':
		case '!':	// creating a private message
			$notification_type = 'private';
			$string_array[0] = substr($string_array[0], 1);
			break;
		case 'b':
		case 'B':
		case '%':	// bind or unbind a China Mobile phone number
			$mobile_phone = substr($string_array[0], 1);
			$mobile_result = 'error';
			if ( preg_match( "/^13[4-9]{1}[0-9]{8}$|15[0-27-9]{1}[0-9]{8}$|18[2378]{1}[0-9]{8}$/", trim($mobile_phone) ) )
				$mobile_result = 'cmcc';
			else if ( $mobile_phone == 0 )
				$mobile_result = 'unreg';
			$parsed_message = array(
				'action'	=> 'mobile',
				'status'	=> 'success',
				'type'		=> $mobile_result,
				'mobile'	=> $mobile_phone,
				'timestamp'	=> 0,
				'message'	=> 'update_mobile'
			);
			return json_encode($parsed_message);
			break;
		case 'x':
		case 'X':
		case '*':
			$notification_type = 'update';
			$string_array[0] = substr($string_array[0], 1);
			break;
		case 'f':
		case 'F':
		case '@':
			$notification_type = 'sendsms';
			$string_array[0] = substr($string_array[0], 1);
			break;
		default:
			break;
	}
	
	if ( !strtotime($string_array[0]) ) {
		switch ( strtolower($string_array[0]) ) {
			case '今天':
			case '今日':
			case '今儿':
			case 'today':
				$string_array[0] = $today;
				break;
			case '明天':
			case '明日':
			case '明儿':
			case 'tomorrow':
				$string_array[0] = $tomorrow;
				break;
			case '后天':
			case '后儿':
			case '後天':
				$string_array[0] = $day_after_tomorrow;
				break;
			case '每天':
			case '每日':
			case '天天':
			case 'everyday':
				$string_array[0] = $today;
				$recurring = 0;
				break;
			default:
				if ( substr($string_array[0], 0, 6) == '每周' || substr($string_array[0], 0, 6) == '每週') {
					switch ( substr($string_array[0], 6) ) {	// 星期，date N
						case 1: case '一': case '壹': $sequence = 1; break;
						case 2: case '二': case '贰': $sequence = 2; break;
						case 3: case '三': case '叁': $sequence = 3; break;
						case 4: case '四': case '肆': $sequence = 4; break;
						case 5: case '五': case '伍': $sequence = 5; break;
						case 6: case '六': case '陆': $sequence = 6; break;
						case 7: case '日': case '七': case '柒': case '天': $sequence = 7; break;
						default: return '{"status":"error", "message":"date"}';
					}
					$string_array[0] = $today;
					$recurring = 1;
				}
				else if ( substr($string_array[0], 0, 6) == '每月' && ( substr($string_array[0], -3, 3) == '号' || substr($string_array[0], -3, 3) == '日' ) ) {
					$sequence = (int)(substr( substr($string_array[0], 6), 0, -3 ));
					if ( $sequence < 1 || $sequence > 31 )
						return '{"status":"error", "message":"date"}';
					$string_array[0] = $today;
					$recurring = 2;
				}
				else {
					$chinese_date = array('年', '月');
					$chinese_postfix = array('號', '号', '日', ' ', '　');
					$string_array[0] = str_replace('今年', date('Y') . '-', trim($string_array[0]));
					$string_array[0] = str_replace($chinese_date, '-', $string_array[0]);
					$string_array[0] = str_replace($chinese_postfix, '', $string_array[0]);
					if ( strlen($string_array[0]) <= 5 ) $string_array[0] = date('Y') . '-' . $string_array[0];
				}
		}
	}

	// $string_array[1]: time parsing
	// replaces full-width characters
	$chinese_time = array('：', '点', '时', '時', '點');
	$chinese_postfix = array('分', ' ', '　');
	$chinese_ending = array('点', '时', '時', '點', '点整', '点正', '點整', '时整', '時整');
	foreach ( $chinese_ending as $ending ) {
		$string_end = substr($string_array[1], mb_strlen($string_array[1]) - mb_strlen($ending));
		if ( $string_end == $ending ) {
			$string_array[1] = str_replace($ending, ':00:00', trim($string_array[1]));
			break;
		}
	}
	$string_array[1] = str_replace($chinese_time, ':', $string_array[1]);
	$string_array[1] = str_replace($chinese_postfix, '', $string_array[1]);

	// date and time concatenation
	$string_time = $string_array[0] . ' ' . $string_array[1];
	if ( !strtotime($string_time) ) {
		return '{"status":"error", "message":"time"}';
	}
	
	// output parsed timestamp
	$parsed_message = array(
		'action'	=> $action,
		'status'	=> 'success',
		'type'		=> $notification_type,
		'timestamp'	=> strtotime($string_time),
		'recurring'	=> $recurring,
		'sequence'	=> $sequence,
		'message'	=> urlencode(trim($string_array[2]))
	);
	return json_encode($parsed_message);
}

?>