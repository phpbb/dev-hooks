<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks\Listener;

use chobie\Jira\Api as JiraClient;
use Phpbb\DevHooks\Helper\GithubHelper;

class PullRequestJiraTicket implements Listener
{
    /** @var int Issue type bug */
    protected const ISSUE_TYPE_BUG = 1;

    protected $githubHelper;
    protected $jiraClient;

    public function __construct(GithubHelper $github, JiraClient $jira)
    {
        $this->githubHelper = $github;
        $this->jiraClient = $jira;
    }

    public function handle(array $data)
    {
        if ($data['action'] === 'opened' || $data['action'] === 'reopened') {
            $title = $data['pull_request']['title'];
            $body = $data['pull_request']['body'];

            if ($this->containsJiraKey($title) || $this->containsJiraKey($body)) {
                return;
            }

            if ($this->containsJiraKey($data['pull_request']['head']['ref'])) {
                return;
            }

            $commits = $this->githubHelper
                ->getAuthenticatedClient()
                ->api('pull_request')
                ->commits(
                    $data['repository']['owner']['login'],
                    $data['repository']['name'],
                    $data['pull_request']['number']
                )
            ;

            if ($this->containsJiraKey($commits[0]['commit']['message'])) {
                return;
            }

            echo "No issue key found, creating ticket\n";

            $ticketId = $this->createJiraTicket($data);

            if ($ticketId) {
                $ticketLink = 'https://tracker.phpbb.com/browse/'.$ticketId;
            } else {
                $ticketLink = 'Could not automatically create an issue. ' .
                    'Please create one on https://tracker.phpbb.com/ and ' .
                    'replace this text with a link to it.';
            }

            $newBody = $body ? "$body\n\n$ticketLink" : $ticketLink;

            $this->githubHelper
                ->getAuthenticatedClient()
                ->api('pull_request')
                ->update(
                    $data['repository']['owner']['login'],
                    $data['repository']['name'],
                    $data['pull_request']['number'],
                    ['title' => $title, 'body' => $newBody]
                )
            ;
        }
    }

    protected function containsJiraKey($message)
    {
        return preg_match('#(PHPBB3?|SECURITY)-(\d+)#', $message) || preg_match('#ticket/(\d+)#', $message);
    }

    protected function createJiraTicket($data)
    {
        $options = [];

        if ($data['pull_request']['body']) {
            $options['description'] = $data['pull_request']['body'];
        }

        $result = $this->jiraClient->createIssue(
            'PHPBB',
            $data['pull_request']['title'],
            self::ISSUE_TYPE_BUG,
            $options
        )->getResult();

        return $result['key'];
    }
}
