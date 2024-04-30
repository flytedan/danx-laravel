<?php

namespace Flytedan\DanxLaravel\Middleware;

use Closure;
use Flytedan\DanxLaravel\Audit\AuditDriver;
use Illuminate\Http\Request;

class AuditingMiddleware
{
	public function handle(Request $request, Closure $next)
	{
		if ($request->method() === 'OPTIONS') {
			return $next($request);
		}

		AuditDriver::startTimer();

		$response = $next($request);

		return AuditDriver::terminate($response);
	}
}
