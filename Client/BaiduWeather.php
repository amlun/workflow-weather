<?php
/**
 * Baidu Weather Client
 *
 * @author lunweiwei
 *        
 */
class BaiduWeather {
	const ENDPOINT = 'https://www.baidu.com/home/other/data/weatherInfo?city=%s';
	private static $_instances = [ ];
	private static $_days = [ 
			'today' => '今日',
			'tomorrow' => '明天',
			'thirdday' => '后天',
			'fourthday' => '',
			'fifthday' => '' 
	];
	private $_query;
	private $_weather;
	/**
	 * get the weather data
	 *
	 * @param string $query        	
	 */
	private function __construct($query) {
		$this->_query = $query;
		$url = sprintf ( self::ENDPOINT, $query );
		$response = $this->_get ( $url );
		$response && $response = json_decode ( $response, true );
		if (0 == $response ['errNo'] && isset ( $response ['data'] ['weather'] ['content'] )) {
			$this->_weather = $response ['data'] ['weather'] ['content'];
		} else {
			throw new Exception ( 'response_error' );
		}
	}
	/**
	 * format current weather info
	 *
	 * @param Workflows $wl        	
	 */
	public function current(Workflows $wl = null) {
		$week = isset ( $this->_weather ['week'] ) ? $this->_weather ['week'] : '';
		$city = isset ( $this->_weather ['city'] ) ? $this->_weather ['city'] : $this->_query;
		$currenttemp = isset ( $this->_weather ['currenttemp'] ) ? $this->_weather ['currenttemp'] : '未知';
		$source = isset ( $this->_weather ['source'] ['name'] ) ? $this->_weather ['source'] ['name'] : '未知';
		$weather_source_url = isset ( $this->_weather ['calendar'] ['weatherSourceUrl'] ) ? $this->_weather ['calendar'] ['weatherSourceUrl'] : null;
		$lunar = isset ( $this->_weather ['calendar'] ['lunar'] ) ? $this->_weather ['calendar'] ['lunar'] : null;
		if (isset ( $wl )) {
			$wl->result ( $city, $weather_source_url, "当前气温：{$currenttemp} @ {$city}", "{$week} {$lunar} 数据来自:{$source} ", '' );
		}
	}
	/**
	 * format the weather of today and next five days
	 *
	 * @param Workflows $wl        	
	 */
	public function days(Workflows $wl = null) {
		foreach ( self::$_days as $day => $day_title ) {
			$day_weather = $this->_weather [$day];
			$condition = $day_weather ['condition'];
			$temp = $day_weather ['temp'];
			$wind = $day_weather ['wind'];
			$pm25 = isset ( $day_weather ['pm25'] ) ? ( int ) $day_weather ['pm25'] : 0;
			empty ( $pm25 ) && $pm25 = '未知';
			$date = $day_weather ['date'];
			$time = $day_weather ['time'];
			$icon = $day_weather ['imgs'] [0];
			if (isset ( $wl )) {
				$wl->result ( $day, $day_weather ['link'], "{$day_title}{$time} {$condition}", "气温{$temp} {$wind} PM2.5 {$pm25} {$date}", 'icon/' . $icon . '.jpg' );
			}
		}
	}
	/**
	 * instance a client by query
	 *
	 * @param string $query        	
	 * @return Baidu
	 */
	public static function instance($query) {
		$query = urldecode ( $query );
		if (! isset ( self::$_instances [$query] )) {
			self::$_instances [$query] = new static ( $query );
		}
		return self::$_instances [$query];
	}
	/**
	 * get the response from the url
	 *
	 * @param string $url        	
	 * @return Ambigous <boolean, mixed>
	 */
	private function _get($url) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)' );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
		curl_setopt ( $ch, CURLOPT_TIMEOUT, 5 );
		$data = curl_exec ( $ch );
		$httpcode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
		curl_close ( $ch );
		return ($httpcode >= 200 && $httpcode < 300) ? $data : false;
	}
}