<?php

/**
* @copyright (c) phpBB Limited <https://www.phpbb.com>
* @license GNU General Public License, version 2 (GPL-2.0)
*/

namespace Phpbb\DevHooks;

use Pimple\Container;

class KernelTest extends TestCase
{
    protected $container;
    protected $kernel;
    protected $hmacKey = '0123456789';

    public function setUp()
    {
        $this->container = new Container([
            'github_webhooks_secret' => $this->hmacKey,
        ]);
        $this->kernel = new Kernel(
            $this->hmacKey,
            $this->container
        );
    }

    public function testHandle()
    {
        $payload = [];
        $body = json_encode($payload);
        $server = [
            'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $body, $this->hmacKey),
            'HTTP_X_GITHUB_EVENT' => 'test_event',
        ];
        $listenerStub = $this->getMock('\Phpbb\DevHooks\Listener\Listener');
        $listenerStub
            ->expects($this->once())
            ->method('handle')
            ->with($this->equalTo($payload))
        ;

        $this->container['listener.test_event.test'] = $listenerStub;

        $this->kernel->handle($body, $server);
    }
}
