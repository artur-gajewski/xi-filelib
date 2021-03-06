<?php

namespace Xi\Tests\Filelib\Queue;

use Xi\Filelib\Queue\PhpAMQPQueue;
use Xi\Filelib\Queue\Message;


class PhpAMQPQueueTest extends \Xi\Tests\Filelib\Queue\TestCase
{

    public function setUp()
    {
        if (!class_exists('PhpAmqpLib\Connection\AMQPConnection')) {
            $this->markTestSkipped('PhpAmqpLib not found');
        }

        if (!RABBITMQ_HOST) {
            $this->markTestSkipped('RabbitMQ not configured');
        }

        parent::setUp();
    }


    protected function getQueue()
    {
        return new PhpAMQPQueue(RABBITMQ_HOST, RABBITMQ_PORT, RABBITMQ_USERNAME, RABBITMQ_PASSWORD, RABBITMQ_VHOST, 'filelib_test_exchange', 'filelib_test_queue');
    }



}

