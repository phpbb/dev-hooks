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
include '../includes/functions_utils.php';

function label_exists(\Github\Client $client, $repo_owner, $repository, $label)
{
	$labels = $client->api('issues')->labels()->all($repo_owner, $repository);
	foreach ($labels as $label_data)
	{
		if ($label_data['name'] === $label)
		{
			return true;
		}
	}

	return false;
}

$body = file_get_contents('php://input');
$signature = 'sha1=' . hash_hmac("sha1", $body, $github_webhooks_secret, false);
if (!isset($_SERVER['HTTP_X_HUB_SIGNATURE']) || !hash_equals($signature, strtolower($_SERVER['HTTP_X_HUB_SIGNATURE'])))
{
	die('Good bye!');
}

$protected_labels = array(
	'3.0 (Olympus)',
	'3.1 (Ascraeus)',
	'3.2 (Rhea)',
);

$data = json_decode($body, true);
if ($data['issue']['user']['id'] === $data['comment']['user']['id'])
{
	$message_parts = explode(' ', $data['comment']['body']);
	$action = array_shift($message_parts);
	$label = implode(' ', $message_parts);
	if (!in_array($label, $protected_labels))
	{
		$client = new Github\Client();

		if (label_exists($client, $data['repository']['owner']['login'], $data['repository']['name'], $label))
		{
			$client->authenticate($github_api_token, Github\Client::AUTH_HTTP_TOKEN);
			if ($action === '!set')
			{
				$client->api('issue')->labels()->add($data['repository']['owner']['login'], $data['repository']['name'], $data['issue']['number'], $label);
				echo "$label set for issue " . $data['issue']['number'];
			}
			else if ($action === '!unset')
			{
				$client->api('issue')->labels()->remove($data['repository']['owner']['login'], $data['repository']['name'], $data['issue']['number'], $label);
				echo "$label removed for issue " . $data['issue']['number'];
			}
			else
			{
				echo 'Unsupported action.';
			}
		}
		else
		{
			echo 'Non-existent label.';
		}
	}
	else
	{
		echo 'Protected label.';
	}
}
