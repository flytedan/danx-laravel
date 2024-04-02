<?php

namespace Flytedan\DanxLaravel\Logging\Audit;

use Exception;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

class AuditLogFormatter extends NormalizerFormatter
{
    /**
     * Formats a set of log records.
     *
     * @param array $records A set of records to format
     * @return array The formatted set of records
     */
    public function formatBatch(array $records): array
    {
        $formatted = [];

        foreach($records as $record) {
            $formatted[] = $this->format($record);
        }

        return $formatted;
    }

    /**
     * Formats a log record.
     *
     * @param array|LogRecord $record A record to format
     * @return string The formatted record
     */
    public function format($record): string
    {
        if($record instanceof LogRecord) {
            $record = $record->toArray();
        }

        /** @var Exception $exception */
        $exception = $record['exception'] ?? $record['context']['exception'] ?? null;
        if($exception) {
            return $exception::class . ': ' . $exception->getMessage() . ' --- ' . $exception->getFile() . '@' . $exception->getLine();
        } else {
            return (string)$record['message'];
        }
    }
}
