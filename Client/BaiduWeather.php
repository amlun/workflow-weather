<?php
/**
 * Baidu Weather Client
 *
 * @author lunweiwei
 *        
 */
class BaiduWeather {
	const ENDPOINT = 'https://www.baidu.com/home/other/data/weatherInfo?city=%s';
	const LIFETIME = 3600;
	const QUERY_DEFAULT = 'auto';
	private static $_instances = [ ];
	private static $_days = [ 
			'today' => '今日',
			'tomorrow' => '明天',
			'thirdday' => '后天',
			'fourthday' => '',
			'fifthday' => '' 
	];
	private $_workflow;
	private $_query;
	private $_weather;
	/**
	 * set the query and workflow
	 *
	 * @param string $query        	
	 */
	private function __construct(Workflows $wl, $query = null) {
		$this->_workflow = $wl;
		$this->_query = $query;
		$this->initWeather ();
	}
	/**
	 * get the weather data
	 *
	 * @throws Exception
	 */
	private function initWeather() {
		$weather = $this->_workflow->read ( $this->_query );
		$file_time = $this->_workflow->filetime ( $this->_query );
		if (! empty ( $weather ) && $file_time && time () - $file_time <= self::LIFETIME) {
			$this->_weather = $weather;
			return;
		}
		if ($this->_query == self::QUERY_DEFAULT) {
			$url = sprintf ( self::ENDPOINT, '' );
		} else {
			$url = sprintf ( self::ENDPOINT, $this->_query );
		}
		$response = $this->_get ( $url );
		$response && $response = json_decode ( $response, true );
		if (is_array($response) && 0 == $response ['errNo'] && isset ( $response ['data'] ['weather'] ['content'] )) {
			$this->_weather = $response ['data'] ['weather'] ['content'];
			$this->_workflow->write ( $this->_weather, $this->_query );
		} elseif (! empty ( $weather ) && $file_time) {
			$this->_weather = $weather;
		} else {
			throw new Exception ( 'response_error' );
		}
	}
	/**
	 * format current weather info
	 *
	 * @param Workflows $wl        	
	 */
	public function current() {
		$week = isset ( $this->_weather ['week'] ) ? $this->_weather ['week'] : '';
		$city = isset ( $this->_weather ['city'] ) ? $this->_weather ['city'] : $this->_query;
		$currenttemp = isset ( $this->_weather ['currenttemp'] ) ? $this->_weather ['currenttemp'] : '未知';
		$source = isset ( $this->_weather ['source'] ['name'] ) ? $this->_weather ['source'] ['name'] : '未知';
		$weather_source_url = isset ( $this->_weather ['calendar'] ['weatherSourceUrl'] ) ? $this->_weather ['calendar'] ['weatherSourceUrl'] : null;
		$lunar = isset ( $this->_weather ['calendar'] ['lunar'] ) ? $this->_weather ['calendar'] ['lunar'] : null;
		if (isset ( $this->_workflow )) {
			$this->_workflow->result ( 'city_' . $city, $weather_source_url, "当前气温：{$currenttemp} @ {$city}", "{$week}，{$lunar}， 数据来自：{$source} ", '' );
		}
	}
	/**
	 * format the weather of today and next five days
	 *
	 * @param Workflows $wl        	
	 */
	public function days() {
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
			if (isset ( $this->_workflow )) {
				$this->_workflow->result ( 'weather_' . $day, $day_weather ['link'], "{$time} {$day_title}，{$condition}", "气温{$temp}，{$wind}，PM2.5：{$pm25}，{$date}", 'icon/' . $icon . '.jpg' );
			}
		}
	}
	/**
	 * instance a client by query
	 *
	 * @param string $query        	
	 * @return Baidu
	 */
	public static function instance(Workflows $wl, $query = null) {
		if (empty ( $query ))
			$query = self::QUERY_DEFAULT;
		$query = urlencode ( $query );
		if (! isset ( self::$_instances [$query] )) {
			self::$_instances [$query] = new static ( $wl, $query );
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