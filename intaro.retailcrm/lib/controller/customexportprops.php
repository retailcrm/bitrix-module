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

        $requestData = json_decode($request, true);
        $properties = $requestData['properties'];
        $profileId = $requestData['profileId'];

        foreach ($properties as $catalogId => $propertyArray) {
            $newPropertiesString = '';
            foreach ($propertyArray as $property) {
                $newPropertiesString .= PHP_EOL . $property['code'] . ' = ' . $property['title'];
            }
            $filePath = sprintf(
                '%s/%s_profileId_%s_catalogId_%s.txt',
                $_SERVER['DOCUMENT_ROOT'] . '/local',
                'icml_property_retailcrm',
                $profileId,
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
        $request = $this->getRequest()->getInput();

        $requestData = json_decode($request, true);
        $properties = $requestData['properties'];
        $profileId = $requestData['profileId'];

        foreach ($properties as $catalogId => $propsArray) {
            $filePath = sprintf(
                '%s/%s_profileId_%s_catalogId_%s.txt',
                $_SERVER['DOCUMENT_ROOT'] . '/local',
                'icml_property_retailcrm',
                $profileId,
                $catalogId
            );
            $fileContent = file_get_contents($filePath);

            foreach ($propsArray as $property) {
                $propStringToDelete = PHP_EOL . $property['code'] . ' = ' . $property['title'];
                $fileContent = str_replace($propStringToDelete, '', $fileContent);
            }
            file_put_contents($filePath, $fileContent);
        }
    }
}