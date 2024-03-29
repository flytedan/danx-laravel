<?php

namespace Flytedan\DanxLaravel;

use Illuminate\Support\ServiceProvider;

class DanxServiceProvider extends ServiceProvider
{
	public function boot()
	{
		//		Event::listen(CommandStarting::class, LogCommandExecution::class);
		die('DanxServiceProvider boot');
	}

	public function register()
	{
		//
	}
}
