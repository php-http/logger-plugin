# Change Log

## 1.4.0 - 2024-10-31

- Support PHP 8.3 and 8.4
- Remove support for EOL PHP versions < 8.2

CI:

- Rewrote testing with phpunit instead of phpspec.
- Removed unused scrutinizer from build

## 1.3.1 - 2024-09-01

- Removed the last place where we forgot the request in the log context.

## 1.3.0 - 2022-02-11

- Do not log request when loggin response again, but use UID to identify request
  that belongs to response.
  If you use a logger that does not log `info` severity and want the request
  logged when an error happened, use a Fingerscrossed log handler to also log
  info if any error is logged.
- Removed the request and response from the log context. They did not get
  printed because they don't implement `__toString`.
- Supporting the newly introduced message formatter method
  `formatResponseForRequest` that allows more flexibility in the formatter.
  See https://github.com/php-http/message/pull/146

## 1.2.2 - 2021-07-26

- Allow installation with psr/log version 2 and 3

## 1.2.1 - 2020-11-27

- Allow installation with PHP 8.0

## 1.2.0 - 2020-07-14

- Use `hrtime` to avoid clock movement bugs
- Support only PHP 7.1-8.0

## 1.1.0 - 2019-01-19

- Support client-common 1.9 and 2
- Measure the time it took to do the request

## 1.0.0 - 2016-05-05

- Initial release
