<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks\Listener;

use Phpbb\DevHooks\Helper\GithubHelper;

class IssueCommentGithubLabels implements Listener
{
    protected $githubHelper;
    protected $protectedLabels = [
        '3.0 (Olympus)',
        '3.1 (Ascraeus)',
        '3.2 (Rhea)',
        '3.3 (Proteus)',
        'Blocker :warning:',
    ];

    public function __construct(GithubHelper $githubHelper)
    {
        $this->githubHelper = $githubHelper;
    }

    public function handle(array $data)
    {
        if ($data['issue']['user']['id'] === $data['comment']['user']['id']) {
            $lines = explode(
                "\n",
                str_replace("\r\n", "\n", $data['comment']['body'])
            );
            foreach ($lines as $line) {
                $messageParts = explode(' ', $line);
                $action = array_shift($messageParts);
                $label = implode(' ', $messageParts);
                $exists = $this->labelExists(
                    $data['repository']['owner']['login'],
                    $data['repository']['name'],
                    $label
                );
                if (!in_array($label, $this->protectedLabels)) {
                    if ($exists) {
                        if ($action === '!set') {
                            $this->githubHelper
                                ->getAuthenticatedClient()
                                ->api('issue')
                                ->labels()
                                ->add(
                                    $data['repository']['owner']['login'],
                                    $data['repository']['name'],
                                    $data['issue']['number'],
                                    $label
                                );
                            echo "$label set for issue " . $data['issue']['number'] . "\n";
                        } elseif ($action === '!unset') {
                            $this->githubHelper
                                ->getAuthenticatedClient()
                                ->api('issue')
                                ->labels()
                                ->remove(
                                    $data['repository']['owner']['login'],
                                    $data['repository']['name'],
                                    $data['issue']['number'],
                                    $label
                                );
                            echo "$label removed for issue " . $data['issue']['number'] . "\n";
                        } else {
                            echo "Unsupported action $action for label $label.\n";
                        }
                    }
                } else {
                    echo "Protected label $label.\n";
                }
            }
        }
    }

    protected function labelExists($repo_owner, $repository, &$label)
    {
        $labels = $this->githubHelper
            ->getClient()
            ->api('issues')
            ->labels()
            ->all($repo_owner, $repository)
        ;
        foreach ($labels as $label_data) {
            if ($label_data['name'] === $label) {
                return true;
            } elseif (stripos($label_data['name'], $label) === 0) {
                $label = $label_data['name'];
                return true;
            }
        }
        return false;
    }
}
