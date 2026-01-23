<?php

namespace ipl\Tests\Html\Lib;

use ipl\Html\FormElement\FileElement;

class FileElementWithAdjustableConfig extends FileElement
{
    public static $defaultMaxFileSize;

    public static $postMaxSize = '8M';

    public static $uploadMaxFilesize = '2M';

    protected static function getPostMaxSize(): string
    {
        return self::$postMaxSize;
    }

    protected static function getUploadMaxFilesize(): string
    {
        return self::$uploadMaxFilesize;
    }
}
