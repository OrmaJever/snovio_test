<?php

abstract class Module
{
	private static $modules = []; 		// список загруженных модулей
	private static $langName = 'ru'; 	// имя языка
	public  static $lang; 				// языковой пакет
	public  static $dependency = []; 	// зависимости модуля
	const SYSTEM = null; 				// системный модуль, не вызываеться из url, только с кода.
	
	public static $module;
	public static $method;

	public function __construct( string $module, string $method, array $param ) {}


	//--------------------------------------------------------------
	// Если вызван несуществующий метод
	//--------------------------------------------------------------
	public function __call( string $name, array $args )
	{
		if( !empty( $args ) and in_array( $name, get_class_methods( $this ) ) ) {
			return call_user_func_array([ $this, $name ], $args);
		} else {
			$this->page404();
		}
	}


	//--------------------------------------------------------------
	// Получение csrf токена
	//--------------------------------------------------------------
	public static function getCSRF($current = false)
	{
		return $current ? $_SESSION['_csrf'] : ($_SESSION['_csrf'] = unique());
	}


	//--------------------------------------------------------------
	// Проверка csrf токена
	//--------------------------------------------------------------
	public function csrf( bool $bool = false, bool $ret = false )
	{
		if( !isset($_SESSION['_csrf']) or $_REQUEST['csrf'] != $_SESSION['_csrf'] or $bool ) {
			if($ret) {
				return false;
			} else {
				throw new JSON('error', self::$lang->global['access_denied']);
			}
		}
		return true;
	}

	//--------------------------------------------------------------
	// Читаем или пишем данные из файлового хранилища (json_db)
	//--------------------------------------------------------------
	public static function json( string $name, array $data = [] )
	{
		$path = ROOT . "/include/config/$name.json";

		if( !is_file( $path ) ) {
			return false;
		}
		if( empty( $data ) ) {
			return json_decode( file_get_contents( $path ), true );
		} else {
			file_put_contents( $path, json_encode( $data, JSON_PRETTY_PRINT ) );
		}
	}


	//--------------------------------------------------------------
	// Получения модуля из списка установленных
	//--------------------------------------------------------------
	public static function getModule( string $name = null )
	{
		if( $name !== null ) {
			return isset( self::$modules[ $name ] ) ? self::$modules[ $name ] : null;
		} else {
			return self::$modules;
		}
	}


	//--------------------------------------------------------------
	// Добавление модулей в список
	//--------------------------------------------------------------
	public static function addModule( string $name, Module $value )
	{
		self::$modules[ $name ] = $value;
	}


	//--------------------------------------------------------------
	// Установка языка
	//--------------------------------------------------------------
	public static function setLang( string $name )
	{
		self::$langName = in_array($name, Config()->langs) ? $name : Config()->default_lang;
	}


	//--------------------------------------------------------------
	// Получение языка
	//--------------------------------------------------------------
	public static function getLang() : string
	{
		return self::$langName;
	}


	public static function page404()
	{
		Template::exec('404.php');
	}


	//--------------------------------------------------------------
	// Загрузка языкового пакета
	//--------------------------------------------------------------
	public static function loadLang( string $packName, string $lang = '' )
	{
		if( self::$lang === null ) {
			self::$lang = new stdClass();
		}
		
		if( isset( self::$lang->$packName ) ) {
			return;
		}

		if( $lang == '' ) {
			$lang = self::$langName;
		}

		$langDir = ROOT . '/include/lang/';
		$langDir .= file_exists( $langDir . $lang ) ? $lang : Config()->default_lang;

		$filename = is_file( "$langDir/$packName.json" ) ? "$langDir/$packName.json" : "$langDir/index.json";
		$arr = json_decode(file_get_contents($filename), true);
		self::$lang->$packName = new Request( $arr );
	}
}