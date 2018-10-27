<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks\Listener;

class PushSplitRepo implements Listener
{
	protected $cacheDir;

	protected $supportedRefs = [
		'3.0.x',
		'3.1.x',
		'3.2.x',
		'master',
	];

	public function __construct($cacheDir)
	{
		$this->cacheDir = $cacheDir;
	}

	public function handle(array $data)
	{
		$cleanedRef = str_replace('refs/heads/', '', $data['ref']);

		// Create file for updating ref and let cron script handle the actual splitting
		if (in_array($cleanedRef, $this->supportedRefs)) {
			if (!file_exists($this->cacheDir . '.split_' . $cleanedRef)) {
				$fp = fopen($this->cacheDir . '.split_' . $cleanedRef, 'wb');
				fwrite($fp, sha1($cleanedRef));
				fclose($fp);
			}
		}
	}
}
