<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks;

use chobie\Jira\Api as JiraClient;
use Phpbb\DevHooks\Helper\GithubHelper;
use Phpbb\DevHooks\Listener\IssueCommentGithubLabels;
use Phpbb\DevHooks\Listener\PullRequestJiraTicket;

class PullRequestJiraTicketTest extends TestCase
{
    /** @var IssueCommentGithubLabels */
    protected $githubLabels;

    /** @var PullRequestJiraTicket */
    protected $prJiraTicket;

    /** @var JiraClient */
    protected $jiraClientMock;

    /** @var array Commit data for testing */
    protected $commitData = [];

    /** @var array Expected issue data */
    protected $expectedIssueData = [];

    /** @var array Ticket return data */
    protected $ticketReturn = [];

    /** @var array Expected updated PR data */
    protected $expectedUpdatedPrData = [];

    protected function setUp(): void
    {
        $abstractApiMock = $this->getMockBuilder(\Github\Api\AbstractApi::class)
            ->disableOriginalConstructor()
            ->addMethods(['commits', 'update'])
            ->getMockForAbstractClass();
        $abstractApiMock->method('commits')
            ->willReturnCallback([$this, 'getCommitData']);
        $abstractApiMock->method('update')
            ->willReturnCallback([$this, 'updatePr']);

        $githubClientMock = $this->getMockBuilder(\Github\Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['api'])
            ->getMock();

        $githubClientMock->method('api')->willReturn($abstractApiMock);

        $githubHelperMock = $this->getMockBuilder(GithubHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAuthenticatedClient'])
            ->addMethods(['update'])
            ->getMock();

        $githubHelperMock->method('getAuthenticatedClient')
            ->willReturn($githubClientMock);

        $this->jiraClientMock = $this->getMockBuilder(JiraClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createIssue'])
            ->addMethods(['getResult'])
            ->getMock();
        $this->jiraClientMock->method('createIssue')->willReturnCallback([$this, 'createIssue']);
        $this->jiraClientMock->method('getResult')->willReturnCallback(function() { return $this->ticketReturn; });

        $this->prJiraTicket = new PullRequestJiraTicket($githubHelperMock, $this->jiraClientMock);
    }

    /**
     * Creates a mocked issue.
     *
     * @param string $project_key Project key.
     * @param string $summary Summary.
     * @param string $issue_type Issue type.
     * @param array $options Options.
     */
    public function createIssue($project_key, $summary, $issue_type, array $options = array())
    {
        $this->assertEquals(
            [
                $project_key,
                $summary,
                $issue_type,
                $options
            ],
            $this->expectedIssueData
        );

        return $this->jiraClientMock;
    }

    public function updatePr($ownerLogin, $repoName, $prNumber, $data)
    {
        $this->assertEquals(
            [
                $ownerLogin,
                $repoName,
                $prNumber,
                $data
            ],
            $this->expectedUpdatedPrData
        );
    }

    public function getCommitData($login, $name, $number)
    {
        return $this->commitData;
    }

    public function dataHandle()
    {
        return [
            [
                // Issue key already in PR body, opened PR
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'PHPBB3-12345',
                    ]
                ],
                '',
            ],
            [
                // ticket style info already in PR body, opened PR
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'ticket/12345',
                    ]
                ],
                '',
            ],
            [
                // Issue key already in title, opened PR
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR PHPBB3-12345',
                        'body' => 'Something',
                    ]
                ],
                '',
            ],
            [
                // Issue key already in PR body, reopened PR
                [
                    'action' => 'reopened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'PHPBB3-12345',
                    ]
                ],
                '',
            ],
            [
                // Issue key already in title, reopened PR with PHPBB key
                [
                    'action' => 'reopened',
                    'pull_request' => [
                        'title' => 'Test PR PHPBB-12345',
                        'body' => 'Something',
                    ]
                ],
                '',
            ],
            [
                // Issue key already in title, reopened PR with SECURITY key
                [
                    'action' => 'reopened',
                    'pull_request' => [
                        'title' => 'Test PR SECURITY-12345',
                        'body' => 'Something',
                    ]
                ],
                '',
            ],
            [
                // Issue key in head ref, opened PR
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'Something',
                        'head' => [
                            'ref' => 'PHPBB-12345',
                        ]
                    ]
                ],
                '',
            ],
            [
                // Issue key in head ref, opened PR
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'Something',
                        'head' => [
                            'ref' => 'ddfdf',
                        ],
                        'number' => 1234,
                    ],
                    'repository' => [
                        'owner' => [
                            'login' => 'foo',
                        ],
                        'name' => 'test',
                    ],
                ],
                '',
                [
                    [
                        'commit' => [
                            'message' => 'PHPBB-1234',
                        ],
                    ],
                ]
            ],
            [
                // Opened PR, no issue key
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'Something',
                        'head' => [
                            'ref' => 'ddfdf',
                        ],
                        'number' => 1234,
                    ],
                    'repository' => [
                        'owner' => [
                            'login' => 'foo',
                        ],
                        'name' => 'test',
                    ],
                ],
                "No issue key found, creating ticket\n",
                [
                    [
                        'commit' => [
                            'message' => 'Nope',
                        ],
                    ],
                ],
                [
                    'PHPBB',
                    'Test PR',
                    1, // Issue type bug
                    [
                        'description'   => 'Something',
                    ],
                ],
                ['key' => 'PHPBB-12345'],
                [
                    'foo',
                    'test',
                    1234,
                    [
                        'title'     => 'Test PR',
                        'body'      => "Something\n\nhttps://tracker.phpbb.com/browse/PHPBB-12345",
                    ],
                ],
            ],
            [
                // Opened PR, no issue key and can't create ticket
                [
                    'action' => 'opened',
                    'pull_request' => [
                        'title' => 'Test PR',
                        'body' => 'Something',
                        'head' => [
                            'ref' => 'ddfdf',
                        ],
                        'number' => 1234,
                    ],
                    'repository' => [
                        'owner' => [
                            'login' => 'foo',
                        ],
                        'name' => 'test',
                    ],
                ],
                "No issue key found, creating ticket\n",
                [
                    [
                        'commit' => [
                            'message' => 'Nope',
                        ],
                    ],
                ],
                [
                    'PHPBB',
                    'Test PR',
                    1, // Issue type bug
                    [
                        'description'   => 'Something',
                    ],
                ],
                ['key' => ''],
                [
                    'foo',
                    'test',
                    1234,
                    [
                        'title'     => 'Test PR',
                        'body'      => "Something\n\nCould not automatically create an issue. " .
                                       'Please create one on https://tracker.phpbb.com/ and replace this text with a link to it.',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataHandle
     */
    public function testHandle($inputData, $expectedOutput, $commitData = [], $expectedIssueData = [], $ticketReturn = [], $expectedUpdatedPrData = [])
    {
        $this->expectOutputString($expectedOutput);
        $this->commitData = $commitData;
        $this->expectedIssueData = $expectedIssueData;
        $this->ticketReturn = $ticketReturn;
        $this->expectedUpdatedPrData = $expectedUpdatedPrData;
        $this->prJiraTicket->handle($inputData);
    }
}
