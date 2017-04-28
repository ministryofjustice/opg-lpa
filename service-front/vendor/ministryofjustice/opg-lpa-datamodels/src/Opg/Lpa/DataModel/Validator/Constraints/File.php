<?php

namespace Opg\Lpa\DataModel\Validator\Constraints;

use Symfony\Component\Validator\Constraints as SymfonyConstraints;

class File extends SymfonyConstraints\File
{
    use ValidatorPathTrait;

    // Check the Image constraint for clashes if adding new constants here
    const NOT_FOUND_ERROR         = 1;
    const NOT_READABLE_ERROR      = 2;
    const EMPTY_ERROR             = 3;
    const TOO_LARGE_ERROR         = 4;
    const INVALID_MIME_TYPE_ERROR = 5;

    protected static $errorNames = [
        self::NOT_FOUND_ERROR => 'NOT_FOUND_ERROR',
        self::NOT_READABLE_ERROR => 'NOT_READABLE_ERROR',
        self::EMPTY_ERROR => 'EMPTY_ERROR',
        self::TOO_LARGE_ERROR => 'TOO_LARGE_ERROR',
        self::INVALID_MIME_TYPE_ERROR => 'INVALID_MIME_TYPE_ERROR',
    ];

    public $maxSize;
    public $binaryFormat;
    public $mimeTypes = [];
    public $notFoundMessage = 'The file could not be found.';
    public $notReadableMessage = 'The file is not readable.';
    public $maxSizeMessage = 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}.';
    public $mimeTypesMessage = 'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.';
    public $disallowEmptyMessage = 'An empty file is not allowed.';

    public $uploadIniSizeErrorMessage = 'The file is too large. Allowed maximum size is {{ limit }} {{ suffix }}.';
    public $uploadFormSizeErrorMessage = 'The file is too large.';
    public $uploadPartialErrorMessage = 'The file was only partially uploaded.';
    public $uploadNoFileErrorMessage = 'No file was uploaded.';
    public $uploadNoTmpDirErrorMessage = 'No temporary folder was configured in php.ini.';
    public $uploadCantWriteErrorMessage = 'Cannot write temporary file to disk.';
    public $uploadExtensionErrorMessage = 'A PHP extension caused the upload to fail.';
    public $uploadErrorMessage = 'The file could not be uploaded.';
}
