<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Exception\HttpException;
use Http\Client\Exception\NetworkException;
use Http\Message\Formatter\SimpleFormatter;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoggerPlugin::class)]
final class LoggerPluginTest extends TestCase
{
    private TestLogger $logger;
    private LoggerPlugin $plugin;

    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->plugin = new LoggerPlugin($this->logger, new SimpleFormatter());
    }

    public function testLogsRequestAndResponse()
    {
        $response = new Response();

        $actualResponse = $this->plugin->handleRequest(
            new Request('GET', 'http://example.com/path?query=value#fragment'),
            fn (RequestInterface $req) => new FulfilledPromise($response),
            function () {}
        )->wait();

        self::assertSame($response, $actualResponse);

        self::assertCount(2, $this->logger->logMessages);
        self::assertSame("Sending request:\nGET http://example.com/path?query=value#fragment 1.1", $this->logger->logMessages[0]['info']);
        self::assertSame('http://example.com/path?query=value#fragment', $this->logger->logMessages[0]['context']['uri']);
        self::assertSame("Received response:\n200 OK 1.1", $this->logger->logMessages[1]['info']);
        self::assertSame('http://example.com/path?query=value#fragment', $this->logger->logMessages[1]['context']['uri']);
    }

    public function testLogsRequestException()
    {
        $this->expectException(NetworkException::class);

        try {
            $this->plugin->handleRequest(
                new Request('GET', 'http://example.com/'),
                fn (RequestInterface $req) => new RejectedPromise(new NetworkException('Network error', $req)),
                function () {}
            )->wait();
        } catch (NetworkException $exception) {
            self::assertCount(2, $this->logger->logMessages);
            self::assertSame("Sending request:\nGET http://example.com/ 1.1", $this->logger->logMessages[0]['info']);
            self::assertSame('http://example.com/', $this->logger->logMessages[0]['context']['uri']);
            self::assertSame("Error:\nNetwork error\nwhen sending request:\nGET http://example.com/ 1.1", $this->logger->logMessages[1]['error']);
            self::assertSame('http://example.com/', $this->logger->logMessages[1]['context']['uri']);

            throw $exception;
        }
    }

    public function testLogsHttpException()
    {
        $this->expectException(HttpException::class);

        try {
            $this->plugin->handleRequest(
                new Request('GET', 'http://example.com/'),
                fn (RequestInterface $req) => new RejectedPromise(new HttpException('Not Found', $req, new Response())),
                function () {}
            )->wait();
        } catch (HttpException $exception) {
            // Expected
            $this->assertCount(2, $this->logger->logMessages);
            $this->assertSame("Sending request:\nGET http://example.com/ 1.1", $this->logger->logMessages[0]['info']);
            self::assertSame('http://example.com/', $this->logger->logMessages[0]['context']['uri']);

            // Ensure there's an error log for the exception
            $this->assertStringContainsString("Error:\nNot Found", $this->logger->logMessages[1]['error']);
            self::assertSame('http://example.com/', $this->logger->logMessages[1]['context']['uri']);

            throw $exception;
        }
    }
}
