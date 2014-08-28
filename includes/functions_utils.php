<?php
/**
*
* This file is part of the phpBB Forum Software package.
*
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*
* For full copyright and license information, please see
* the docs/CREDITS.txt file.
*
*/

if(!function_exists('hash_equals')) {
	function hash_equals($a, $b) {
		$sha256 = function($data) {
			return hash('sha256', $data, true);
		};
		return $sha256($a) === $sha256($b);
	}
}
