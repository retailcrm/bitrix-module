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
        $requestData = $this->getRequestData();
        $props = $requestData['properties'];
        $profileId = $requestData['profileId'];

        $settingsService = SettingsService::getInstance(
            [],
            null,
            $profileId
        );

        $idCategories = array_keys($props);

        if ($idCategories !== []) {
            $settingsService->setProfileCatalogs($idCategories);//сразу заменяет и create и update
        }

        foreach ($props as $catalogId => $propsArray) {
            $catalogCustomProps = [];

            foreach ($propsArray as $property) {
                $catalogCustomProps[] = [
                    'code' => $property['code'],
                    'title' => $property['title']
                ];
            }

            $settingsService
                ->setCatalogCustomPropsOptionName($catalogId)
                ->saveCustomProps($catalogCustomProps)
            ;
        }
    }

    public function deleteAction()
    {
        $requestData = $this->getRequestData();
        $props = $requestData['properties'];
        $profileId = $requestData['profileId'];

        $settingsService = SettingsService::getInstance(
            [],
            null,
            $profileId
        );

        foreach ($props as $catalogId => $propsArray) {
            $catalogCustomProps = [];

            foreach ($propsArray as $property) {
                $catalogCustomProps[] = [
                    'code' => $property['code'],
                    'title' => $property['title']
                ];
            }

            $settingsService
                ->setCatalogCustomPropsOptionName($catalogId)
                ->removeCustomProps($catalogCustomProps, $catalogId)
            ;
        }
    }
}
