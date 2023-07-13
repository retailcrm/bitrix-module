<?php

namespace Intaro\RetailCrm\Component\Installer;

use Intaro\RetailCrm\Component\Constants;
use Bitrix\Main\EventManager;
use Intaro\RetailCrm\Component\Handlers\EventsHandlers;
use Intaro\RetailCrm\Repository\ToModuleRepository;
use Bitrix\Main\SystemException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\ArgumentException;
use RCrmActions;
use CUserTypeEntity;

IncludeModuleLangFile(__FILE__);

trait SubscriberInstallerTrait
{
    public function addSubscriberEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach (Constants::SUBSCRIBE_EVENTS as $event) {
            try {
                $events = ToModuleRepository::getCollectionByWhere(
                    ['ID'],
                    [
                        ['from_module_id', '=', $event['FROM_MODULE']],
                        ['to_module_id', '=', Constants::MODULE_ID],
                        ['to_method', '=', $event['EVENT_NAME'] . 'Handler'],
                        ['to_class', '=', EventsHandlers::class],
                    ]
                );

                if ($events !== null && count($events) === 0) {
                    $eventManager->registerEventHandler(
                        $event['FROM_MODULE'],
                        $event['EVENT_NAME'],
                        Constants::MODULE_ID,
                        EventsHandlers::class,
                        $event['EVENT_NAME'] . 'Handler'
                    );
                }
            } catch (ObjectPropertyException | ArgumentException | SystemException $exception) {
                RCrmActions::eventLog(
                    'intaro.retailcrm/install/index.php',
                    'RetailCrm\SubscriberInstallerTrait::addSubscribeEvents',
                    $exception->getMessage()
                );
            }
        }
    }

    public function CopyFilesSubscribe(): void
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . Constants::MODULE_ID . '/install';

        CopyDirFiles(
            $pathFrom . '/export',
            $_SERVER['DOCUMENT_ROOT'],
            true,
            true,
            false
        );

        $lpTemplateNames = [
            'sale.personal.section'
        ];

        foreach ($lpTemplateNames as $lpTemplateName){
            $lpTemplatePath = $_SERVER['DOCUMENT_ROOT']
                . '/local/templates/.default/components/bitrix/' . $lpTemplateName . '/default_subscribe16';

            if (!file_exists($lpTemplatePath)) {
                $pathFrom = $_SERVER['DOCUMENT_ROOT']
                    . '/bitrix/modules/intaro.retailcrm/install/export/local/components/intaro/'
                    . $lpTemplateName;

                CopyDirFiles(
                    $pathFrom,
                    $lpTemplatePath,
                    true,
                    true,
                    false
                );
            }
        }
    }

    public function addSubscribeUserFields(): void
    {
        $this->addCustomSubscribeUserFields(
            [
                [
                    'name' => 'UF_SUBSCRIBE_USER_EMAIL',
                    'title' => GetMessage('UF_SUBSCRIBE_USER_EMAIL_TITLE')
                ]
            ]
        );
    }

    /**
     * @param        $fields
     * @param string $filedType
     * @param array  $customProps
     */
    public function addCustomSubscribeUserFields($fields, string $filedType = 'boolean', array $customProps  = []): void
    {
        foreach ($fields as $filed) {
            $arProps     = [
                'ENTITY_ID'       => 'USER',
                'FIELD_NAME'      => $filed['name'],
                'USER_TYPE_ID'    => $filedType,
                'MULTIPLE'        => 'N',
                'MANDATORY'       => 'N',
                'EDIT_FORM_LABEL' => ['ru' => $filed['title']],

            ];
            $props = array_merge($arProps, $customProps);
            $obUserField = new CUserTypeEntity();
            $dbRes       = CUserTypeEntity::GetList([], ['FIELD_NAME' => $filed['name']])->fetch();

            if (!$dbRes['ID']) {
                $obUserField->Add($props);
            }
        }
    }

    private function deleteSubscribeEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach (Constants::SUBSCRIBE_EVENTS as $event){
            $eventManager->unRegisterEventHandler(
                $event['FROM_MODULE'],
                $event['EVENT_NAME'],
                $this->MODULE_ID,
                EventsHandlers::class,
                $event['EVENT_NAME'].'Handler'
            );
        }
    }
}