<?php

//--------------------------------------------------------------
// Singltone для бд
//--------------------------------------------------------------
function PDO(string $database = null) : PDO
{
	static $PDO;

	if( $PDO === null || $database ) { 
		$opt = [
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
			PDO::ATTR_ERRMODE			 => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_TIMEOUT			 => 5
		];
		try{
			$className = Config()->develop ? 'pgsql' : 'PDO';
			$PDO = new $className( 'mysql:dbname=' . ($database ?: Config()->db->name) . ';host=' . Config()->db->host, Config()->db->user, Config()->db->pass, $opt );
			$PDO->exec("set names 'utf8'"); 
		} catch( PDOException $e ) {
			die(include Template::dir() . '/offline.php'); 
		}
	
	}
	return $PDO;
}

function memcache() : Memcache
{
	static $memcache;

	if( $memcache === null ) {
		try {
			$memcache = new Memcache();
			$memcache->connect(Config()->memcache->host, Config()->memcache->port ?? 11211, 1);
		} catch(Exception $e) {
			die(include Template::dir() . '/offline.php'); 
		}
	}

	return $memcache;
}

//--------------------------------------------------------------
// Получаем конфиг
//--------------------------------------------------------------
function Config() : stdClass
{
	static $config;

	if( $config === null ) {
		$config = json_decode( file_get_contents( ROOT . '/include/config/config.json' ) );
	}

	return $config;
}

//--------------------------------------------------------------
// Генерация уникальной строки 1-32 символа
//--------------------------------------------------------------
function unique( int $size = 32 ) : string
{
	return substr( md5( uniqid( mt_rand(), 1 ) ), 0, $size);
}


//--------------------------------------------------------------
// CURL
//--------------------------------------------------------------
function curl( string $url, array $post = null)
{
	$cl = curl_init( $url );
	curl_setopt($cl, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($cl, CURLOPT_HEADER, 0);
	curl_setopt($cl, CURLOPT_TIMEOUT, 5);
	curl_setopt($cl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($cl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 OPR/57.0.3098.116');
	curl_setopt($cl, CURLOPT_COOKIEJAR, __DIR__ . '/cookie');
	curl_setopt($cl, CURLOPT_COOKIEFILE, __DIR__ . '/cookie');
	if( !empty($post) ) {
		curl_setopt($cl, CURLOPT_POST, 1); 
		curl_setopt($cl, CURLOPT_POSTFIELDS, $post);
	} else {
		curl_setopt($cl, CURLOPT_POST, 0);
	}
	if( preg_match('#^https://#', $url) ) {
		curl_setopt($cl, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($cl, CURLOPT_SSL_VERIFYHOST, 0);
	}
	$ex=curl_exec($cl);
	curl_close($cl);
	return $ex;
}

//--------------------------------------------------------------
// Запись ошибок в файл
//--------------------------------------------------------------
function logging( string $text, string $file = '', int $line = 0 )
{
	$file = str_replace( ROOT, '', $file );  
	$data = '[' . date('d.m.Y H:i:s') . "] $text $file:$line (" . Module::$module . "/" . Module::$method. ")\n";
	file_put_contents( ROOT . '/include/error.log', $data, FILE_APPEND );
}

function location($url)
{
	die(header('Location: ' . $url));
}

//--------------------------------------------------------------
// Логер запросов
//--------------------------------------------------------------
function debug( float $timeStart, int $memoryStart ) : string
{
	$all_time = round( (microtime(true) - $timeStart) * 1000, 2);
	$mem = round( (memory_get_usage() - $memoryStart) / 1024, 2);
	$db_time = PDO()->getTime();
	$data  = "\n[All time: $all_time ms]";
	$data .= "\n[DB time: $db_time ms (" . round($db_time * 100 / $all_time, 1) . "%)]";
	$data .= "\n[Query count: " . PDO()->getQCount() . "]";
	$data .= "\n[Memory: $mem KB]";
	$data .= "\n[Path: /".Module::$module."/".Module::$method."]\n";
	return $data;
}