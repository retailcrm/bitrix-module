<?php

require_once 'ModifiedFile.php';

/**
 * Class ReleaseBuilder
 */
class ReleaseBuilder
{
    /** @var ModifiedFile[] */
    protected $files;

    /** @var string */
    protected $releaseDir;

    /**
     * ReleaseBuilder constructor.
     * @param ModifiedFile[] $files
     * @param string $releaseVersion
     * @throws \RuntimeException
     */
    public function __construct($files, $releaseVersion)
    {
        $this->files = $files;

        if (!defined('RELEASE_DIR') || !defined('ORIGINAL')) {
            throw new \RuntimeException('`RELEASE_DIR` or `ORIGINAL` not defined');
        }

        $this->releaseDir = RELEASE_DIR . $releaseVersion . '/';
    }

    /**
     * @return void
     */
    public function build()
    {
        $this->createReleaseDir();
        $modifiedFiles = [];

        foreach ($this->files as $file) {
            if (!$file->isModuleFile() || $file->isDeleted()) {
                continue;
            }

            $modifiedFiles[] = $this->getRealFilename($file->getFilename());
        }

        if (empty($modifiedFiles)) {
            throw new \LogicException('Not found modified files for release');
        }

        $this->createDirNodes($modifiedFiles);
        $this->copyFiles($modifiedFiles);
    }

    /**
     * @param string[] $files
     */
    private function copyFiles($files)
    {
        foreach ($files as $file) {
            copy(ORIGINAL . $file, $this->releaseDir . $file);
        }
    }

    /**
     * @param string[] $files
     */
    private function createDirNodes($files)
    {
        $paths = [];

        foreach ($files as $file) {
            $dirs = explode('/', $file, -1);
            $path = $this->releaseDir;

            foreach ($dirs as $dir) {
                $path .= $dir . '/';
                $paths[] = $path;
            }
        }

        foreach ($paths as $path) {
            if (!file_exists($path)) {
                mkdir($path, 0755);
            }
        }
    }

    /**
     * @return void
     */
    private function createReleaseDir()
    {
        if (!file_exists($this->releaseDir)) {
            mkdir($this->releaseDir, 0755);
        }
    }

    /**
     * @param string $filename
     * @return string
     */
    private function getRealFilename($filename)
    {
        return str_replace(ModifiedFile::MODULE_ID . '/', '', $filename);
    }
}
