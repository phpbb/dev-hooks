<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks\Listener;

class AddTagSplit implements Listener
{
	protected $cacheDir;

	public function __construct($cacheDir)
	{
		$this->cacheDir = $cacheDir;
	}

	public function handle(array $data)
	{
		if ($data['ref_type'] == 'tag' && preg_match('#^refs/tags/release-[3-9]#', $data['ref'])) {
			if (!file_exists($this->cacheDir . '.split_tags')) {
				$fp = fopen($this->cacheDir . '.split_tags', 'wb');
				fwrite($fp, sha1($data['ref']));
				fclose($fp);
			}
		}
	}
}
