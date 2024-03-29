<?php

namespace Flytedan\DanxLaravel\Models\File;

use Exception;
use Flytedan\DanxLaravel\Helpers\FileHelper;
use Flytedan\DanxLaravel\Traits\SerializesDates;
use Flytedan\DanxLaravel\Traits\UuidModelTrait;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class File extends Model implements AuditableContract
{
    use
        Auditable,
        SerializesDates,
        SoftDeletes,
        UuidModelTrait;

    const
        MIME_3G2 = 'video/3gpp2',
        MIME_3GP = 'video/3gpp',
        MIME_EPS = 'image/x-eps',
        MIME_EXCEL = 'application/vnd.ms-excel',
        MIME_GIF = 'image/gif',
        MIME_HEIC = 'image/heic',
        MIME_HTML = 'text/html',
        MIME_ICON = 'image/x-icon',
        MIME_JPEG = 'image/jpeg',
        MIME_JSON = 'application/json',
        MIME_M4V = 'video/x-m4v',
        MIME_MP2T = 'video/mp2t',
        MIME_MP4 = 'video/mp4',
        MIME_MPEG = 'video/mpeg',
        MIME_MS_OFFICE = 'application/vnd.ms-office',
        MIME_OCTET = 'application/octet-stream',
        MIME_OGG = 'video/ogg',
        MIME_OPEN_WORD = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        MIME_PDF = 'application/pdf',
        MIME_PHOTOSHOP = 'image/vnd.adobe.photoshop',
        MIME_PNG = 'image/png',
        MIME_QUICKTIME = 'video/quicktime',
        MIME_SVG = 'image/svg',
        MIME_TEXT = 'text/plain',
        MIME_TIFF = 'image/tiff',
        MIME_WEBM = 'video/webm',
        MIME_WEBP = 'image/webp',
        MIME_OPEN_SHEET = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        MIME_ZIP = 'application/zip';

    const IMAGE_MIMES = [
        self::MIME_EPS,
        self::MIME_GIF,
        self::MIME_HEIC,
        self::MIME_ICON,
        self::MIME_JPEG,
        self::MIME_PHOTOSHOP,
        self::MIME_PNG,
        self::MIME_SVG,
        self::MIME_TIFF,
        self::MIME_WEBP,
    ];

    const VIDEO_MIMES = [
        self::MIME_3G2,
        self::MIME_3GP,
        self::MIME_M4V,
        self::MIME_MP2T,
        self::MIME_MP4,
        self::MIME_MPEG,
        self::MIME_OGG,
        self::MIME_QUICKTIME,
        self::MIME_WEBM,
    ];

    const CANNOT_TRANSCODE_MIMES = [
        self::MIME_EXCEL,
        self::MIME_HTML,
        self::MIME_JSON,
        self::MIME_MS_OFFICE,
        self::MIME_OCTET,
        self::MIME_OPEN_SHEET,
        self::MIME_OPEN_WORD,
        self::MIME_TEXT,
        self::MIME_ZIP,
    ];

    protected $table = 'file';

    protected $keyType = 'string';

    protected $fillable = [
        'disk',
        'filename',
        'filepath',
        'url',
        'mime',
        'requires_transcode',
        'is_transcode_complete',
        'transcodes',
        'transcoding_start_at',
        'size',
        'exif',
        'meta',
        'storable_subtype',
    ];

    protected $casts = [
        'transcodes' => 'json',
        'exif'       => 'json',
        'meta'       => 'json',
        'location'   => 'json',
    ];

    /** @var string Cached file contents blob */
    protected $contents;

    /** @var bool If the cached content has changed */
    protected $contentsChanged = false;

    /**
     * Synchronize the list of files to the instance type. Disassociates any existing
     * relationships, and attaches the existing file ID's to $id for $type
     *
     * @param $id
     * @param $type
     * @param $files
     * @param null $subtype
     * @return bool
     */
    public static function sync($id, $type, $files, $subtype = null)
    {
        self::unguard();
        // Remove any old files currently associated to the type instance
        self::where('storable_id', $id)
            ->where('storable_type', $type)
            ->where('storable_subtype', $subtype)
            ->update([
                'storable_id'   => '',
                'storable_type' => '',
            ]);

        // Associate the new files to the type instance
        self::whereIn('id', $files)
            ->update([
                'storable_id'      => $id,
                'storable_type'    => $type,
                'storable_subtype' => $subtype,
            ]);
        self::reguard();

        return true;
    }

    /**
     * Handle CUD events
     */
    public static function boot()
    {
        parent::boot();

        static::saving(function (self $file) {
            if (!$file->url) {
                $file->url = $file->storageDisk()->url($file->filepath);
            }
        });
    }

    /**
     * @return MorphTo
     */
    public function storable()
    {
        return $this->morphTo();
    }

    /**
     * Returns the file contents either from the disk or any cached version
     * This is useful if we want to make a series of changes to a file
     * (ie: transcoding a file's contents)
     *
     * @return false|string
     */
    public function getContents()
    {
        // Cache file contents for quick retrieval
        if (!$this->contents) {
            $this->contents = $this->storageDisk()->get($this->filepath);
        }

        return $this->contents;
    }

    /**
     * Sets the cached contents for the file, does not write the file to the storage location
     *
     * @param $contents
     * @return $this
     */
    public function setContents($contents)
    {
        $this->contents        = $contents;
        $this->contentsChanged = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isContentsDirty()
    {
        return $this->contentsChanged;
    }

    /**
     * @return Filesystem|FilesystemAdapter
     */
    public function storageDisk()
    {
        return Storage::disk($this->disk);
    }

    /**
     * Writes any changes to the file's contents to the new filepath (if given)
     * and updates the file's stored size.
     * NOTE: This does NOT save the file! If you do not save it, the file will still reference the original
     *
     * @param null $filePath
     * @return mixed
     */
    public function write($filePath = null, $public = true)
    {
        if ($filePath) {
            $this->filepath = $filePath;
        }

        // Save it on the disk
        $this->storageDisk()->put(
            $this->filepath,
            $this->contents,
            $public ? 'public' : null
        );

        // Update the related fields to content changes
        $this->url  = $this->storageDisk()->url($this->filepath);
        $this->size = $this->storageDisk()->size($this->filepath);

        // Reset the flag so we know the contents have been saved to disk
        $this->contentsChanged = false;

        // Return the URL pointing to the formatted image stored on the disk
        return $this;
    }

    /**
     * Starts the transcoding process for the file by setting up the transcode placeholder data in the transcodes JSON object
     * @param $name
     * @param $filepath
     * @param $meta
     * @return $this
     */
    public function startTranscode($name, $filepath, $meta = [])
    {
        $transcodes        = $this->transcodes ?: [];
        $transcodes[$name] = [
                'filepath' => $filepath,
                'url'      => $this->storageDisk()->url($filepath),
                'size'     => 0,
                'start_at' => now()->toDateTimeString(),
            ] + $meta;

        $this->setAttribute('transcodes', $transcodes);

        return $this;
    }

	/**
	 * Writes this file's cached contents as a transcoded version of the file
	 *
	 * @param       $name
	 * @param array $meta
	 * @return $this
	 * @throws Exception
	 */
    public function writeTranscode($name, $meta = [])
    {
        $transcodes = $this->transcodes ?: [];
        $transcode  = $transcodes[$name] ?? null;

        if (!$transcode) {
            throw new Exception("Failed saving transcoded file: No transcode found for $name. Did you try startTranscode($name)? File ID $this->id");
        }

        $filepath = $transcode['filepath'];
        Log::debug('Writing transcode to ' . $this->storageDisk()->url($filepath));

        // Only save the transcoded file if there were changes
        if (!$this->isContentsDirty()) {
            throw new Exception("Failed saving transcoded file $filepath: There were no changes to the file");
        }

        // Save it on the disk
        $this->storageDisk()->put(
            $filepath,
            $this->contents,
            // All transcodes are intended to be public
            'public'
        );

        $transcode['size']         = $this->storageDisk()->size($filepath);
        $transcode['completed_at'] = now()->toDateTimeString();
        $transcode                 += $meta;
        $transcodes[$name]         = $transcode;

        $this->setAttribute('transcodes', $transcodes);

        // Always reset the contents after transcoding, so they will not be used for something else on accident
        $this->contents        = null;
        $this->contentsChanged = false;

        return $this;
    }

    /**
     * Sets the contents of the file to the given transcoded file
     *
     * @param $name
     * @return mixed|null
     *
     */
    public function useTranscode($name)
    {
        if ($this->hasTranscode($name)) {
            $transcode = $this->transcodes[$name];

            $this->contents        = $this->storageDisk()->get($transcode['filepath']);
            $this->contentsChanged = false;

            return $transcode;
        } else {
            return null;
        }
    }

    /**
     * Check if this file has already been transcoded in the given format
     *
     * @param $name
     * @return bool
     */
    public function hasTranscode($name)
    {
        return $this->transcodes && !empty($this->transcodes[$name]['size']);
    }

    /**
     * Retrieve the URL for the desired transcode of the file
     *
     * @param $name
     * @return File|array|null
     */
    public function transcodedFile($names)
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            if ($this->hasTranscode($name)) {
                $tFile = $this->transcodes[$name];

                $mime = match ($name) {
                    'mp4' => 'video/mp4',
                    default => 'image/png',
                };

                return new File([
                    'url'  => $tFile['url'],
                    'size' => $tFile['size'],
                    'mime' => $mime,
                ]);
            }
        }

        return null;
    }

    /**
     * Checks if the transcoding lock is set on the File
     *
     * @return mixed|string|null
     */
    public function isLockedForTranscoding()
    {
        return $this->refresh()->transcoding_start_at;
    }

    /**
     * @param $path
     * @return string
     */
    public function getUrl($path)
    {
        return $this->storageDisk()->url($path);
    }

    /**
     * @param $mimes
     * @return bool
     */
    public function isMime($mimes)
    {
        if (!is_array($mimes)) {
            $mimes = [$mimes];
        }

        return in_array($this->mime, $mimes);
    }

    /**
     * Checks if the file is an image format
     *
     * @return bool
     */
    public function isImage()
    {
        return in_array($this->mime, static::IMAGE_MIMES);
    }

    /**
     * Checks if the file is an image format (or a format renderable as an image)
     * @return bool
     */
    public function hasPreviewImage()
    {
        return $this->isImage() || $this->isPdf();
    }

    /**
     * Checks if the file is video format
     *
     * @return bool
     */
    public function isVideo()
    {
        return in_array($this->mime, static::VIDEO_MIMES);
    }

    /**
     * Checks if the file is a PDF
     *
     * @return bool
     */
    public function isPdf()
    {
        return $this->mime === static::MIME_PDF;
    }

    /**
     * Checks if this is a known mime type that cannot be transcoded
     *
     * @return bool
     */
    public function cannotTranscode()
    {
        return in_array($this->mime, static::CANNOT_TRANSCODE_MIMES);
    }

    /**
     * @return string|string[]
     */
    public function extension()
    {
        return pathinfo($this->filepath, PATHINFO_EXTENSION);
    }

    /**
     * @return string A human-readable version of the number of bytes in this file
     */
    public function getHumanSizeAttribute()
    {
        return FileHelper::getHumanSize($this->size);
    }

    /**
     * @return bool|void|null
     */
    public function forceDelete()
    {
        // Delete the actual stored file
        $this->storageDisk()->delete($this->filepath);

        // Make sure we clean up any transcoded files
        if ($this->transcodes) {
            foreach ($this->transcodes as $transcode) {
                if (!empty($transcode['filepath'])) {
                    $this->storageDisk()->delete($transcode['filepath']);
                }
            }
        }

        return parent::forceDelete();
    }

    /**
     * @return array
     */
    public function resolveLocation()
    {
        $latitude  = null;
        $longitude = null;

        if ($this->exif) {
            [$latitude, $longitude] = FileHelper::getExifLocation($this->exif);
        }

        if ($latitude === null || $longitude === null) {
            $latitude  = $this->meta['latitude'] ?? null;
            $longitude = $this->meta['longitude'] ?? null;
        }

        if ($latitude !== null && $longitude !== null) {
            return [
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ];
        }

        return [];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "File ($this->id) $this->filename [$this->mime, $this->human_size]";
    }
}
