<?php

declare(strict_types=1);

namespace App\Shared\App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

final readonly class JsonLogFormatter
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter);
            }

            if (! $handler instanceof ProcessableHandlerInterface) {
                continue;
            }

            $handler->pushProcessor(new class implements ProcessorInterface
            {
                public function __invoke(LogRecord $record): LogRecord
                {
                    return $record->with(extra: [
                        ...$record->extra,
                        'hostname' => gethostname() ?: 'unknown',
                        'request_id' => request()->header('X-Request-ID', ''),
                        'user_id' => auth()->id(),
                    ]);
                }
            });
        }
    }
}
