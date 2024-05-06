<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

	public function up()
	{
		if (!Schema::hasTable('stored_files')) {
			Schema::create('stored_files', function (Blueprint $table) {
				$table->uuid('id')->primary();
				$table->string('disk', 36);
				$table->string('filepath', 768)->index('file_filepath_index');
				$table->string('filename', 255);
				$table->string('url', 512)->nullable();
				$table->string('mime', 255);
				$table->unsignedInteger('size')->default(0);
				$table->json('exif')->nullable();
				$table->json('meta')->nullable();
				$table->json('location')->nullable();
				$table->uuid('storable_id')->nullable();
				$table->string('storable_type', 255)->nullable();
				$table->string('category', 255)->nullable();
				$table->timestamps();
				$table->timestamp('deleted_at')->nullable();

				$table->index(['storable_id', 'storable_type'], 'file_storable_index');
			});
		}
	}

	public function down()
	{
		Schema::dropIfExists('stored_files');
	}
};
