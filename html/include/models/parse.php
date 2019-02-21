<?php

namespace Model;

class parse
{
	public static function parse_email(string $url, int $deep, int &$email_max, int $parent = null) : void
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
			$email_max = 0;
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

				self::parse_email($tmp_url, $deep-1, $email_max, $parent);
			}
		}
	}

	public static function get_result(string $url) : array
	{
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

		$urls = [];

		if( $p->rowCount() ) {
			$urls = [];
			while($row = $p->fetch()) {
				$row->emails = json_decode($row->emails);
				$urls[] = $row;
			}
		}

		return $urls;
	}
}