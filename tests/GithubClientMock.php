<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks;

use Github\Api\AbstractApi;
use Github\Api\Issue;

class GithubClientMock extends \Github\Client
{
    public $labels;

    public AbstractApi $apiMock;

    public function __construct()
    {
        parent::__construct();
        $this->labels = new LabelsMock();
    }

    public function api($name): AbstractApi
    {
        return $this->apiMock;
    }

    public function labels()
    {
        return $this->labels;
    }
}
