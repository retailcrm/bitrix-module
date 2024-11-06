<?php

namespace Intaro\RetailCrm\Controller;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Result;
use Bitrix\Main\Error;

/**
 * @category Integration
 * @package  Intaro\RetailCrm\Controller
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */
class CustomExportProps extends Controller
{
    public function saveAction()
    {
        $request = $this->getRequest()->getInput();
        $response = new Result();

        if ($request === null) {
            $response->setStatus(new Error('Ошибка'));
        }

        $properties = json_decode($this->getRequest()->getInput(), true)['properties'];

        foreach ($properties as $catalogId => $propertyArray) {
            $newPropertiesString = '';
            foreach ($propertyArray as $property) {
                $newPropertiesString .= PHP_EOL . $property['code'] . ' = ' . $property['title'];
            }
            $filePath = sprintf(
                '%s/%s_%s.txt',
                $_SERVER['DOCUMENT_ROOT'] . '/local',
                'icml_property_retailcrm',
                $catalogId
            );
            $saveResult = file_put_contents($filePath, $newPropertiesString, FILE_APPEND);
        }

        if (!$saveResult) {
            $response->setStatus(new Error('Ошибка'));
        }

//        return $response->setStatus(Result::SUCCESS);
    }

    public function deleteAction()
    {
        
    }
}