<?php

return [
	'audit'               => [
		'enabled' => env('AUDITING_ENABLED', false),

		/**
		 * Enable auditing / logging for any Api implementations using the Api class
		 */
		'api'     => [
			'enabled'         => env('AUDIT_API_ENABLED', false),
			'max_body_length' => env('AUDIT_API_MAX_BODY_LENGTH', 1000),
		],

		/**
		 * Enable auditing / logging for any Jobs implementations using the Job class
		 */
		'jobs'    => [
			'enabled' => env('AUDIT_JOBS_ENABLED', false),
			'debug'   => env('AUDIT_JOBS_DEBUG', false),
		],
	],

	/*
	 * AWS ELB application load balancer (ALB) has a 1MB limit on the response size when used w/ Lambda
	 * This should be enabled for the Laravel Vapor environment
	 */
	'response_size_limit' => [
		'enabled'    => env('RESPONSE_SIZE_LIMIT_ENABLED', false),
		'limit'      => env('RESPONSE_SIZE_LIMIT', 1024 * 1024),
		'disk'       => env('RESPONSE_SIZE_LIMIT_DISK', 's3'),

		// If the response file should be served via a CDN, setting the alias / origin will rewrite the URL of the file to use the CDN
		'cdn_origin' => env('RESPONSE_SIZE_LIMIT_CDN_ORIGIN'),
		'cdn_alias'  => env('RESPONSE_SIZE_LIMIT_CDN_ALIAS'),
	],
];
