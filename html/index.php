<?php

declare(strict_types=1);

$memoryStart = memory_get_usage();
$timeStart = microtime( true );

error_reporting( E_ALL | E_STRICT );
session_start();

header('Content-type: text/html; charset=UTF-8');

const ROOT = __DIR__;
require ROOT . '/include/function.php';



//--------------------------------------------------------------
// Подгрузка классов
//--------------------------------------------------------------
spl_autoload_register(function( string $className ) {
	if( is_file( ROOT . "/include/classes/$className.php" ) ) {
		require_once ROOT . "/include/classes/$className.php";
	}
});

if(isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
	$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
}

//--------------------------------------------------------------
// Таблица редиректов
//--------------------------------------------------------------
$redirect = (array)Module::json('redirect');

foreach( $redirect as $from => $to ) {
	if(strpos($_SERVER['REQUEST_URI'], $from) === 0) {
		$_SERVER['REQUEST_URI'] = str_replace($from, $to, $_SERVER['REQUEST_URI']);
	}
}

$_GET		= new Request( $_GET );
$_POST		= new Request( $_POST );
$_COOKIE	= new Request( $_COOKIE );
$_REQUEST	= new Request( $_REQUEST );

define('URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/');


// Язык и шаблон можно менять в конструкторе любого модуля вызовом Module::setLang() и Template::setTemplate()
Module::setLang( $_COOKIE['language'] ?: Config()->default_lang );


//--------------------------------------------------------------
// Устанавливаем пользовательский обработчик ошибок который кидает исключение
//--------------------------------------------------------------
set_error_handler( function( $code, $mess, $file, $line )
{
	$error_string = [
		E_NOTICE 	 => 'Notice: ', 
		E_WARNING 	 => 'Warning: ', 
		E_DEPRECATED => 'Deprecated: ', 
		E_STRICT 	 => 'Strict: '
	];
	if(Config()->develop) {
		echo "{$error_string[$code]} $mess $file:$line";
	}
	throw new ErrorException( $error_string[$code] . $mess, $code, 1, $file, $line ); 

}, E_ALL | E_STRICT );



//--------------------------------------------------------------
// Парсим URL
//--------------------------------------------------------------
$url = parse_url($_SERVER['REQUEST_URI']);
$URI = explode( '/', ltrim( (string)$url['path'], '/' ) );

Module::$module = !empty( $URI[0] ) ? $URI[0] : 'index';
Module::$method = !empty( $URI[1] ) ? $URI[1] : 'index';
unset($URI[0], $URI[1]);



//--------------------------------------------------------------
// Получаем и записываем GET переменные (url: ?key1=val1&key2=val2)
//--------------------------------------------------------------
$_GET->clean();
if( !empty( $url['query'] ) ) 
{
	$p = [];
	parse_str( $url['query'], $p );
	foreach( $p as $k => $v ) {
		$_GET[ $k ] = $v;
	}
}



//--------------------------------------------------------------
// Получаем список модулей
//--------------------------------------------------------------
$mod = array_slice( scandir( ROOT . '/include/modules/' ), 2 );
$availableModules = [];

foreach( $mod as $v ) {

	$path = ROOT . '/include/modules/';
	$path .= is_file( $path . $v ) ? $v : "$v/$v.php";

	include_once $path;

	$className = basename( $v, '.php' );

	if( is_subclass_of( '\\Module\\' . $className, 'Module' ) ) {
		$availableModules[] = '\\Module\\' . $className;
	}
}

//--------------------------------------------------------------
// Проверяем зависимости и инстализируем модули
//--------------------------------------------------------------
foreach( $availableModules as $mod ) {
	try {
		foreach( $mod::$dependency as $dependency ) {
			if( !in_array( $dependency, $availableModules ) ) {
				throw new RuntimeException(); // зависимости не соблюдены, следующий модуль
			}
		}

		Module::addModule( str_replace('\\Module\\', '', $mod), new $mod( Module::$module, Module::$method, $URI ) );
	} catch( RuntimeException $e ) {
		logging( $e->getMessage(), $e->getFile(), $e->getLine() );
	} catch( PDOException $e ) {

		logging( 'SQL error: ' . $e->getMessage(), $e->getFile(), $e->getLine());
		header('500 Internal Server Error');
		die('Server error');

	} catch( Exception $e ) {

		logging( $e->getMessage(), $e->getFile(), $e->getLine() );
		header('500 Internal Server Error');
		die('Server error');
	}
}

//--------------------------------------------------------------
// Подгрузка языкового пакета
//--------------------------------------------------------------
Module::loadLang( 'global' );
Module::loadLang( Module::$module );


//--------------------------------------------------------------
// Вызов указанного модуля (url: /className/MethodName)
//--------------------------------------------------------------
try {
	$className = "\\Module\\" . Module::$module;
	if( ($Module = Module::getModule( Module::$module )) && !$className::SYSTEM ) {
		if( strpos(Module::$method, '__') !== 0 ) { // не вызываем служебные методы (__construct, __install, ...)
			$Module->{Module::$method}(new Request($URI));
		} else {
			location('/404');
		}
	} else {
		if( strpos(Module::$module, '__') !== 0 ) { // не вызываем служебные методы (__construct, __install, ...)
			Module::getModule('index')->{Module::$module}(new Request($URI));
		} else {
			location('/404');
		}
	}
} catch( PDOException $e ) {
	
	logging( 'SQL error: ' . $e->getMessage(), $e->getFile(), $e->getLine());
	header('500 Internal Server Error');
	die('Server error');

} catch( JSON $json ) {

	header('Content-Type: application/json');
	die( $json );

} catch( Exception $e ) {

	logging( $e->getMessage(), $e->getFile(), $e->getLine() );
	header('500 Internal Server Error');
	die('Server error');
}

if(Config()->develop) {
	echo "<!--", debug($timeStart, $memoryStart), "-->";
}