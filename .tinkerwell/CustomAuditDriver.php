<?php

use Flytedan\DanxLaravel\Audit\AuditDriver;

class TinkerwellAuditDriver extends LaravelTinkerwellDriver
{
	public function bootstrap($projectPath)
	{
		parent::bootstrap($projectPath);

		// TODO: if its possible, we want to record the logging output and exceptions from tinkerwell. Might not be possible...
		AuditDriver::getAuditRequest()->update([
			'request' => [
				'command' => 'tinkerwell',
			],
		]);
	}
}
