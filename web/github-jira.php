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

// This file is generated by Composer
require_once '../vendor/autoload.php';

include '../config/github-api.php';

function containsJiraKey($message)
{
    return preg_match('#(PHPBB3|SECURITY)-(\d+)#', $message);
}

$equals = function($a, $b) {
	$sha256 = function($data) {
		return hash('sha256', $data, true);
	};
	return $sha256($a) === $sha256($b);
};

$body = file_get_contents('php://input');
$signature = 'sha1=' . hash_hmac("sha1", $body, $github_webhooks_secret, false);
if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE']) || !$equals(strtolower($_SERVER['HTTP_X_HUB_SIGNATURE']), $signature))
{
	die('Good bye!');
}

$data = json_decode($body, true);
if ($data['action'] == 'opened')
{
    $title = $data['pull_request']['title'];
    $body = $data['pull_request']['body'];

    if (!containsKey($title) && !containsKey($body)) {

        $ticketId = 'FOOBAR';
        $ticketLink = 'https://tracker.phpbb.com/browse/'.$ticketId;

        $newBody = $body ? $body."\r\n\r\n".$ticketLink : $ticketLink;
        $client = new Github\Client();
        $client->authenticate($github_api_token, Github\Client::AUTH_HTTP_TOKEN);
        $client->api('pull_request')->update($data['repository']['owner']['login'], $data['repository']['name'], $data['pull_request']['number'], array(
            'title' => $title,
            'body' => $newBody,
        ));
    }
}
