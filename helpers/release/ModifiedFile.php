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
    protected $filename;

    /** @var string */
    protected $modificator;

    /**
     * ModifiedFile constructor.
     * @param string $filename
     * @param string $modificator
     */
    public function __construct($filename, $modificator = self::Modified)
    {
        $this->filename = $filename;
        $this->modificator = $modificator;
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
}
