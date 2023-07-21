<?php

/**
 * Class RetailcrmClasspathBuilder.
 * Builds classpath for Bitrix autoloader. Contains some hardcoded things, which will go away when everything refactored.
 */
class RetailcrmClasspathBuilder
{
    /**
     * File extension as a string. Defaults to ".php".
     * @var string
     */
    protected $fileExt = 'php';

    /**
     * @var string $moduleId
     */
    protected $moduleId = 'intaro.retailcrm';

    /**
     * @var string
     */
    protected $customizedFilesPath = '/bitrix/php_interface/retailcrm/';

    /**
     * @var string
     */
    protected $customizedClassesPath = '../../php_interface/retailcrm/';

    /**
     * The topmost directory where recursion should begin. Default: `classes/general`. Relative to the __DIR__.
     * @var string
     */
    protected $path = 'classes/general';

    /**
     * @var string
     */
    protected $bitrixModulesPath = 'bitrix/modules';

    /**
     * Do not include directory paths as namespaces.
     * @var bool
     */
    protected $disableNamespaces;

    /**
     * Bitrix document root
     * @var string
     */
    protected $documentRoot;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var array $result
     */
    protected $result = [];

    /**
     * @var array $notIncluded
     */
    protected $notIncluded = [];

    protected $directories = [];

    /**
     * These classes can be customized, in which case they would be replaced with files from
     * directory <root>/bitrix/php_interface/retailcrm
     *
     * Array format:
     * [
     *      'class path with namespace' => 'file name'
     * ]
     */
    protected static $customizableClasses = [
        'classes' => [
            'RestNormalizer' => 'RestNormalizer.php',
            'Logger' => 'Logger.php',
            'RetailCrm\ApiClient' => 'ApiClient_v5.php',
            'RetailCrm\Http\Client' => 'Client.php',
            'RCrmActions' => 'RCrmActions.php',
            'RetailCrmUser' => 'RetailCrmUser.php',
            'RetailCrmICML' => 'RetailCrmICML.php',
            'RetailCrmInventories' => 'RetailCrmInventories.php',
            'RetailCrmPrices' => 'RetailCrmPrices.php',
            'RetailCrmCollector' => 'RetailCrmCollector.php',
            'RetailCrmUa' => 'RetailCrmUa.php',
            'RetailCrmEvent' => 'RetailCrmEvent.php',
            'RetailCrmCorporateClient' => 'RetailCrmCorporateClient.php'
        ],
        'lib/icml' => [
            'Intaro\RetailCrm\Icml\XmlOfferBuilder' => 'xmlofferbuilder.php',
            'Intaro\RetailCrm\Icml\XmlOfferDirector' => 'xmlofferdirector.php',
            'Intaro\RetailCrm\Icml\IcmlWriter' => 'icmlwriter.php',
            'Intaro\RetailCrm\Icml\QueryParamsMolder' => 'queryparamsmolder.php',
            'Intaro\RetailCrm\Icml\SettingsService' => 'settingsservice.php',
            'Intaro\RetailCrm\Icml\XmlCategoryDirector' => 'xmlcategorydirector.php',
            'Intaro\RetailCrm\Icml\XmlCategoryFactory' => 'xmlcategoryfactory.php',
            'Intaro\RetailCrm\Icml\IcmlDirector' => 'icmldirector.php'
        ]
    ];

    /**
     * These classes can be customized, in which case they would be replaced with files from
     * directory <root>/bitrix/php_interface/retailcrm
     * Customized versions have fixed name, and original versions name depends on API version
     *
     * Array format:
     * [
     *      'class path with namespace' => ['customized file name', 'original file name with %s for API version']
     * ]
     */
    protected static $versionedClasses = [
        'classes' => [
            'RetailCrm\ApiClient' => ['ApiClient.php', 'ApiClient_%s.php'],
            'RetailCrmOrder' => ['RetailCrmOrder.php', 'RetailCrmOrder_%s.php'],
            'RetailCrmHistory' => ['RetailCrmHistory.php', 'RetailCrmHistory_%s.php'],
            'RetailCrmCart' => ['RetailCrmCart.php', 'RetailCrmCart_%s.php']
        ],
        'lib/icml' => []
    ];

    /**
     * These classes will be ignored while loading from original files
     */
    protected static $ignoredClasses = [
        'classes' => [
            'ApiClient_v4.php',
            'ApiClient_v5.php',
            'RetailCrmOrder_v4.php',
            'RetailCrmOrder_v5.php',
            'RetailCrmHistory_v4.php',
            'RetailCrmHistory_v5.php',
            'RetailCrmCart_v5.php',
        ],
        'lib/icml' => []
    ];

    /**
     * These namespaces are hardcoded.
     */
    protected static $hardcodedNamespaces = [
        'classes' => [
            'RetailCrm\Response\ApiResponse' => 'ApiResponse.php',
            'RetailCrm\Exception\InvalidJsonException' => 'InvalidJsonException.php',
            'RetailCrm\Exception\CurlException' => 'CurlException.php'
        ],
        'lib/icml' => []
    ];

