<?php

namespace Flytedan\DanxLaravel\Listeners;

use Flytedan\DanxLaravel\Audit\AuditDriver;

class LogCommandExecution
{
	public function handle(object $event): void
	{
		$commandName = $event->command;
		$params      = $event->input->getArguments();

		AuditDriver::getAuditRequest()?->update([
			'request' => [
				'command' => $commandName,
				'params'  => $params,
			],
		]);
	}
}
