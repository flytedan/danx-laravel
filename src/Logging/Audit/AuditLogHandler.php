<?php

namespace Flytedan\DanxLaravel\Logging\Audit;

use Exception;
use Flytedan\DanxLaravel\Audit\AuditDriver;
use Flytedan\DanxLaravel\Helpers\StringHelper;
use Flytedan\DanxLaravel\Models\Audit\ErrorLog;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Writes entries to error_logs table
 */
class AuditLogHandler extends AbstractProcessingHandler
{
	public function __construct(
		$level = Logger::DEBUG,
		$bubble = true
	)
	{
		parent::__construct($level, $bubble);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param array $record
	 *
	 * @throws Exception
	 */
	protected function write($record): void
	{
		$formatted = $record['formatted'];

		if ($formatted) {
			$level     = $record['level_name'];
			$message   = $formatted['message'];
			$exception = $formatted['exception'];

			$auditRequest = AuditDriver::getAuditRequest();

			if ($auditRequest) {
				$timestamp = now()->toDateTimeString();
				$entry     = "\n$timestamp $level $message";

				// DO NOT save here, the logs will be written when the terminate event is fired
				$auditRequest->logs = StringHelper::logSafeString($auditRequest->logs . $entry, 65000);

				// Make an exception for running jobs to ensure we're getting logging leading up to an error
				$auditRequest->save();
			}

			if ($exception) {
				ErrorLog::logException($level, $exception);
			} elseif (Logger::toMonologLevel($level) >= Level::Error) {
				ErrorLog::logErrorMessage($level, $message);
			}
		}
	}
}
