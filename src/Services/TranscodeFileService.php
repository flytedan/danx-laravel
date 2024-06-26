<?php

namespace Flytedan\DanxLaravel\Services;

use Flytedan\DanxLaravel\Api\ConvertApi\ConvertApi;
use Flytedan\DanxLaravel\Exceptions\ApiException;
use Flytedan\DanxLaravel\Models\Utilities\StoredFile;
use Flytedan\DanxLaravel\Repositories\FileRepository;

class TranscodeFileService
{
	const string TRANSCODE_PDF_TO_IMAGES = 'pdf-to-images';

	public function pdfToImages(StoredFile $storedFile)
	{
		$transcodeName = self::TRANSCODE_PDF_TO_IMAGES;

		$transcodes = $storedFile->transcodes()->where('transcode_name', $transcodeName)->get();

		if ($transcodes->isNotEmpty()) {
			return $transcodes;
		}

		$result = app(ConvertApi::class)->pdfToImage($storedFile->url);

		if (!isset($result['Files'])) {
			throw new ApiException("Convert API did not return any files for PDF to Images transcode\n\n" . json_encode($result));
		}

		foreach($result['Files'] as $image) {
			$dir = $storedFile->id . ':' . $storedFile->filename;
			// Save file to storage disk
			$transcodedFile                          = app(FileRepository::class)->createFileWithContents("transcodes/$transcodeName/$dir/" . $image['FileName'], base64_decode($image['FileData']));
			$transcodedFile->original_stored_file_id = $storedFile->id;
			$transcodedFile->transcode_name          = $transcodeName;
			$transcodedFile->save();
			$transcodes->push($transcodedFile);
		}

		return $transcodes;
	}
}
