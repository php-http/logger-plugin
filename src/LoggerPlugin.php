<?php

namespace Http\Client\Common\Plugin;

use Http\Client\Common\Plugin;
use Http\Client\Exception;
use Http\Message\Formatter;
use Http\Message\Formatter\SimpleFormatter;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Log request, response and exception for an HTTP Client.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
final class LoggerPlugin implements Plugin
{
    use VersionBridgePlugin;

    private $logger;

    private $formatter;

    public function __construct(LoggerInterface $logger, Formatter $formatter = null)
    {
        $this->logger = $logger;
        $this->formatter = $formatter ?: new SimpleFormatter();
    }

    protected function doHandleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $start = hrtime(true) / 1E6;
        $uid = uniqid('', true);
        $this->logger->info(sprintf("Sending request:\n%s", $this->formatter->formatRequest($request)), ['request' => $request, 'uid' => $uid]);

        return $next($request)->then(function (ResponseInterface $response) use ($start, $uid, $request) {
            $milliseconds = (int) round(hrtime(true) / 1E6 - $start);
            $formattedResponse = method_exists($this->formatter, 'formatResponseForRequest')
                ? $this->formatter->formatResponseForRequest($response, $request)
                : $this->formatter->formatResponse($response);
            $this->logger->info(
                sprintf("Received response:\n%s", $formattedResponse),
                [
                    'milliseconds' => $milliseconds,
                    'uid' => $uid,
                ]
            );

            return $response;
        }, function (Exception $exception) use ($request, $start, $uid) {
            $milliseconds = (int) round((hrtime(true) / 1E6 - $start));
            if ($exception instanceof Exception\HttpException) {
                $formattedResponse = method_exists($this->formatter, 'formatResponseForRequest')
                    ? $this->formatter->formatResponseForRequest($exception->getResponse(), $exception->getRequest())
                    : $this->formatter->formatResponse($exception->getResponse());
                $this->logger->error(
                    sprintf("Error:\n%s\nwith response:\n%s", $exception->getMessage(), $formattedResponse),
                    [
                        'exception' => $exception,
                        'milliseconds' => $milliseconds,
                        'uid' => $uid,
                    ]
                );
            } else {
                $this->logger->error(
                    sprintf("Error:\n%s\nwhen sending request:\n%s", $exception->getMessage(), $this->formatter->formatRequest($request)),
                    [
                        'exception' => $exception,
                        'milliseconds' => $milliseconds,
                        'uid' => $uid,
                    ]
                );
            }

            throw $exception;
        });
    }
}
