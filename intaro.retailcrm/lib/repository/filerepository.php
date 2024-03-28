<?php

namespace Intaro\RetailCrm\Repository;

use CFile;

/**
 * Class FileRepository
 * @package Intaro\RetailCrm\Repository
 */
class FileRepository
{
    /**
     * @var string
     */
    private $defaultServerName;

    /** @var array */
    private $domainCatalogList = [];
    
    /**
     * FileRepository constructor.
     * @param string $defaultServerName
     */
    public function __construct(string $defaultServerName)
    {
        $this->defaultServerName = $defaultServerName;
        $this->domainCatalogList = SiteRepository::getDomainList();
    }
    
    /**
     * @param int|null $fileId
     * @return string
     */
    public function getImageUrl(?int $fileId): string
    {
        if (!$fileId) {
            return '';
        }
        
        $pathImage  = CFile::GetPath($fileId);
        $validation = '/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';
        
        if ((bool) preg_match($validation, $pathImage) === false) {
            return $this->defaultServerName . $pathImage;
        }
        
        return $pathImage;
    }
    
    /**
     * @param array  $product
     * @param string $pictureProp
     * @return string
     */
    public function getProductPicture(array $product, string $pictureProp = ''): string
    {
        $picture    = '';
        $pictureId  = $product['PROPERTY_' . $pictureProp . '_VALUE'] ?? null;
        
        if (isset($product['DETAIL_PICTURE'])) {
            $picture = $this->getImageUrl($product['DETAIL_PICTURE']);
        } elseif (isset($product['PREVIEW_PICTURE'])) {
            $picture = $this->getImageUrl($product['PREVIEW_PICTURE']);
        } elseif ($pictureId !== null) {
            $picture = $this->getImageUrl($pictureId);
        }
        
        return $picture ?? '';
    }
}
