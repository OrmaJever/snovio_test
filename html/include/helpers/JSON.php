<?php

class JSON extends RuntimeException
{
	private $json;

	public function __construct( string $status, $data = [], $ext = [] )
	{
		$this->data = json_encode([
			'status' => $status,
			'data'	 => $data
		] + $ext );
	}

	public function __toString() : string
	{
		return $this->data;
	}
}