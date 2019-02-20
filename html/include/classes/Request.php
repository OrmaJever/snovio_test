<?php

class arr implements ArrayAccess, Iterator, Countable
{
	private $data;

 	public function __construct( array &$var )
 	{
		$this->data = $var;
	}

	public function clean()
	{
		$this->data = [];
	}

	public function __invoke() : array
	{
		return $this->data;
	}

	// Countable
	public function count() : int
	{
		return count( $this->data );
	}

	// ArrayAccess
	public function offsetExists ( $offset ) : bool
	{
		 return isset( $this->data[ $offset ] );
	}

	public function offsetUnset ( $offset )
	{
		unset( $this->data[ $offset ] );
	}

	public function offsetGet ( $offset )
	{
		return isset($this->data[ $offset ]) ? $this->data[ $offset ] : null;
	}

	public function offsetSet ( $offset, $value )
	{

		if( is_array( $value ) ) {
			$value = new self( $value );
		}

		if ( $offset === null ) {
			$this->data[] = $value;
		} else {
			$this->data[ $offset ] = $value;
		}
	}

	// Iterator
	public function rewind()
	{
		return reset( $this->data );
	}

	public function current()
	{
		return current( $this->data );
	}

	public function next()
	{
		return next( $this->data );
	}

	public function key()
	{
		return key( $this->data );
	}

	public function valid() : bool
	{
		return key( $this->data ) !== null;
	}
}

class request extends arr
{
	public function safe( string $key ) : string
	{
		$val = $this->offsetGet($key);
		if(is_array($val) || is_object($val)) {
			return '';
		}
		return htmlspecialchars( (string)$val, ENT_QUOTES );
	}

	public function trim( string $key ) : string
	{
		$val = $this->offsetGet($key);
		if(is_array($val) || is_object($val)) {
			return '';
		}
		return trim( (string)$val, "\x20\t\n\r\0 ");
	}

	public function inArray( string $key, $defVal, array $arr )
	{
		$val = $this->offsetGet($key);
		if(is_array($val) || is_object($val)) {
			return $defVal;
		}
		return in_array((string)$val, $arr) ? (string)$val : $defVal;
	}

	public function split( string $key, string $sep ) : array
	{
		$val = $this->offsetGet($key);
		if(is_array($val) || is_object($val)) {
			return [];
		}
		return explode($sep, (string)$val);
	}

	public function bool( string $key ) : bool
	{
		$val = $this->offsetGet( $key );
		if(is_array($val) || is_object($val)) {
			return false;
		}
		return (bool)$val;
	}

	public function int( string $key, $min = null, $max = null ) : int
	{
		$val = $this->offsetGet( $key );
		if(is_array($val) || is_object($val)) {
			return 0;
		}
		$val = (int)$val;
		if( $min !== null and $val < $min ) {
			return $min;
		} elseif( $max !== null and $val > $max ) {
			return $max;
		}
		if( !$min && !$max && $val > 2147483647 ) {
			$val = 2147483647;
		}
		return $val;
	}
}