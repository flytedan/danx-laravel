<?php

namespace Flytedan\DanxLaravel;

use Flytedan\DanxLaravel\Listeners\LogCommandExecution;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DanxServiceProvider extends ServiceProvider
{
	public function boot()
	{
		Event::listen(CommandStarting::class, LogCommandExecution::class);
	}

	public function register()
	{
		//
	}
}
