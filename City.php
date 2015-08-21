<?php
class City {
	protected static $_dict;
	protected static $_appends = [ ];
	protected static $_settings = [ 
			'delimiter' => '',
			'accent' => true 
	];
	private static $_instance;
	public static function instance() {
		if (is_null ( self::$_instance )) {
			self::$_instance = new static ();
		}
		return self::$_instance;
	}
	private function __construct() {
		if (is_null ( self::$_dict )) {
			self::$_dict = require_once __DIR__ . '/data/dict.php';
		}
	}
	public function getName($short) {
		$short = strtolower ( $short );
		if (isset ( self::$_dict [$short] )) {
			return self::$_dict [$short];
		}
		return $short;
	}
}