<?php

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Application;
use Intaro\RetailCrm\Icml\SettingsService;

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
    private function getRequestData(): array
    {
        $data = $this->getRequest()->getInput();

        if ($data === null) {

        }

        return json_decode($data, true);
    }

    public function saveAction()
    {
        $settingsService = SettingsService::getInstance(
            [],
            null
        );

        $requestData = $this->getRequestData();
        $props = $requestData['properties'];
        $profileId = $requestData['profileId'];

        $dbConnection = Application::getInstance()->getConnection();
        try {
            $dbConnection->startTransaction();
            foreach ($props as $catalogId => $propsArray) {
                $catalogCustomProps = [];
                foreach ($propsArray as $property) {
                    $catalogCustomProps[] = [
                        'code' => $property['code'],
                        'title' => $property['title']
                    ];
                }
                $settingsService->setCustomProps($profileId, $catalogId, $catalogCustomProps);
            }

            $dbConnection->commitTransaction();
        } catch (\Throwable $e) {
            $dbConnection->rollbackTransaction();
        }
    }

    public function deleteAction()
    {
        $settingsService = SettingsService::getInstance(
            [],
            null
        );

        $requestData = $this->getRequestData();
        $props = $requestData['properties'];
        $profileId = $requestData['profileId'];

        $dbConnection = Application::getInstance()->getConnection();

        try {
            $dbConnection->startTransaction();

            foreach ($props as $catalogId => $propsArray) {
                $catalogCustomProps = [];
                foreach ($propsArray as $property) {
                    $catalogCustomProps[] = [
                        'code' => $property['code'],
                        'title' => $property['title']
                    ];
                }
                $settingsService->deleteCustomProps($profileId, $catalogId, $catalogCustomProps);
//            $filePath = sprintf(
//                '%s/%s_profileId_%s_catalogId_%s.txt',
//                $_SERVER['DOCUMENT_ROOT'] . '/local',
//                'icml_property_retailcrm',
//                $profileId,
//                $catalogId
//            );
//            $fileContent = file_get_contents($filePath);
//
//            foreach ($propsArray as $property) {
//                $propStringToDelete = PHP_EOL . $property['code'] . ' = ' . $property['title'];
//                $fileContent = str_replace($propStringToDelete, '', $fileContent);
//            }
//            file_put_contents($filePath, $fileContent);

            }
        } catch (\Throwable $e) {
            $dbConnection->rollbackTransaction();
            // Добавить возврат ответа с ошибкой
        }

//        foreach ($props as $catalogId => $propsArray) {
//            $filePath = sprintf(
//                '%s/%s_profileId_%s_catalogId_%s.txt',
//                $_SERVER['DOCUMENT_ROOT'] . '/local',
//                'icml_property_retailcrm',
//                $profileId,
//                $catalogId
//            );
//            $fileContent = file_get_contents($filePath);
//
//            foreach ($propsArray as $property) {
//                $propStringToDelete = PHP_EOL . $property['code'] . ' = ' . $property['title'];
//                $fileContent = str_replace($propStringToDelete, '', $fileContent);
//            }
//            file_put_contents($filePath, $fileContent);

        }
}