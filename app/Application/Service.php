<?php

declare(strict_types=1);

namespace Application;

use Core\Request\HandlerFactory;
use Core\Request\RequestException;
use Core\Request\RequestFactory;
use Core\Service\ServiceInterface;
use Psr\Log\LoggerInterface;

final class Service implements ServiceInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestFactory $requestFactory,
        private readonly HandlerFactory $handlerFactory,
    ) {
    }

    public function run(): int
    {
        $isCli = PHP_SAPI === 'cli';
        $exitCode = 0;

        try {
            $output = $this->handlerFactory->create()->handle($this->requestFactory->create());
        } catch (RequestException $e) {
            $this->logger->notice($e->getMessage(), ['exception' => $e]);
            $exitCode = 1;
            $output = $e->getMessage();
            if (!$isCli) {
                $status = $e->getCode();
                http_response_code($status >= 400 && $status < 600 ? $status : 400);
            }
        }

        $this->logger->debug(sprintf(
            'time: %f sec; memory: %d kb',
            microtime(true) - BEGIN_TIME,
            intdiv(memory_get_usage(), 1024)
        ), ['callable' => __METHOD__]);

        if ($isCli) {
            fwrite($exitCode === 0 ? STDOUT : STDERR, $output === '' ? '' : rtrim($output, "\n") . "\n");
        } else {
            echo $output;
        }

        return $exitCode;
    }
}
