<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

class GithubClientMock extends Github\Client
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

class LabelsMock
{
	public $addList = [];
	public $removeList = [];

	public function all($repoOwner, $repository)
	{
		return [
			['name' => '3.0 (Olympus)'],
			['name' => '3.1 (Ascraeus)'],
			['name' => '3.2 (Rhea)'],
			['name' => '3.3 (Proteus)'],
			['name' => 'Blocker :warning:'],
			['name' => 'Event'],
			['name' => 'Abandoned by Author 🏚️'],
			['name' => 'Do not merge :hand:'],
			['name' => 'GSOC 🎓'],
			['name' => 'JS :scroll:'],
			['name' => 'Missing Tests'],
			['name' => 'Pending Closure'],
			['name' => 'RFC Under Discussion'],
			['name' => 'Styles 🎨'],
			['name' => 'WIP :construction:'],
		];
	}

	public function add($owner, $name, $issueNumber, $label)
	{
		$this->addList[] = $label;
	}

	public function remove($owner, $name, $issueNumber, $label)
	{
		$this->removeList[] = $label;
	}
}
