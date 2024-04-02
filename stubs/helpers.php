<?php

use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

function app(): App { }

function auth($guard = null): Factory|Guard|StatefulGuard { }

function config($path = ''): array|string|int|bool|float|Config { }

function request(): Request { }

function database_path($path): string { }

function storage_path($path): string { }

function public_path($path): string { }

function resource_path($path): string { }

function base_path($path): string { }

function app_path($path): string { }

function config_path($path): string { }

function now(): Carbon { }
