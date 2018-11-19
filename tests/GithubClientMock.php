<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks;

class GithubClientMock extends \Github\Client
{
    public $labels;

    public function __construct(\Github\HttpClient\HttpClientInterface $httpClient = null)
    {
        parent::__construct($httpClient);
        $this->labels = new LabelsMock();
    }

    public function api($name)
    {
        return $this;
    }

    public function labels()
    {
        return $this->labels;
    }
}
