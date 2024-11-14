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

        foreach ($props as $catalogId => $propsArray) {
            $catalogCustomProps = [];
            foreach ($propsArray as $property) {
                $catalogCustomProps[] = [
                    'code' => $property['code'],
                    'title' => $property['title']
                ];
            }
            $settingsService->saveCustomProps($profileId, $catalogId, $catalogCustomProps);
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

        foreach ($props as $catalogId => $propsArray) {
            $catalogCustomProps = [];
            foreach ($propsArray as $property) {
                $catalogCustomProps[] = [
                    'code' => $property['code'],
                    'title' => $property['title']
                ];
            }
            $settingsService->removeCustomProps($profileId, $catalogId, $catalogCustomProps);
        }
    }
}