<?php


namespace Flytedan\DanxLaravel\Resources;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Model
 * @property Model $resource
 */
abstract class ActionResource extends JsonResource
{
	public function __construct($resource)
	{
		parent::__construct($resource);
		$this->with = $this->data();
	}

	public function toArray($request)
	{
		return $this->with + [
				'__type'      => preg_replace("/Resource\$/", '', preg_replace("/^.*\\\\/", '', static::class)),
				'__timestamp' => request()->header('X-Timestamp') ?: LARAVEL_START,
			];
	}

	public function data(): array
	{
		throw new Exception('ActionResource requires the data method (in place of toArray). Please update ' . static::class);
	}
}
