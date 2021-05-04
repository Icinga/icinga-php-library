<?php

class AssetLoader
{
    public static $awesomeVendorFiles = [
        'asset/static/font/awesome' => 'vendor/fortawesome/font-awesome/webfonts',
        'asset/css'                 => 'vendor/fortawesome/font-awesome/css/fontawesome.css'
    ];

    public static function update(Composer\Script\Event $event)
    {
        $copy = in_array('copy-assets', $event->getArguments(), true);

        if (is_dir('asset')) {
            // Check for removed files
            $fs = new Composer\Util\Filesystem();
            $assets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                'asset',
                FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
            ), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($assets as $asset) {
                /** @var SplFileInfo $asset */
                if ($asset->isDir()) {
                    if ($fs->isDirEmpty($asset->getPathname())) {
                        rmdir($asset);
                    }
                } elseif (! $asset->isReadable()) {
                    unlink($asset);
                }
            }
        }

        // Check for new files
        $vendorLibs = new FilesystemIterator('vendor/ipl');
        foreach ($vendorLibs as $vendorLib) {
            /** @var SplFileInfo $vendorLib */
            $assetDir = join(DIRECTORY_SEPARATOR, [$vendorLib->getRealPath(), 'asset']);
            if (is_readable($assetDir) && is_dir($assetDir)) {
                $libAssets = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                    $assetDir,
                    FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
                ), RecursiveIteratorIterator::SELF_FIRST);
                foreach ($libAssets as $asset) {
                    /** @var SplFileInfo $asset */
                    $relativePath = ltrim(substr($asset->getPathname(), strlen($vendorLib->getRealPath())), '/\\');
                    if (file_exists($relativePath)) {
                        continue;
                    }

                    if ($asset->isDir()) {
                        mkdir($relativePath, 0755, true);
                    } elseif ($asset->isFile()) {
                        if ($copy) {
                            copy($asset->getPathname(), $relativePath);
                        } else {
                            symlink($asset->getPathname(), $relativePath);
                        }
                    }
                }
            }
        }

        // Register font-awesome files as assets
        foreach (static::$awesomeVendorFiles as $targetPath => $sourcePath) {
            $sourcePath = realpath($sourcePath);
            if (! $sourcePath) {
                continue;
            }

            if (is_dir($sourcePath)) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }

                $awesomeFiles = new FilesystemIterator($sourcePath);
            } else { // is_file($sourcePath)
                $awesomeFiles = [new SplFileInfo($sourcePath)];
                $sourcePath = $awesomeFiles[0]->getPath();
            }

            foreach ($awesomeFiles as $awesomeFile) {
                /** @var SplFileInfo $awesomeFile */
                $relativePath = join(DIRECTORY_SEPARATOR, [$targetPath, ltrim(
                    substr($awesomeFile->getPathname(), strlen($sourcePath)),
                    '/\\'
                )]);
                if (! file_exists($relativePath) && $awesomeFile->isFile()) {
                    if ($copy) {
                        copy($awesomeFile->getPathname(), $relativePath);
                    } else {
                        symlink($awesomeFile->getPathname(), $relativePath);
                    }
                }
            }
        }
    }
}
