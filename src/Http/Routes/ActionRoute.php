<?php

namespace Flytedan\DanxLaravel\Http\Routes;


use Flytedan\DanxLaravel\Http\Controllers\ActionController;
use Flytedan\DanxLaravel\Requests\PagerRequest;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Route;

class ActionRoute extends Route
{
	/**
	 * @param string           $name
	 * @param ActionController $controller The class name of a controller extending ActionController
	 * @return RouteRegistrar
	 */
	public static function routes(string $name, ActionController $controller): RouteRegistrar
	{
		// Strict naming / prefixing rules to ensure consistency
		$prefix = str_replace('.', '/', $name);

		return static::prefix($prefix)->withoutMiddleware([VerifyCsrfToken::class])->group(function () use ($name, $controller) {
			$getPost = ['GET', 'HEAD', 'POST'];
			// GET Data - NOTE: POST is included since filters can be too long for URLs in some browsers
			self::addRoute($getPost, 'list', [$controller::class, 'list'])->name($name . '.list');
			self::addRoute($getPost, 'summary', [$controller::class, 'summary'])->name($name . '.summary');
			self::addRoute($getPost, 'filter-field-options', [$controller::class, 'filterFieldOptions'])->name($name . '.filterFieldOptions');
			self::get('{id}/details', fn($model) => $controller->details($controller->repo()->instance($model)))->name($name . '.details');
			self::addRoute($getPost, 'export', [$controller::class, 'export'])->name($name . '.export');

			// Actions
			self::post('{id}/apply-action', fn($model, PagerRequest $request) => $controller->applyAction($controller->repo()->instance($model), $request))->name($name . '.apply-action');
			self::post('batch-action', [$controller::class, 'batchAction'])->name($name . '.batch-action');
		});
	}
}