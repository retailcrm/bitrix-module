<?php

/**
 * Class ModifiedFile
 */
class ModifiedFile
{
    /** @var string */
    const ADDED = 'A';

    /** @var string */
    const DELETED = 'D';

    /** @var string */
    const MODIFIED = 'M';

    /** @var string */
    const RENAMED = 'R';

    /** @var string */
    const MODULE_ID  = 'intaro.retailcrm';

    /** @var string */
    const DESCRIPTION = 'description.ru';

    /** @var string */
    const VERSION = 'install/version.php';

    /** @var string */
    protected $filename;

    /** @var string */
    protected $oldFilename;

    /** @var string */
    protected $modificator;

    /** @var int */
    protected $percent;

    /**
     * ModifiedFile constructor.
     * @param string $source
     */
    public function __construct($source)
    {
        $params = explode("\t", trim($source));

        $this->filename = $params[1];
        $this->modificator = $params[0][0];

        if (strlen($params[0]) > 1) {
            $this->percent = (int) substr($params[0], 1);
        }

        if (count($params) >= 3) {
            $this->filename = $params[2];
            $this->oldFilename = $params[1];
        }
    }

    /**
     * @return bool
     */
    public function isAdded()
    {
        return $this->modificator === static::ADDED;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->modificator === static::DELETED;
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return $this->modificator === static::MODIFIED;
    }

    /**
     * @return bool
     */
    public function isRenamed()
    {
        return $this->modificator === static::RENAMED;
    }

    /**
     * @return bool
     */
    public function isModuleFile()
    {
        return strpos($this->filename, static::MODULE_ID) === 0;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getOldFilename()
    {
        return $this->oldFilename;
    }

    /**
     * @return int
     */
    public function getPercent()
    {
        return $this->percent;
    }
}
