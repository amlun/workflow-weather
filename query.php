<?php
require_once 'workflow.php';
$baidu = 'https://www.baidu.com/home/other/data/weatherInfo?city=%s';

isset ( $argv [1] ) && $query = trim ( $argv [1] );
if (isset ( $query ) && ! empty ( $query )) {
	$url = sprintf ( $baidu, urlencode ( $query ) );
	$wl = new Workflows ();
	$response = $wl->request ( $url );
	$response = json_decode ( $response, true );
	if ($response ['errNo'] == 0 && isset ( $response ['data'] ['weather'] ['content'] )) {
		$weather_content = $response ['data'] ['weather'] ['content'];
		// 当前的信息
		$week = $weather_content ['week'];
		$city = $weather_content ['city'];
		$currenttemp = $weather_content ['currenttemp'];
		$calendar = $weather_content ['calendar'];
		$source = $weather_content ['source'];
		$wl->result ( 'city_info', $calendar ['weatherSourceUrl'], "当前气温：{$currenttemp} @ {$city}", "{$week} $calendar[lunar] 数据来自$source[name] ", '' );
		// 今天
		$days = [ 
				'today' => '今日',
				'tomorrow' => '明天',
				'thirdday' => '后天',
				'fourthday' => '',
				'fifthday' => '' 
		];
		foreach ( $days as $day => $day_title ) {
			$day_weather = $weather_content [$day];
			$condition = $day_weather ['condition'];
			$temp = $day_weather ['temp'];
			$wind = $day_weather ['wind'];
			$pm25 = ( int ) $day_weather ['pm25'];
			empty ( $pm25 ) && $pm25 = '未知';
			$date = $day_weather ['date'];
			$time = $day_weather ['time'];
			$icon = $day_weather ['imgs'] [0];
			$wl->result ( $day, $day_weather ['link'], "{$day_title}{$time} {$condition}", "气温{$temp} {$wind} PM2.5 {$pm25} {$date}", 'icon/' . $icon . '.jpg' );
		}
	} else {
		$wl->result ( 'response_error', null, '找不到对应城市的天气信息', '请求失败，请重试', '' );
	}
	print $wl->toxml ();
}