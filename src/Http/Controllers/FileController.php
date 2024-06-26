<?php

namespace Flytedan\DanxLaravel\Http\Controllers;

use Exception;
use Flytedan\DanxLaravel\Exceptions\ValidationError;
use Flytedan\DanxLaravel\Models\Utilities\StoredFile;
use Flytedan\DanxLaravel\Repositories\FileRepository;
use Flytedan\DanxLaravel\Resources\StoredFileResource;
use Illuminate\Http\Request;

class FileController extends Controller
{
	/**
	 * Creates a File resource with a presigned URL that can be used to upload a file directly to AWS S3 (or local
	 * filesystem in case of local env)
	 *
	 * @param Request $request
	 * @return StoredFile
	 */
	public function presignedUploadUrl(Request $request)
	{
		$path = $request->get('path');
		$name = $request->get('name');
		$mime = $request->get('mime');
		$meta = json_decode($request->get('meta'), true) ?: [];

		$file = app(FileRepository::class)->createFileWithUploadUrl($path, $name, $mime, $meta);

		return StoredFileResource::make($file);
	}

	/**
	 * Upload file contents for a presigned URL to the storage disk
	 *
	 * @param Request    $request
	 * @param StoredFile $storedFile
	 * @return array
	 *
	 * @throws Exception
	 */
	public function uploadPresignedUrlContents(Request $request, StoredFile $storedFile)
	{
		if ($storedFile->url !== route('file.upload-presigned-url-contents', ['storedFile' => $storedFile->id])) {
			throw new ValidationError('File is not a presigned URL file');
		}

		$uploadedFile = $request->file('file');

		if (!$uploadedFile) {
			throw new ValidationError('No file uploaded');
		}

		$uploadedFile->storePubliclyAs(dirname($storedFile->filepath), $storedFile->filename);

		return ['success' => true];
	}

	/**
	 * Marks a presigned file upload as completed and sets mime / size / url on the File record
	 *
	 * @param StoredFile $storedFile
	 * @return StoredFile
	 * @throws ValidationError
	 */
	public function presignedUploadUrlCompleted(StoredFile $storedFile)
	{
		app(FileRepository::class)->presignedUploadUrlCompleted($storedFile);

		return StoredFileResource::make($storedFile);
	}
}
