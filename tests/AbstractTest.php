<?php
namespace EmailToSlack\tests;

require './src/EmailToSlack.php';

use CL\Slack\Model\Channel;
use Ddeboer\Imap\Message;
use Ddeboer\Imap\Connection;
use Ddeboer\Imap\Mailbox;
use Ddeboer\Imap\MessageIterator;
use Ddeboer\Imap\Search\Flag\Unseen;
use Ddeboer\Imap\SearchExpression;
use EmailToSlack\EmailToSlack;
use Mockery;
use PHPUnit_Framework_TestCase;

abstract class AbstractTest extends PHPUnit_Framework_TestCase
{
    /** @var EmailToSlack */
    public $emailToSlack;

    public function initEmailToSlack()
    {
        $loggerMock = Mockery::mock('Monolog');


//        $responseMock = $loggerMock->shouldReceive('send')->with(Mockery::ducktype('getMethod'))->andReturnSelf();
//        $responseMock->shouldReceive('isOk')->andReturn(true);
//
//        $slackChannelMock = $responseMock->shouldReceive('getChannels')->andReturnUsing(function () {
//            return $this->getSlackChannelMock();
//        });

        $imapClientMock = Mockery::mock('Server');

        $connectionMock = $imapClientMock->shouldReceive('authenticate')->with('', '')->andReturnSelf();
        $connectionMock->shouldReceive('getResource')->andReturn(1);

        $mailBoxMock = $connectionMock->shouldReceive('getMailbox')->with('INBOX')->andReturnSelf();

        $messagesMock = $mailBoxMock->shouldReceive('getMessages')->with(Mockery::ducktype('addCondition'))->andReturnUsing(function () {
            return $this->getMessagesMock();
        });


        $slackClientMock = Mockery::mock('ApiClient');

        $responseMock = $slackClientMock->shouldReceive('send')->with(Mockery::ducktype('getMethod'))->andReturnSelf();
        $responseMock->shouldReceive('isOk')->andReturn(true);

        $slackChannelMock = $responseMock->shouldReceive('getChannels')->andReturnUsing(function () {
            return $this->getSlackChannelMock();
        });

        $this->emailToSlack = new EmailToSlack('', '', $loggerMock, $slackClientMock, $imapClientMock);
    }


    public function getMessagesMock()
    {
        $messageMock1 = $this->getMessageMock('forward', 'general');

        $messageMock2 = $this->getMessageMock('forward', 'random');

        return [
            $messageMock1,
            $messageMock2
        ];
    }


    public function getSlackChannelMock()
    {
        $slackChannelMock1 = Mockery::mock(Channel::class);
        $slackChannelMock1->shouldReceive('getName')->andReturn('general');

        $slackChannelMock2 = Mockery::mock(Channel::class);
        $slackChannelMock2->shouldReceive('getName')->andReturn('random');

        return [
            $slackChannelMock1,
            $slackChannelMock2
        ];
    }

    public function getMessageMock($messageType, $channel = 'general', $getTo = true, $getCc = false, $hasAttachments = false)
    {
        $messageMock = Mockery::mock(Message::class);
        $messageMock->shouldReceive('getBodyText')->andReturn(file_get_contents(__DIR__ . '/fixture/' . $messageType . '.txt'));
        $messageMock->shouldReceive('getBodyHtml')->andReturn(file_get_contents(__DIR__ . '/fixture/' . $messageType . '.html'));
        $messageMock->shouldReceive('getSubject')->andReturn('Mail di test per ' . $channel);
        $messageMock->shouldReceive('getFrom')->andReturn('mauro@example.com');
        if ($getTo) {
            $messageMock->shouldReceive('getTo')->andReturn(['slack-' . $channel . '@mmh-tech.com', 'test@example.com']);
        }else{
            $messageMock->shouldReceive('getTo')->andReturn([]);
        }
        if ($getCc) {
            $messageMock->shouldReceive('getCc')->andReturn(['slack-' . $channel . '@mmh-tech.com']);
        }
        $messageMock->shouldReceive('hasAttachments')->andReturn($hasAttachments);
//        $messageMock->shouldReceive('getAttachments')->andReturn(0);
        $messageMock->shouldReceive('getNumber')->andReturn(1);
        $messageMock->shouldReceive('getDate')->andReturn(new \DateTime('2017-02-13 18:01:11'));

        return $messageMock;

    }

    public function tearDown()
    {
        Mockery::close();
    }
}
