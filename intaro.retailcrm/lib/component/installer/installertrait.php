<?php

namespace Intaro\RetailCrm\Component\Installer;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\EventManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use CUserTypeEntity;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Handlers\EventsHandlers;
use Intaro\RetailCrm\Model\Bitrix\Agreement;
use Intaro\RetailCrm\Repository\AgreementRepository;
use Intaro\RetailCrm\Repository\ToModuleRepository;
use RCrmActions;

IncludeModuleLangFile(__FILE__);

trait InstallerTrait
{
    /**
     * Создание событий для программы лояльности
     */
    public function addEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach (Constants::LP_EVENTS as $event) {
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
                    'RetailCrm\ApiClient::addLPEvents',
                    $exception->getMessage()
                );
            }
        }
    }

    /**
     * Перемещение модульных шаблонов в CMS
     */
    public function CopyFiles(): void
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . Constants::MODULE_ID . '/install';

        CopyDirFiles(
            $pathFrom . '/export',
            $_SERVER['DOCUMENT_ROOT'],
            true,
            true,
            false
        );

        $templateNames = [
            'default_loyalty' => [
                0 => [
                    'name' => 'sale.order.ajax',
                    'templateDirectory' => '.default'
                ],
                1 => [
                    'name' => 'sale.basket.basket',
                    'templateDirectory' => '.default'
                ],
                2 => [
                    'name' => 'main.register',
                    'templateDirectory' => '.default'
                ],
            ],

            'default_subscribe' => [
                0 => [
                    'name' => 'sale.personal.section',
                    'templateDirectory' => '.default'
                ],
                1 => [
                    'name' => 'main.register',
                    'templateDirectory' => '.default_subscribe'
                ]
            ]
        ];

        foreach ($templateNames as $directory => $templates) {
            foreach ($templates as $template) {
                $this->copy($directory, $template);
            }
        }
    }

    /**
     * Добавление полей пользователя для ПЛ и подписки
     */
    public function addUserFields(): void
    {
        $this->addCustomUserFields(
            [
                [
                    'name'  => 'UF_CARD_NUM_INTARO',
                    'title' => GetMessage('UF_CARD_NUMBER_INTARO_TITLE'),
                ],
            ],
            'string'
        );

        $this->addCustomUserFields(
            [
                [
                    'name'  => 'UF_LP_ID_INTARO',
                    'title' => GetMessage('UF_LP_ID_INTARO_TITLE'),
                ],
            ],
            'string',
            ['EDIT_IN_LIST' => 'N']
        );

        $this->addCustomUserFields(
            [
                [
                    'name'  => 'UF_REG_IN_PL_INTARO',
                    'title' => GetMessage('UF_REG_IN_PL_INTARO_TITLE'),
                ],
                [
                    'name'  => 'UF_AGREE_PL_INTARO',
                    'title' => GetMessage('UF_AGREE_PL_INTARO_TITLE'),
                ],
                [
                    'name'  => 'UF_PD_PROC_PL_INTARO',
                    'title' => GetMessage('UF_PD_PROC_PL_INTARO_TITLE'),
                ],
                [
                    'name'  => 'UF_EXT_REG_PL_INTARO',
                    'title' => GetMessage('UF_EXT_REG_PL_INTARO_TITLE'),
                ],
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
    public function addCustomUserFields($fields, string $filedType = 'boolean', array $customProps  = []): void
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

    /**
     * Добавление соглашений для формы регистрации
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addAgreement(): void
    {
        $isAgreementLoyaltyProgram = AgreementRepository::getFirstByWhere(
            ['ID'],
            [
                ['CODE', '=', Constants::AGREEMENT_LOYALTY_PROGRAM_CODE]
            ]
        );

        if (!isset($isAgreementLoyaltyProgram['ID'])) {
            $agreementLoyaltyProgram = new Agreement();
            $agreementLoyaltyProgram->setCode(Constants::AGREEMENT_LOYALTY_PROGRAM_CODE);
            $agreementLoyaltyProgram->setDateInsert(new DateTime());
            $agreementLoyaltyProgram->setActive('Y');
            $agreementLoyaltyProgram->setName(GetMessage('AGREEMENT_LOYALTY_PROGRAM_TITLE'));
            $agreementLoyaltyProgram->setType('C');
            $agreementLoyaltyProgram->setAgreementText(GetMessage('AGREEMENT_LOYALTY_PROGRAM_TEXT'));
            $agreementLoyaltyProgram->save();
        }

        $isAgreementPersonalProgram = AgreementRepository::getFirstByWhere(
            ['ID'],
            [
                ['CODE', '=', Constants::AGREEMENT_PERSONAL_DATA_CODE]
            ]
        );

        if (!isset($isAgreementPersonalProgram['ID'])) {
            $agreementPersonalData = new Agreement();
            $agreementPersonalData->setCode(Constants::AGREEMENT_PERSONAL_DATA_CODE);
            $agreementPersonalData->setDateInsert(new DateTime());
            $agreementPersonalData->setActive('Y');
            $agreementPersonalData->setName(GetMessage('AGREEMENT_PERSONAL_DATA_TITLE'));
            $agreementPersonalData->setType('C');
            $agreementPersonalData->setAgreementText(GetMessage('AGREEMENT_PERSONAL_DATA_TEXT'));
            $agreementPersonalData->save();
        }
    }

    /**
     * delete loyalty program events handlers
     */
    private function deleteLPEvents(): void
    {
        $eventManager = EventManager::getInstance();

        foreach (Constants::LP_EVENTS as $event){
            $eventManager->unRegisterEventHandler(
                $event['FROM_MODULE'],
                $event['EVENT_NAME'],
                $this->MODULE_ID,
                EventsHandlers::class,
                $event['EVENT_NAME'].'Handler'
            );
        }
    }

    private function copy($directory, $template): void
    {
        $templatePath = $_SERVER['DOCUMENT_ROOT']
            . '/local/templates/.default/components/bitrix/' . $template['name'] . '/'. $directory
        ;

        if (!file_exists($templatePath)) {
            $pathFrom = $_SERVER['DOCUMENT_ROOT']
                . '/bitrix/modules/intaro.retailcrm/install/export/local/components/intaro/'
                . $template['name']
                . '/templates/' . $template['templateDirectory']
            ;

            CopyDirFiles(
                $pathFrom,
                $templatePath,
                true,
                true,
                false
            );
        }
    }
}