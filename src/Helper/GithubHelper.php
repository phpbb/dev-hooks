<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks\Helper;

use Github\AuthMethod;
use Github\Client;

class GithubHelper
{
    protected Client $client;
    protected string $apiToken;
    protected bool $authed = false;

    public function __construct(Client $client, string $apiToken)
    {
        $this->client = $client;
        $this->apiToken = $apiToken;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getAuthenticatedClient(): Client
    {
        if (!$this->authed) {
            $this->client->authenticate(
                $this->apiToken,
                AuthMethod::ACCESS_TOKEN
            );
        }
        return $this->client;
    }
}
