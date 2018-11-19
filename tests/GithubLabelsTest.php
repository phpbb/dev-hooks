<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks;

require_once __DIR__ . '/GithubClientMock.php';

use Phpbb\DevHooks\Listener\IssueCommentGithubLabels;

class GithubLabelsTest extends TestCase
{
	/** @var IssueCommentGithubLabels */
	protected $githubLabels;

	/** @var \GithubClientMock */
	protected $mockClient;

	public function setUp()
	{
		$githubHelperMock = $this->getMockBuilder('Phpbb\DevHooks\Helper\GithubHelper')
			->disableOriginalConstructor()
			->getMock();

		$this->mockClient = new \GithubClientMock();

		$githubHelperMock->method('getClient')
			->willReturn($this->mockClient);
		$githubHelperMock->method('getAuthenticatedClient')
			->willReturn($this->mockClient);
		$this->githubLabels = new IssueCommentGithubLabels($githubHelperMock);
	}

	public function dataHandleEmpty()
	{
		return [
			['!set 3.0 (Olympus)', [], []], // Protected label
			['!set 3.1 (Ascraeus)', [], []], // Protected label
			['!set 3.2 (Rhea)', [], []], // Protected label
			['!set 3.3 (Proteus)', [], []], // Protected label
			['!set Blocker :warning:', [], []], // Protected label
			['!set Blocker', [], []], // Protected label
			['!set Event', ['Event'], []],
			['!set WIP', ['WIP :construction:'], []],
			['!set Do not merge', ['Do not merge :hand:'], []],
			['!set GSOC 🎓', ['GSOC 🎓'], []],
			['!set GSOC', ['GSOC 🎓'], []],
			['!unset 3.0 (Olympus)', [], []], // Protected label
			['!unset 3.1 (Ascraeus)', [], []], // Protected label
			['!unset 3.2 (Rhea)', [], []], // Protected label
			['!unset 3.3 (Proteus)', [], []], // Protected label
			['!unset Blocker', [], []], // Protected label
			['!unset Blocker :warning:', [], []], // Protected label
			['!unset Event', [], ['Event']],
			['!unset GSOC 🎓', [], ['GSOC 🎓']],
			['!unset GSOC', [], ['GSOC 🎓']],
		];
	}

	/**
	 * @dataProvider dataHandleEmpty
	 */
	public function testHandleEmpty($comment, $expectedAdded = [], $expectedRemoved = [])
	{
		$handleData = [
			'issue' => [
				'user' => ['id' => 1],
				'number' => 5,
			],
			'comment' => [
				'body' => $comment,
				'user' => ['id' => 1]
			],
			'repository' => [
				'owner' => ['login' => 'foo'],
				'name' => 'foo',
			]
		];
		$this->githubLabels->handle($handleData);

		$this->assertEquals($expectedAdded, $this->mockClient->labels()->addList);
		$this->assertEquals($expectedRemoved, $this->mockClient->labels()->removeList);

		// Reset data
		$this->mockClient->labels()->addList = [];
		$this->mockClient->labels()->removeList = [];
	}
}
