<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Exception\HttpException;
use Http\Client\Exception\NetworkException;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Http\Message\Formatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;
use PhpSpec\ObjectBehavior;

class LoggerPluginSpec extends ObjectBehavior
{
    function let(LoggerInterface $logger, Formatter $formatter)
    {
        $this->beConstructedWith($logger, $formatter);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Http\Client\Common\Plugin\LoggerPlugin');
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement('Http\Client\Common\Plugin');
    }

    function it_logs_request_and_response(
        LoggerInterface $logger,
        Formatter $formatter,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $formatter->formatRequest($request)->willReturn('GET / 1.1');
        $formatter->formatResponse($response)->willReturn('200 OK 1.1');

        $logger->info('Emit request: "GET / 1.1"', ['request' => $request])->shouldBeCalled();
        $logger->info('Receive response: "200 OK 1.1" for request: "GET / 1.1"', ['request' => $request, 'response' => $response])->shouldBeCalled();

        $next = function () use ($response) {
            return new FulfilledPromise($response->getWrappedObject());
        };

        $this->handleRequest($request, $next, function () {});
    }

    function it_logs_exception(LoggerInterface $logger, Formatter $formatter, RequestInterface $request)
    {
        $formatter->formatRequest($request)->willReturn('GET / 1.1');

        $exception = new NetworkException('Cannot connect', $request->getWrappedObject());

        $logger->info('Emit request: "GET / 1.1"', ['request' => $request])->shouldBeCalled();
        $logger->error('Error: "Cannot connect" when emitting request: "GET / 1.1"', ['request' => $request, 'exception' => $exception])->shouldBeCalled();

        $next = function () use ($exception) {
            return new RejectedPromise($exception);
        };

        $this->handleRequest($request, $next, function () {});
    }

    function it_logs_response_within_exception(
        LoggerInterface $logger,
        Formatter $formatter,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $formatter->formatRequest($request)->willReturn('GET / 1.1');
        $formatter->formatResponse($response)->willReturn('403 Forbidden 1.1');

        $exception = new HttpException('Forbidden', $request->getWrappedObject(), $response->getWrappedObject());

        $logger->info('Emit request: "GET / 1.1"', ['request' => $request])->shouldBeCalled();
        $logger->error('Error: "Forbidden" with response: "403 Forbidden 1.1" when emitting request: "GET / 1.1"', [
            'request'   => $request,
            'response'  => $response,
            'exception' => $exception
        ])->shouldBeCalled();

        $next = function () use ($exception) {
            return new RejectedPromise($exception);
        };

        $this->handleRequest($request, $next, function () {});
    }
}