    protected function buildCustomizableClasspath()
    {
        foreach (static::$customizableClasses[$this->path] as $className => $fileName) {
            if (file_exists($this->documentRoot . $this->customizedFilesPath . $fileName)) {
                $this->result[$className] = $this->customizedClassesPath . $fileName;
            } else {
                $this->notIncluded[$className] = $fileName;
            }
        }
    }

    protected function buildVersionedClasspath()
    {
        foreach (static::$versionedClasses[$this->path] as $className => $fileNames) {
            if (file_exists($this->documentRoot . $this->customizedFilesPath . $fileNames[0])) {
                $this->result[$className] = $this->customizedClassesPath . $fileNames[0];
            } else {
                $this->notIncluded[$className] = sprintf($fileNames[1], $this->version);
            }
        }
    }

    /**
     * Traverse through directories, build include paths
     * @return $this
     */
    public function build(): self
    {
        foreach ($this->directories as $path) {
            $this->path = $path;

            $directory = new RecursiveDirectoryIterator(
                $this->getSearchPath(),
                RecursiveDirectoryIterator::SKIP_DOTS
            );

            $fileIterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);

            $this->buildCustomizableClasspath();
            $this->buildVersionedClasspath();

            $notIncludedClasses = array_flip($this->notIncluded);
            $hardcodedNamespaces = array_flip(static::$hardcodedNamespaces[$path]);

            /** @var \SplFileObject $file */
            foreach ($fileIterator as $file) {
                $fileNameWithoutExt = str_ireplace('.' . $this->fileExt, '', $file->getFilename());

                if ($file->getExtension() !== $this->fileExt) {
                    continue;
                }

                if (in_array($file->getFilename(), static::$customizableClasses[$path])
                    || in_array($file->getFilename(), static::$ignoredClasses[$path])
                ) {
                    if (in_array($file->getFilename(), $this->notIncluded)) {
                        $this->result[$notIncludedClasses[$file->getFilename()]] = $this->getImportPath($file->getPathname());
                    }

                    continue;
                }

                if (in_array($file->getFilename(), static::$hardcodedNamespaces[$path])) {
                    $this->result[$hardcodedNamespaces[$file->getFilename()]] = $this->getImportPath($file->getPathname());
                } else {
                    $this->result[$this->getImportClass($fileNameWithoutExt, $file->getPath())] = $this->getImportPath($file->getPathname());
                }
            }
        }

        return $this;
    }

    /**
     * Sets the $fileExt property
     *
     * @param string $fileExt The file extension used for class files.  Default is "php".
     *
     * @return \RetailcrmClasspathBuilder
     */
    public function setFileExt($fileExt)
    {
        $this->fileExt = $fileExt;
        return $this;
    }

    /**
     * @param string $documentRoot
     *
     * @return RetailcrmClasspathBuilder
     */
    public function setDocumentRoot(string $documentRoot): RetailcrmClasspathBuilder
    {
        $this->documentRoot = $documentRoot;
        return $this;
    }

    /**
     * Sets the $path property
     *
     * @param array $path Top path to load files
     *
     * @return \RetailcrmClasspathBuilder
     */
    public function setPath(array $path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param array $directories
     * @return \RetailcrmClasspathBuilder
     */
    public function setDirectories(array $directories)
    {
        $this->directories = $directories;

        return $this;
    }

    /**
     * @param mixed $disableNamespaces
     *
     * @return RetailcrmClasspathBuilder
     */
    public function setDisableNamespaces($disableNamespaces)
    {
        $this->disableNamespaces = $disableNamespaces;
        return $this;
    }

    /**
     * @param string $moduleId
     *
     * @return RetailcrmClasspathBuilder
     */
    public function setModuleId(string $moduleId): RetailcrmClasspathBuilder
    {
        $this->moduleId = $moduleId;
        return $this;
    }

    /**
     * @param string $version
     *
     * @return RetailcrmClasspathBuilder
     */
    public function setVersion(string $version): RetailcrmClasspathBuilder
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @return string
     */
    protected function getSearchPath(): string
    {
        return $this->documentRoot . DIRECTORY_SEPARATOR . $this->bitrixModulesPath . DIRECTORY_SEPARATOR
            . $this->moduleId. DIRECTORY_SEPARATOR . $this->path;
    }

    /**
     * @param string $filePath
     *
     * @return string
     */
    protected function getImportPath(string $filePath): string
    {
        return (string) str_ireplace(implode(DIRECTORY_SEPARATOR, [
            $this->documentRoot,
            $this->bitrixModulesPath,
            $this->moduleId
        ]) . DIRECTORY_SEPARATOR, '', $filePath);
    }

    /**
     * @param string $fileNameWithoutExt
     * @param string $filePath
     *
     * @return string
     */
    protected function getImportClass(string $fileNameWithoutExt, string $filePath): string
    {
        if ($this->disableNamespaces) {
            return $fileNameWithoutExt;
        }

        $importClass = str_ireplace($this->getSearchPath(),  '', $filePath). '\\' . $fileNameWithoutExt;

        if (strlen($importClass) > 0 && $importClass[0] === '/') {
            $importClass = '\\' . substr($importClass, 1);
        }

        return (string) str_replace(DIRECTORY_SEPARATOR, '\\', $importClass);
    }
}
