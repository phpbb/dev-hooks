<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks;

use Pimple\Container;

class Kernel
{
    protected $githubWebhooksSecret;
    protected $container;

    public function __construct($githubWebhooksSecret, Container $container)
    {
        $this->githubWebhooksSecret = (string) $githubWebhooksSecret;
        $this->container = $container;
    }

    public function handle($body, array $server)
    {
        if (!isset($server['HTTP_X_GITHUB_EVENT'])) {
            throw new \UnexpectedValueException('Missing X-Github-Event header.');
        }
        $this->checkMessageAuthentication($body, $server);
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            throw new \UnexpectedValueException('Expected payload to be array.');
        }

        // Note: headers are not under the Message Authentication Code (MAC)
        // Also see https://github.com/github/github-services/issues/499
        $servicePrefix = sprintf('listener.%s.', $server['HTTP_X_GITHUB_EVENT']);
        foreach ($this->container->keys() as $serviceName) {
            if (strpos($serviceName, $servicePrefix) === 0) {
                $this->container[$serviceName]->handle($payload);
            }
        }
    }

    protected function checkMessageAuthentication($body, array $server)
    {
        if (!isset($server['HTTP_X_HUB_SIGNATURE'])) {
            throw new \UnexpectedValueException('Missing X-Hub-Signature header');
        }
        $theirs = strtolower($server['HTTP_X_HUB_SIGNATURE']);
        $ours = 'sha1='.hash_hmac('sha1', $body, $this->githubWebhooksSecret);
        if (!hash_equals($ours, $theirs)) {
            throw new \UnexpectedValueException('Incorrect X-Hub-Signature header');
        }
    }
}
