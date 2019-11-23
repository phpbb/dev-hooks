<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks;

use Pimple\Container;

class ContainerBuilder
{
    /** @return \ArrayAccess */
    public function build()
    {
        $values = [
            // Parameters
            'jira_url' => 'https://tracker.phpbb.com',
            'cache_dir' => __DIR__ . '/../cache/',

            // Services
            'github_client' => function ($c) {
                return new \Github\Client;
            },
            'github_helper' => function ($c) {
                return new Helper\GithubHelper(
                    $c['github_client'],
                    $c['github_api_token']
                );
            },
            'jira_client' => function ($c) {
                return new \chobie\Jira\Api(
                    $c['jira_url'],
                    new \chobie\Jira\Api\Authentication\Basic(
                        $c['jira_username'],
                        $c['jira_password']
                    )
                );
            },
            'kernel' => function ($c) {
                return new Kernel(
                    $c['github_webhooks_secret'],
                    $c
                );
            },
            'listener.issue_comment.github_labels' => function ($c) {
                return new Listener\IssueCommentGithubLabels(
                    $c['github_helper']
                );
            },
            'listener.pull_request.jira_ticket' => function ($c) {
                return new Listener\PullRequestJiraTicket(
                    $c['github_helper'],
                    $c['jira_client']
                );
            },
            'listener.pull_request.test' => function ($c) {
                return new Listener\TestPullRequest(
                    $c['bamboo_username'],
                    $c['bamboo_password'],
                    $c['notify_username'],
                    $c['notify_password'],
                    $c['notify_email'],
                    $c['notify_url']
                );
            },
            'listener.push.split_repo' => function ($c) {
                return new Listener\PushSplitRepo(
                    $c['cache_dir']
                );
            },
            'listener.push.add_tag' => function ($c) {
                return new Listener\AddTagSplit(
                    $c['cache_dir']
                );
            },
            'listener.create.add_tag' => function ($c) {
                return new Listener\AddTagSplit(
                    $c['cache_dir']
                );
            }
        ];

        $secretsFile = __DIR__.'/../config/parameters.php';
        if (file_exists($secretsFile)) {
            $secrets = require $secretsFile;
            if (is_array($secrets)) {
                $values = array_merge($values, $secrets);
            }
        }

        return new Container($values);
    }
}
