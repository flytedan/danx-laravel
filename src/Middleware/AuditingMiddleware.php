<?php

namespace Flytedan\DanxLaravel\Middleware;

use Closure;
use Flytedan\DanxLaravel\Audit\AuditDriver;

class AuditingMiddleware
{
	public function handle($request, Closure $next)
	{
		AuditDriver::startTimer();

		$response = $next($request);

		return AuditDriver::terminate($response);
	}
}
