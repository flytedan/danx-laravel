<?php

namespace Flytedan\DanxLaravel\Helpers;

use Flytedan\DanxLaravel\Library\CsvExport;
use Flytedan\DanxLaravel\Models\Utilities\StoredFile as FileModel;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File as FileFacade;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Yaml\Parser as YamlParser;
use ZipArchive;

class FileHelper
{
	/**
	 * @param $name
	 * @return array|string|string[]|null
	 */
	public static function safeFilename($name)
	{
		$name = preg_replace('/[\\s%]/', '-', urldecode($name));

		if (strlen($name) > 103) {
			$extension = pathinfo($name, PATHINFO_EXTENSION);
			$name      = rtrim(substr($name, 0, 100), '. -%_()*&^$#@!') . '.' . $extension;
		}

		return $name;
	}

	/**
	 * Returns a human-readable string representing the number of bytes in the given number
	 *
	 * @param $byteSize
	 * @return string
	 */
	public static function getHumanSize($byteSize)
	{
		$powers = [
			['pow' => 0, 'unit' => 'B'],
			['pow' => 10, 'unit' => 'KB'],
			['pow' => 20, 'unit' => 'MB'],
			['pow' => 30, 'unit' => 'GB'],
			['pow' => 40, 'unit' => 'TB'],
			['pow' => 50, 'unit' => 'PB'],
		];

		// Should always be an integer
		$byteSize = round($byteSize);

		foreach($powers as $power) {
			// Check the next unit up minimum value to get our current unit max
			$max = pow(2, $power['pow'] + 10);

			if ($max > $byteSize) {
				break;
			}
		}

		// Using PHP's scoping to our advantage ($power is set to most recent iteration in for loop)
		return round($byteSize / pow(2, $power['pow'])) . ' ' . $power['unit'];
	}

	/**
	 * Convert an array of records into a CSV string
	 * @param $data
	 * @param $delimiter
	 * @param $enclosure
	 * @param $escapeChar
	 * @return false|string
	 */
	public static function arrayToCsv($data, $delimiter = ',', $enclosure = '"', $escapeChar = "\\")
	{
		// Open a memory "file" for read/write...
		$fp = fopen('php://memory', 'r+');

		// Check if data is not empty
		if (empty($data)) {
			return false;
		}

		// Use the array keys as column headers
		fputcsv($fp, array_keys(reset($data)), $delimiter, $enclosure, $escapeChar);

		// Loop over the data, outputting each row
		foreach($data as $row) {
			fputcsv($fp, $row, $delimiter, $enclosure, $escapeChar);
		}

		// Rewind the "file" so we can read what we just wrote...
		rewind($fp);

		// Read all the data back and capture the output
		$csv = stream_get_contents($fp);

		// Close the "file"...
		fclose($fp);

		// Return the CSV data
		return $csv;
	}

	/**
	 * Convert an associative array of records into a CSV string
	 *
	 * @param $records
	 * @return bool|string
	 */
	public static function toCsv($records)
	{
		if (!is_array($records)) {
			if (method_exists($records, 'toArray')) {
				$records = $records->toArray();
			} else {
				$records = (array)$records;
			}
		}

		return FileHelper::exportCsv($records);
	}

	/**
	 * @param array $records
	 * @return string
	 */
	public static function exportCsv(array $records)
	{
		return (new CsvExport($records))->getCsvContent();
	}

	/**
	 * Parses a CSV string (see parseCsvFile)
	 *
	 * @param string $csv - the csv content to parse into an array
	 * @return array
	 */
	public static function parseCsv($csv)
	{
		$file = storage_path(uniqid() . 'parser-file.csv');

		file_put_contents($file, $csv);

		$rows = self::parseCsvFile($file);

		@unlink($file);

		return $rows;
	}

	/**
	 * Parse a CSV file into an Array
	 * Options to skip formatting
	 *
	 * @param     $file
	 * @param int $headerRowIndex
	 * @param int $firstContentRowIndex
	 * @return array
	 */
	public static function parseCsvFile($file, int $headerRowIndex = 0, int $firstContentRowIndex = 1): array
	{
		$rows          = [];
		$columnHeaders = [];
		$rowIndex      = 0;
		$columnCount   = 0;

		if (($handle = fopen($file, 'r')) !== false) {
			while(($rowData = fgetcsv($handle, 0, ',')) !== false) {
				if ($rowIndex == $headerRowIndex) {
					$columnHeaders = $rowData;
					$columnCount   = count($columnHeaders);
				}

				if ($rowIndex >= $firstContentRowIndex) {
					// Make sure our row data matches exactly the number of elements in our headers
					$paddedRowData = array_slice(array_pad($rowData, $columnCount, ''), 0, $columnCount);

					// Only add rows until we have reached an entirely empty row (then assume this is the end of the file)
					if (array_filter($paddedRowData, fn($value) => $value !== '')) {
						$rows[] = array_combine($columnHeaders, $paddedRowData);
					} else {
						break;
					}
				}

				$rowIndex++;
			}
		}

		return $rows;
	}

