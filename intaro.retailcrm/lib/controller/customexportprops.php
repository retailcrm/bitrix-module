<?php

namespace Intaro\RetailCrm\Controller;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Intaro\RetailCrm\Component\Constants;
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
    public function configureActions(): array
    {
        return [
            'save' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
            'delete' => [
                'prefilters' => [
                    new Authentication(),
                    new HttpMethod([HttpMethod::METHOD_POST]),
                    new Csrf(),
                ],
            ],
        ];
    }

    private function getRequestData(): array
    {
        $data = $this->getRequest()->getInput();

        if (!is_string($data) || $data === '') {
            return [];
        }

        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function hasWriteAccess(): bool
    {
        global $APPLICATION, $USER;

        return $USER instanceof \CUser
            && (
                $USER->IsAdmin()
                || ($APPLICATION instanceof \CMain && $APPLICATION->GetGroupRight(Constants::MODULE_ID) === 'W')
            );
    }

    public function saveAction()
    {
        if (!$this->hasWriteAccess()) {
            $this->addError(new Error('Access denied'));

            return null;
        }

        $requestData = $this->getRequestData();
        $props = is_array($requestData['properties'] ?? null) ? $requestData['properties'] : [];
        $profileId = (int) ($requestData['profileId'] ?? 0);

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
        if (!$this->hasWriteAccess()) {
            $this->addError(new Error('Access denied'));

            return null;
        }

        $requestData = $this->getRequestData();
        $props = is_array($requestData['properties'] ?? null) ? $requestData['properties'] : [];
        $profileId = (int) ($requestData['profileId'] ?? 0);

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
