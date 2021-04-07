<?php

namespace Intaro\RetailCrm\Repository;

use CIBlockElement;

class FileRepository
{
    /**
     * @param $fileId
     * @return string
     */
    public function getImageUrl($fileId): string
    {
        $pathImage  = CFile::GetPath($fileId);
        $validation = '/^(http|https):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i';
        
        if ((bool)preg_match($validation, $pathImage) === false) {
            return $this->setup->defaultServerName . $pathImage;
        }
        
        return $pathImage;
    }
}
