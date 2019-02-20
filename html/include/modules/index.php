<?php

namespace Module;

use \Template;
use \JSON;

class index extends \Module
{
	public function __construct() 
	{
		PDO()->query("SELECT 1");
	}

	public function index(\Request $param)
	{
		Template::exec('index.php');
	}

	public function parse()
	{
		$url = $_POST->trim('url');
		$deep = $_POST->int('deep') ?: 0;
		$email_max = $_POST->int('email_max') ?: 10;

		if( empty($url) ) {
			throw new JSON('error', 'empty url');
		}
		$result = [];

		$p = PDO()->prepare("
			WITH RECURSIVE tree (id, url, emails, parent_id, i) as (
  				SELECT id, url, emails, parent_id, 0
				FROM urls
				WHERE url = ?
			UNION ALL
				SELECT u.id, u.url, u.emails, u.parent_id, i+1
				FROM urls u
				INNER JOIN tree ON (u.parent_id = tree.id)
			)
			SELECT url, emails, i FROM tree
		");
		$p->execute([ $url ]);

		if( $p->rowCount() ) {
			$urls = [];
			while($row = $p->fetch()) {
				$row->emails = json_decode($row->emails);
				$urls[] = $row;
			}
			throw new JSON('success', $urls);
		}

		try {
			$res = self::get_email($url, $deep, $email_max);
			throw new JSON('success', $res);
		} catch(\LogicException $e) {
			throw new JSON('error', $e->getMessage());
		}
	}


	// по хорошему эта функция должна быть в модели
	private static function get_email($url, $deep, &$email_max, $parent = null)
	{
		$res = curl($url);

		if( !$res ) {
			throw new \LogicException('error url');
		}

		// регулярку решил написать простую, более сложную можно найти например на https://login.sendpulse.com/js/emailservice.js  => validEmail()
		preg_match_all('#[a-z0-9_-]+@[a-z0-9]+\.[a-z]{2,5}#i', $res, $emails);
		$emails[0] = array_values(array_unique($emails[0]));

		if(count($emails[0]) > $email_max) {
			$data = array_slice($emails[0], 0, $email_max);
			return $data;
		} else {
			$data = $emails[0];
			$email_max -= count($emails[0]);
		}

		$p = PDO()->prepare("INSERT IGNORE INTO urls (url, parent_id, emails) values (?, ?, ?)");
		$p->execute([ $url, $parent, json_encode($data) ]);

		$parent = PDO()->lastInsertId();

		if( $deep ) {
			preg_match_all('#<a href="(.+?)"#i', $res, $url_data);
			$url_data = array_unique(array_filter($url_data[1], function($u) use($url) {
				return $u != '/' && (strpos($u, '/') === 0 || strpos($u, $url));
			}));

			
			foreach($url_data as $link) {
				if( strpos($link, '/') === 0 ) {
					$p_url = parse_url($url);
					$tmp_url = $p_url['scheme'] . '://' . $p_url['host'] . $link;
				} else {
					$tmp_url = $url . $link;
				}

				self::get_email($tmp_url, $deep-1, $email_max, $parent);
			}
		}
	}
}