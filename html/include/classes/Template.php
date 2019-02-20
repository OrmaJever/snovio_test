<?php

class Template
{
	public static $data = [
		'title' => '',
		'meta' => [], 
		'lang' => []
	];


	//--------------------------------------------------------------
	// Папка с шаблонами
	//--------------------------------------------------------------
	public static function dir() : string
	{
		return ROOT . "/include/template/";
	}

	//--------------------------------------------------------------
	// Установка переменных которые будут поступать в любой шаблон
	//--------------------------------------------------------------
	public static function setData( $data, $value = null )
	{
		if( is_string( $data ) and $value !== null ) {
			self::$data[ $data ] = $value;
		} else {
			self::$data += $data;
		}
	}

	//--------------------------------------------------------------
	// Добавляем языковые переменные в шаблон
	//--------------------------------------------------------------
	public static function setLang(string $name, string $val)
	{
		self::$data['lang'][$name] = $val;
	}

	//--------------------------------------------------------------
	// Выполнение и вывод шаблона
	//--------------------------------------------------------------
	public static function exec( string $name, array $data = [] )
	{
		$_filePath = self::dir();

		// получение имени вызвавшего модуля
		$backtrace = debug_backtrace();
		$template = basename( $backtrace[0]['file'] , '.php');

		// если нету папки с именем модуля то берём из общего каталога
		if( !is_dir( dirname( $_filePath . "/$template/$name" ) ) ) {
			$_filePath .= "/$name";
		} else {
			$_filePath .= "/$template/$name";
		}
		if( !is_file( $_filePath ) ) {
			throw new Exception('Template file not exists');
		}

		extract( $data + self::$data );

		include $_filePath;
	}
}
