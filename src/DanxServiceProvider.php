<?php

namespace Flytedan\DanxLaravel;

use Flytedan\DanxLaravel\Console\Commands\SyncDirtyJobsCommand;
use Flytedan\DanxLaravel\Console\Commands\VaporDecryptCommand;
use Flytedan\DanxLaravel\Console\Commands\VaporEncryptCommand;
use Flytedan\DanxLaravel\Listeners\LogCommandExecution;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DanxServiceProvider extends ServiceProvider
{
	public function boot()
	{
		Event::listen(CommandStarting::class, LogCommandExecution::class);

		$this->mergeConfigFrom(__DIR__ . '/../config/danx.php', 'danx');

		$this->publishesMigrations([
			__DIR__ . '/../database/migrations' => database_path('migrations'),
		]);

		$this->publishes([
			__DIR__ . '/../.tinkerwell/CustomAuditDriver.php' => base_path('..tinkerwell/CustomAuditDriver.php'),
		]);

		if ($this->app->runningInConsole()) {
			$this->commands([
				SyncDirtyJobsCommand::class,
				VaporDecryptCommand::class,
				VaporEncryptCommand::class,
			]);
		}
	}

	public function register()
	{
	}
}
