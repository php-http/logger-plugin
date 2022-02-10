<?php

namespace spec\Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Common\Plugin\LoggerPlugin;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\NetworkException;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use Http\Message\Formatter;
use Prophecy\Argument;
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
        $this->shouldHaveType(LoggerPlugin::class);
    }

    function it_is_a_plugin()
    {
        $this->shouldImplement(Plugin::class);
    }

    function it_logs_request_and_response(
        LoggerInterface $logger,
        Formatter $formatter,
        RequestInterface $request,
        ResponseInterface $response
    ) {
        $formatter->formatRequest($request)->willReturn('GET / 1.1');
        if (method_exists(Formatter\SimpleFormatter::class, 'formatResponseForRequest')) {
            $formatter->formatResponseForRequest($response, $request)->willReturn('200 OK 1.1');
        } else {
            $formatter->formatResponse($response)->willReturn('200 OK 1.1');
        }

        $logger->info(
            "Sending request:\nGET / 1.1",
            Argument::that(
                function(array $context) {
                    return is_string($context['uid']);
                }
            )
        )->shouldBeCalled();
        $logger->info(
            "Received response:\n200 OK 1.1",
            Argument::that(
                function(array $context) {
                    return is_int($context['milliseconds'])
                        && is_string($context['uid'])
                    ;
                }
            )
        )->shouldBeCalled();

        $next = function () use ($response) {
            return new FulfilledPromise($response->getWrappedObject());
        };

        $this->handleRequest($request, $next, function () {});
    }

    function it_logs_exception(LoggerInterface $logger, Formatter $formatter, RequestInterface $request)
    {
        $formatter->formatRequest($request)->willReturn('GET / 1.1');

        $exception = new NetworkException('Cannot connect', $request->getWrappedObject());

        $logger->info(
            "Sending request:\nGET / 1.1",
            Argument::that(
                function(array $context) {
                    return is_string($context['uid']);
                }
            )
        )->shouldBeCalled();
        $logger->error(
            "Error:\nCannot connect\nwhen sending request:\nGET / 1.1",
            Argument::that(
                function(array $context) use ($exception) {
                    return $context['exception'] === $exception
                        && is_int($context['milliseconds'])
                        && is_string($context['uid'])
                    ;
                }
            )
        )->shouldBeCalled();

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
        if (method_exists(Formatter\SimpleFormatter::class, 'formatResponseForRequest')) {
            $formatter->formatResponseForRequest($response, $request)->willReturn('403 Forbidden 1.1');
        } else {
            $formatter->formatResponse($response)->willReturn('403 Forbidden 1.1');
        }

        $exception = new HttpException('Forbidden', $request->getWrappedObject(), $response->getWrappedObject());

        $logger->info(
            "Sending request:\nGET / 1.1",
            Argument::that(
                function(array $context) {
                    return is_string($context['uid']);
                }
            )
        )->shouldBeCalled();
        $logger->error(
            "Error:\nForbidden\nwith response:\n403 Forbidden 1.1",
            Argument::that(
                function(array $context) use ($exception) {
                    return $context['exception'] === $exception
                        && is_int($context['milliseconds'])
                        && is_string($context['uid'])
                    ;
                }
            )
        )->shouldBeCalled();

        $next = function () use ($exception) {
            return new RejectedPromise($exception);
        };

        $this->handleRequest($request, $next, function () {});
    }
}
