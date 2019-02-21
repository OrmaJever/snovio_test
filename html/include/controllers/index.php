<?php

namespace Module;

require ROOT . '/include/models/parse.php'; // здесь надо сделать автоподгрузку моделей, но пока просто подключим

use \Template;
use \JSON;
use \Model\parse;

class index extends \Module
{
	public function __construct() 
	{
		PDO()->query("SELECT 1");
	}

	public function index(\Request $param) : void
	{
		Template::exec('index.php');
	}

	public function parse(\Request $param) : void
	{
		$url = $_POST->trim('url');
		$deep = $_POST->int('deep') ?: 0;
		$email_max = $_POST->int('email_max') ?: 10;

		if( empty($url) ) {
			throw new JSON('error', 'empty url');
		}

		$result = parse::get_result($url);

		if( !empty($result) ) {
			throw new JSON('success', $result);
		}

		try {
			parse::parse_email($url, $deep, $email_max);
			$result = parse::get_result($url);
			throw new JSON('success', $result);
		} catch(\LogicException $e) {
			throw new JSON('error', $e->getMessage());
		}
	}
}