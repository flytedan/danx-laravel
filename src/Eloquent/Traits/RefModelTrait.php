<?php

namespace Flytedan\DanxLaravel\Eloquent\Traits;

use Flytedan\DanxLaravel\Models\Ref;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * This trait is to be used with the 'ref' field $table->string('ref')->unique() schema definition
 *
 * @mixin Model
 */
trait RefModelTrait
{
	public static $refPrefix = 'REF-';

	/**
	 * This function overwrites the default boot static method of Eloquent models. It will hook
	 * the creation event with a simple closure to generate a new increment Unique Ref
	 *
	 * @throws Throwable
	 */
	public static function bootRefModelTrait()
	{
		static::creating(function (Model $model) {
			if (!$model->ref) {
				$model->ref = static::generateRef();
			}
		});
	}

	/**
	 * Generates the next sequential Ref ID for this Model
	 *
	 * @return string
	 *
	 * @throws Throwable
	 */
	public static function generateRef()
	{
		return Ref::generate(static::$refPrefix);
	}
}
