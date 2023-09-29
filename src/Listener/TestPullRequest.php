<?php

/**
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 */

namespace Phpbb\DevHooks\Listener;

class TestPullRequest implements Listener
{
    protected $supportedBranches = [
        '3.2.x',
        '3.3.x',
        'master'
    ];

    protected $supportedActions = [
        'opened',
        'reopened',
        'synchronize',
    ];

    private $user;
    private $pass;
    private $authString;
    private $notifyEmail;
    private $notifyUrl;

    public function __construct($bambooUser, $bambooPass, $notifyUser, $notifyPass, $notifyEmail, $notifyUrl)
    {
        $this->authString = $bambooUser;
        $this->authString .= ':' . $bambooPass;
        $this->user = $notifyUser;
        $this->pass = $notifyPass;
        $this->notifyEmail = $notifyEmail;
        $this->notifyUrl = $notifyUrl;
    }

    public function handle(array $data)
    {
        $action = $data['action'];
        $pullRequestNumber = (int) $data['number'];
        $ref = $data['pull_request']['base']['ref'];

        if (!in_array($ref, $this->supportedBranches) || !in_array($action, $this->supportedActions)) {
            return;
        }

        $build_url = 'https://bamboo.phpbb.com/rest/api/latest/queue/PHPBB3-PR';
        $build_url .= '?bamboo.variable.PRnumber=';

        $build_url .= $pullRequestNumber;
        $build_url .= '&bamboo.variable.PRref=' . $ref;

        $this->loginAccount();

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->authString);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, null);
        curl_setopt($curl, CURLOPT_URL, $build_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('X-Atlassian-Token: no-check'));

        $result = curl_exec($curl);
        $headers = curl_getinfo($curl);

        curl_close($curl);

        if ($headers['header_size'] == 0 || ($headers['http_code'] != 200 && $headers['http_code'] != 302
            && $headers['http_code'] != 500 && !($headers['http_code'] == 400
            && strpos($result, 'Build requested but not started') !== false)) || empty($result)) {
            @mail(
                $this->notifyEmail,
                'Error contacting Bamboo',
                'HTTP Code: ' . $headers['http_code'] . "\n" . 'HTML Result: ' . $result
            );
            throw new \RuntimeException($result);
        }
    }

    private function loginAccount()
    {
        //
        // Log into URL to keep the account active
        //
        $post_fields = 'login=Login&username=' . urlencode($this->user);
        $post_fields .= '&password=' . urlencode($this->pass) . '&viewonline=1';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($curl, CURLOPT_URL, $this->notifyUrl);

        $result = curl_exec($curl);
        $headers = curl_getinfo($curl);

        curl_close($curl);

        if ($headers['header_size'] == 0 || ($headers['http_code'] != 200 && $headers['http_code'] != 302)
            || (empty($result) && $headers['http_code'] != 302)) {
            $mailMessage = 'HTTP Code: ' . $headers['http_code'] . "\n";
            $mailMessage .= 'HTML Result: ' . $result . "\n" . print_r($headers, true);
            @mail(
                $this->notifyEmail,
                'Error logging in.',
                $mailMessage
            );
            throw new \RuntimeException($result);
        }
    }
}
