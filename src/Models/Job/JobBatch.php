<?php

namespace Flytedan\DanxLaravel\Models\Job;

use Flytedan\DanxLaravel\Jobs\Job;
use Flytedan\DanxLaravel\Models\Ref;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

class JobBatch extends Model
{
	protected $table = 'job_batches';

	protected $guarded      = [];
	protected $keyType      = 'string';
	public    $timestamps   = false;
	public    $incrementing = false;

	// Always include processed_jobs and progress in results
	protected $appends = ['processed_jobs', 'progress'];

	/**
	 * @param string        $name
	 * @param Job[]         $jobs
	 * @param callable|null $onComplete
	 * @return JobBatch
	 * @throws Throwable
	 */
	public static function createForJobs(string $name, array $jobs, $onComplete = null): JobBatch
	{
		$batchRef = Ref::generate('JB-');

		$jobBatch = JobBatch::create([
			'id'             => $batchRef,
			'name'           => "$name - $batchRef",
			'total_jobs'     => count($jobs),
			'pending_jobs'   => count($jobs),
			'failed_jobs'    => 0,
			'failed_job_ids' => '',
			'created_at'     => now()->timestamp,
			'on_complete'    => serialize(new SerializableClosure($onComplete)),
		]);

		$jobIds = [];
		foreach($jobs as $job) {
			if ($job->getJobDispatch()) {
				$jobIds[] = $job->getJobDispatch()->id;
			}
		}

		// Associate all jobs with the batch
		JobDispatch::whereIn('id', $jobIds)->update(['job_batch_id' => $jobBatch->id]);

		// Dispatch all the jobs
		foreach($jobs as $job) {
			$job->dispatch();
		}

		return $jobBatch;
	}

	/**
	 * @return HasMany|JobDispatch[]
	 */
	public function jobDispatches()
	{
		return $this->hasMany(JobDispatch::class, 'job_batch_id');
	}

	/**
	 * @return int number of jobs successfully processed
	 */
	public function getProcessedJobsAttribute()
	{
		return $this->total_jobs - $this->pending_jobs;
	}

	/**
	 * @return float|int
	 */
	public function getProgressAttribute()
	{
		return $this->total_jobs ? round($this->processed_jobs / $this->total_jobs, 1) : 0;
	}
}
