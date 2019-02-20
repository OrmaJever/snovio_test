<?php

class pgsql extends PDO
{
	private static $time = 0;
	private static $QCount = 0;

	public function query( $sql )
	{
		$time = microtime(true);
		$res = parent::query($sql);
		self::setTime( microtime(true) - $time );
		self::setQCount();
		return $res;
	}

	public function prepare( $sql, $options = [] )
	{
		$time = microtime(true);
		$obj = parent::prepare( $sql, $options );
		self::setTime( microtime(true) - $time );
		return new pgsqlStatement( $obj );
	}

	public static function getTime()
	{
		return round( self::$time * 1000, 2);
	}

	public static function getQCount()
	{
		return self::$QCount;
	}

	public static function setTime( $time )
	{
		self::$time += $time;
	}

	public static function setQCount()
	{
		++self::$QCount;
	}
}

class pgsqlStatement
{
	private $statement;

	public function __construct( PDOStatement $obj ) {
		$this->statement = $obj;
	}

	public function execute( $params = null )
	{
		$time = microtime(true);
		$res = $this->statement->execute($params);
		pgsql::setTime( microtime(true) - $time );
		pgsql::setQCount();
		return $res;
	}

	public function __call($name, $params)
	{
		return call_user_func_array([$this->statement, $name], $params);
	}

	public function __get( $name )
	{
		return $this->statement->$name;
	}

	public function __set( $name, $val )
	{
		return $this->statement->$name = $val;
	}
}