	/**
	 * Create a zip file containing all the files in the given collection
	 * @param                               $name
	 * @param Collection|array|FileFacade[] $files
	 * @return ZipArchive
	 * @throws FileNotFoundException
	 */
	public static function createZipFile($name, Collection|array $files)
	{
		// Make sure the directory exists
		if (!is_dir(dirname($name))) {
			mkdir(dirname($name), 0777, true);
		}

		// Create the zip archive
		$zip = new ZipArchive();
		// Used to guarantee unique names
		$names = [];

		$zip->open($name, ZipArchive::CREATE);
		foreach($files as $file) {
			// Parse the filename and contents
			if ($file instanceof FileModel) {
				$name     = $file->filename;
				$contents = $file->getContents();
			} elseif ($file instanceof FileFacade) {
				$name     = $file->name;
				$contents = file_get_contents($file->path);
			} elseif (is_string($file)) {
				$name     = basename($file);
				$contents = file_get_contents($file);
			} else {
				throw new FileNotFoundException('File not found: ' . json_encode($file));
			}

			// Guarantee unique names for each entry
			if (in_array($name, $names)) {
				$name = substr(uuid(), 19, 4) . '-' . $name;
			}

			// Add the file to the archive
			$zip->addFromString($name, $contents);
			$names[] = $name;
		}
		// Save the Zip file to disk
		$zip->close();

		return $zip;
	}

	/**
	 * Converts a file contents to UTF-8 encoding and returns the converted contents
	 *
	 * @param $file
	 * @return false|string
	 */
	public static function convertToUtf8($file)
	{
		$contents = file_get_contents($file);
		$encoding = mb_detect_encoding($contents, 'UTF-8, ISO-8859-1, WINDOWS-1252, WINDOWS-1251', true);

		if ($encoding != 'UTF-8') {
			return iconv($encoding, 'UTF-8//IGNORE', $contents);
		}

		return $contents;
	}

	/**
	 * Parses a YAML string to an associative array
	 *
	 * @param     $yaml
	 * @param int $flags
	 * @return array
	 */
	public static function parseYaml($yaml, $flags = 0)
	{
		$parser = new YamlParser();

		return $parser->parse($yaml, $flags);
	}

	/**
	 * Parses a YAML file to an associative array
	 *
	 * @param     $file
	 * @param int $flags
	 * @return array
	 */
	public static function parseYamlFile($file, $flags = 0)
	{
		$parser = new YamlParser();

		return $parser->parseFile($file, $flags);
	}

	/**
	 * Recursively remove a directory
	 *
	 * @param $dir
	 * @return void
	 */
	public static function rrmdir($dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach($objects as $object) {
				if ($object != '.' && $object != '..') {
					if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . '/' . $object)) {
						static::rrmdir($dir . DIRECTORY_SEPARATOR . $object);
					} else {
						unlink($dir . DIRECTORY_SEPARATOR . $object);
					}
				}
			}
			rmdir($dir);
		}
	}

	/**
	 * @param $exif
	 * @return array|float[]|int[]
	 */
	public static function getExifLocation($exif)
	{
		if (isset($exif['GPSLatitude']) && isset($exif['GPSLongitude'])) {
			$lat = static::getExifGps($exif['GPSLatitude'], $exif['GPSLatitudeRef'] ?? 'N');
			$lon = static::getExifGps($exif['GPSLongitude'], $exif['GPSLongitudeRef'] ?? 'W');

			return [$lat, $lon];
		}

		return [null, null];
	}

	/**
	 * @param $gps
	 * @param $direction
	 * @return float|int|string
	 */
	public static function getExifGps($gps, $direction)
	{
		$degrees   = count($gps) > 0 ? static::getExifGpsDegrees($gps[0]) : 0;
		$minutes   = count($gps) > 1 ? static::getExifGpsDegrees($gps[1]) : 0;
		$seconds   = count($gps) > 2 ? static::getExifGpsDegrees($gps[2]) : 0;
		$plusMinus = $direction == 'W' || $direction == 'S' ? -1 : 1;

		return $plusMinus * ($degrees + ($minutes / 60) + ($seconds / 3600));
	}

	/**
	 * @param $gps
	 * @return float|int|string
	 */
	public static function getExifGpsDegrees($gps)
	{
		$parts = explode('/', $gps);
		$part1 = $parts[0] ?? 0;
		$part2 = $parts[1] ?? 0;

		if ($part1 && $part2) {
			return $part1 / $part2;
		} else {
			return $part1;
		}
	}

	/**
	 * @param $url
	 * @return bool
	 */
	public static function isPdf($url)
	{
		// Initialize cURL
		$ch = curl_init($url);

		// Set options: we want a HEAD request
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// Execute the request
		$data = curl_exec($ch);

		// Check if the request was successful
		if ($data !== false) {
			// Get the Content-Type of the URL
			$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

			// Check if the Content-Type is 'application/pdf'
			if (strpos($contentType, 'application/pdf') !== false) {
				return true;
			}
		}

		// Close the cURL resource
		curl_close($ch);

		return false;
	}

	/**
	 * @param $filename
	 * @return mixed|string
	 */
	public static function getMimeFromExtension($filename)
	{
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		$mimes     = MimeTypes::getDefault()->getMimeTypes($extension);

		return $mimes[0] ?? 'application/octet-stream';
	}
}
