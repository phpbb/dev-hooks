<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

require __DIR__.'/../vendor/autoload.php';

(new Phpbb\DevHooks\ContainerBuilder)
    ->build()['kernel']
    ->handle(file_get_contents('php://input'), $_SERVER);
