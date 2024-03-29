<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\UrlGenerator;

if(!function_exists('user')) {
	/**
	 * Returns an authenticated User
	 *
	 * @return Model|null
	 */
	function user()
	{
		return auth()->guard()?->user();
	}
}

if(!function_exists('app_url')) {
	function app_url($path = '', $params = [])
	{
		$ug = new UrlGenerator(app('router')->getRoutes(), request());
		$ug->forceRootUrl(config('app.spa_url'));

		return $ug->to($path, $params, config('app.forceHttps'));
	}
}

if(!function_exists('api_url')) {
	function api_url($path = '', $params = [], $short = false)
	{
		$baseUrl = $short && config('app.short_url') ? config('app.short_url') : config('app.url');
		$ug      = new UrlGenerator(app('router')->getRoutes(), request());
		$ug->forceRootUrl($baseUrl);

		return $ug->to($path, $params, $short ? config('app.forceHttpsShortUrl') : config('app.forceHttps'));
	}
}
