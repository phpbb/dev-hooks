<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks\Helper;

use Github\Client;

class GithubHelper
{
    protected $client;
    protected $apiToken;
    protected $authed = false;

    public function __construct(Client $client, $apiToken)
    {
        $this->client = $client;
        $this->apiToken = (string) $apiToken;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getAuthenticatedClient()
    {
        if (!$this->authed) {
            $this->client->authenticate(
                $this->apiToken,
                Client::AUTH_HTTP_TOKEN
            );
        }
        return $this->client;
    }
}
