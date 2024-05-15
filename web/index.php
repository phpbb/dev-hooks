<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

require __DIR__.'/../vendor/autoload.php';

try {
    (new Phpbb\DevHooks\ContainerBuilder)
        ->build()['kernel']
        ->handle(file_get_contents('php://input'), $_SERVER);
} catch (\UnexpectedValueException $e) {
    http_response_code(403);
    echo $e->getMessage();
}
