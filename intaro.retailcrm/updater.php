<?php

use Bitrix\Highloadblock as HL;
use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserConsent\Internals\AgreementTable;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PersonTypeTable;

/**
 * Class ToModuleTable
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime mandatory default 'CURRENT_TIMESTAMP'
 * <li> SORT int optional default 100
 * <li> FROM_MODULE_ID string(50) mandatory
 * <li> MESSAGE_ID string(255) mandatory
 * <li> TO_MODULE_ID string(50) mandatory
 * <li> TO_PATH string(255) optional
 * <li> TO_CLASS string(255) optional
 * <li> TO_METHOD string(255) optional
 * <li> TO_METHOD_ARG string(255) optional
 * <li> VERSION int optional
 * <li> UNIQUE_ID string(32) mandatory
 * </ul>
 *
 * @package Bitrix\Module
 **/
class ToModuleTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_module_to_module';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     * @throws \Bitrix\Main\SystemException
     */
    public static function getMap(): array
    {
        return [
            new IntegerField(
                'id',
                ['primary' => true, 'autocomplete' => true]),
            new DatetimeField('TIMESTAMP_X', ['required' => true]),
            new IntegerField('sort'),
            new StringField(
                'from_module_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateFromModuleId']]
            ),
            new StringField(
                'message_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateMessageId']]
            ),
            new StringField(
                'to_module_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateToModuleId']]
            ),
            new StringField(
                'to_path',
                ['validation' => [__CLASS__, 'validateToPath']]
            ),
            new StringField(
                'to_class',
                ['validation' => [__CLASS__, 'validateToClass']]
            ),
            new StringField(
                'to_method',
                ['validation' => [__CLASS__, 'validateToMethod']]
            ),
            new StringField(
                'to_method_arg',
                ['validation' => [__CLASS__, 'validateToMethodArg']]
            ),
            new IntegerField('version'),
            new StringField(
                'unique_id',
                ['required' => true, 'validation' => [__CLASS__, 'validateUniqueId']]
            ),
        ];
    }

    /**
     * Returns validators for FROM_MODULE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateFromModuleId(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 50),
        ];
    }

    /**
     * Returns validators for MESSAGE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateMessageId(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }

    /**
     * Returns validators for TO_MODULE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToModuleId(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 50),
        ];
    }

    /**
     * Returns validators for TO_PATH field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToPath(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }

    /**
     * Returns validators for TO_CLASS field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToClass(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }

    /**
     * Returns validators for TO_METHOD field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToMethod(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }

    /**
     * Returns validators for TO_METHOD_ARG field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateToMethodArg(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 255),
        ];
    }

    /**
     * Returns validators for UNIQUE_ID field.
     *
     * @return array
     * @throws \Bitrix\Main\ArgumentTypeException
     */
    public static function validateUniqueId(): array
    {
        return [
            new Main\Entity\Validator\Length(null, 32),
        ];
    }
}

class UpdaterRetailExportTable extends Main\Entity\DataManager
{
    /**
     * Returns DB table name for entity.
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'b_catalog_export';
    }

    /**
     * Returns entity map definition.
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            'ID'              => [
                'data_type'    => 'integer',
                'primary'      => true,
                'autocomplete' => true,
                'title'        => Loc::getMessage('EXPORT_ENTITY_ID_FIELD'),
            ],
            'FILE_NAME'       => [
                'data_type'  => 'string',
                'required'   => true,
                'validation' => [__CLASS__, 'validateFileName'],
                'title'      => Loc::getMessage('EXPORT_ENTITY_FILE_NAME_FIELD'),
            ],
            'NAME'            => [
                'data_type'  => 'string',
                'required'   => true,
                'validation' => [__CLASS__, 'validateName'],
                'title'      => Loc::getMessage('EXPORT_ENTITY_NAME_FIELD'),
            ],
            'DEFAULT_PROFILE' => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_DEFAULT_PROFILE_FIELD'),
            ],
            'IN_MENU'         => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IN_MENU_FIELD'),
            ],
            'IN_AGENT'        => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IN_AGENT_FIELD'),
            ],
            'IN_CRON'         => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IN_CRON_FIELD'),
            ],
            'SETUP_VARS'      => [
                'data_type' => 'text',
                'title'     => Loc::getMessage('EXPORT_ENTITY_SETUP_VARS_FIELD'),
            ],
            'LAST_USE'        => [
                'data_type' => 'datetime',
                'title'     => Loc::getMessage('EXPORT_ENTITY_LAST_USE_FIELD'),
            ],
            'IS_EXPORT'       => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_IS_EXPORT_FIELD'),
            ],
            'NEED_EDIT'       => [
                'data_type' => 'boolean',
                'values'    => ['N', 'Y'],
                'title'     => Loc::getMessage('EXPORT_ENTITY_NEED_EDIT_FIELD'),
            ],
            'TIMESTAMP_X'     => [
                'data_type' => 'datetime',
                'title'     => Loc::getMessage('EXPORT_ENTITY_TIMESTAMP_X_FIELD'),
            ],
            'MODIFIED_BY'     => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('EXPORT_ENTITY_MODIFIED_BY_FIELD'),
            ],
            'DATE_CREATE'     => [
                'data_type' => 'datetime',
                'title'     => Loc::getMessage('EXPORT_ENTITY_DATE_CREATE_FIELD'),
            ],
            'CREATED_BY'      => [
                'data_type' => 'integer',
                'title'     => Loc::getMessage('EXPORT_ENTITY_CREATED_BY_FIELD'),
            ],
        ];
    }
}

class LoyaltyProgramUpdater
{
    /**
     * CamelCase в имени является требованием Bitrix. Изменить на lowerCamelCase нельзя
     */
    public function CopyFiles(): self
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.retailcrm/install';

        CopyDirFiles(
            $pathFrom . '/export',
            $_SERVER['DOCUMENT_ROOT'],
            true,
            true,
            false
        );

        $lpTemplateNames = [
            'sale.order.ajax',
            'sale.basket.basket',
            'main.register',
        ];

        foreach ($lpTemplateNames as $lpTemplateName) {
            $lpTemplatePath = $_SERVER['DOCUMENT_ROOT']
                . '/local/templates/.default/components/bitrix/' . $lpTemplateName . '/default_loyalty';

            if (!file_exists($lpTemplatePath)) {
                $pathFrom = $_SERVER['DOCUMENT_ROOT']
                    . '/bitrix/modules/intaro.retailcrm/install/export/local/components/intaro/'
                    . $lpTemplateName
                    . '/templates/.default';

                CopyDirFiles(
                    $pathFrom,
                    $lpTemplatePath,
                    true,
                    true,
                    false
                );
            }
        }

        return $this;
    }

    /**
     * @throws \Bitrix\Main\SystemException
     * @throws \Bitrix\Main\ArgumentException
     */
    public function tryReplaceExportVars(): self
    {
        /** @var EntityObject $exportSystem */
        $exportSystem = UpdaterRetailExportTable::query()
            ->addSelect('*')
            ->where('FILE_NAME', 'retailcrm')
            ->fetchObject();

        if ($exportSystem instanceof EntityObject) {
            $replaceableVars = [
                ['search' => 'IBLOCK_EXPORT', 'replace' => 'iblockExport'],
                ['search' => 'IBLOCK_PROPERTY_SKU', 'replace' => 'iblockPropertySku'],
                ['search' => 'IBLOCK_PROPERTY_UNIT_SKU', 'replace' => 'iblockPropertyUnitSku'],
                ['search' => 'IBLOCK_PROPERTY_PRODUCT', 'replace' => 'iblockPropertyProduct'],
                ['search' => 'IBLOCK_PROPERTY_UNIT_PRODUCT', 'replace' => 'iblockPropertyUnitProduct'],
                ['search' => 'MAX_OFFERS_VALUE', 'replace' => 'maxOffersValue'],
            ];
            $setupVars = $exportSystem->get('SETUP_VARS');
            $newSetupVars = str_replace(
                array_column($replaceableVars, 'search'),
                array_column($replaceableVars, 'replace'),
                $setupVars
            );

            $exportSystem->set('SETUP_VARS', $newSetupVars);
            $exportSystem->save();
        }

        return $this;
    }

    public function addLoyaltyProgramEvents(): self
    {
        $eventManager = EventManager::getInstance();
        $eventsList = [
            ['EVENT_NAME' => 'OnSaleOrderSaved', 'FROM_MODULE' => 'sale'],
            ['EVENT_NAME' => 'OnSaleComponentOrderResultPrepared', 'FROM_MODULE' => 'sale'],
            ['EVENT_NAME' => 'OnAfterUserRegister', 'FROM_MODULE' => 'main'],
        ];

        foreach ($eventsList as $event) {
            $events = ToModuleTable::query()
                ->setSelect(['ID'])
                ->where([
                    ['from_module_id', '=', $event['FROM_MODULE']],
                    ['to_module_id', '=', 'intaro.retailcrm'],
                    ['to_method', '=', $event['EVENT_NAME'] . 'Handler'],
                    ['to_class', '=', 'Intaro\RetailCrm\Component\Handlers\EventsHandlers'],
                ])
                ->fetchCollection();

            if (null === $events || 0 === count($events)) {
                $eventManager->registerEventHandler(
                    $event['FROM_MODULE'],
                    $event['EVENT_NAME'],
                    'intaro.retailcrm',
                    'Intaro\RetailCrm\Component\Handlers\EventsHandlers',
                    $event['EVENT_NAME'] . 'Handler'
                );
            }
        }

        return $this;
    }

    public function getLoyaltyHlFields(string $ufObject): array
    {
        return [
            'UF_ORDER_ID' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_ORDER_ID',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID заказа',
                    'en' => 'Order ID',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'ID заказа',
                    'en' => 'Order ID',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'ID заказа',
                    'en' => 'Order ID',
                ],
            ],
            'UF_ITEM_ID' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_ITEM_ID',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID товара',
                    'en' => 'Product ID',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'ID товара',
                    'en' => 'Product ID',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'ID товара',
                    'en' => 'Product ID',
                ],
            ],
            'UF_ITEM_POS_ID' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_ITEM_POS_ID',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID позиции в корзине',
                    'en' => 'Basket position ID',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'ID позиции в корзине',
                    'en' => 'Basket position ID',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'ID позиции в корзине',
                    'en' => 'Basket position ID',
                ],
            ],
            'UF_NAME' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_NAME',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Название товара',
                    'en' => 'Product name',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Название товара',
                    'en' => 'Product name',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Название товара',
                    'en' => 'Product name',
                ],
            ],
            'UF_DEF_DISCOUNT' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_DEF_DISCOUNT',
                'USER_TYPE_ID' => 'double',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Скидка магазина',
                    'en' => 'Shop discount',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Скидка магазина',
                    'en' => 'Shop discount',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Скидка магазина',
                    'en' => 'Shop discount',
                ],
            ],
            'UF_CHECK_ID' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_CHECK_ID',
                'USER_TYPE_ID' => 'string',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'ID проверочного кода',
                    'en' => 'Verification code ID',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'ID проверочного кода',
                    'en' => 'Verification code ID',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'ID проверочного кода',
                    'en' => 'Verification code ID',
                ],
            ],
            'UF_IS_DEBITED' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_IS_DEBITED',
                'USER_TYPE_ID' => 'boolean',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Списаны ли бонусы',
                    'en' => 'Is debited',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Списаны ли бонусы',
                    'en' => 'Is debited',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Списаны ли бонусы',
                    'en' => 'Is debited',
                ],
            ],
            'UF_QUANTITY' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_QUANTITY',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'Y',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Количество в корзине',
                    'en' => 'Quantity in the basket',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Количество в корзине',
                    'en' => 'Quantity in the basket',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Количество в корзине',
                    'en' => 'Quantity in the basket',
                ],
            ],
            'UF_BONUS_COUNT' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_BONUS_COUNT',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в позиции',
                    'en' => 'Bonuses for writing off in position',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в позиции',
                    'en' => 'Bonuses for writing off in position',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в позиции',
                    'en' => 'Bonuses for writing off in position',
                ],
            ],
            'UF_BONUS_COUNT_TOTAL' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_BONUS_COUNT_TOTAL',
                'USER_TYPE_ID' => 'integer',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в заказе',
                    'en' => 'Bonuses for writing off in order',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в заказе',
                    'en' => 'Bonuses for writing off in order',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в заказе',
                    'en' => 'Bonuses for writing off in order',
                ],
            ],
        ];
    }

    /**
     * @throws \Bitrix\Main\SystemException
     * @throws \Exception
     */
    public function createLoyaltyHlBlock(): self
    {
        $result = HL\HighloadBlockTable::add([
            'NAME' => 'LoyaltyProgramRetailCRM',
            'TABLE_NAME' => 'loyalty_program'
        ]);
        $arLangs = [
            'ru' => 'Программа лояльности',
            'en' => 'Loyalty Program'
        ];

        if ($result->isSuccess()) {
            $hlId = $result->getId();

            foreach ($arLangs as $langKey => $langVal) {
                HL\HighloadBlockLangTable::add([
                    'ID' => $hlId,
                    'LID' => $langKey,
                    'NAME' => $langVal,
                ]);
            }
        } else {
            foreach ($result->getErrorMessages() as $message) {
                AddMessage2Log($message, 'intaro.retailcrm');
            }
        }

        if (!isset($hlId)) {
            return $this;
        }

        $ufObject = 'HLBLOCK_' . $hlId;
        $arCartFields = $this->getLoyaltyHlFields($ufObject);

        foreach ($arCartFields as $arCartField) {
            $obUserField = new CUserTypeEntity();
            $obUserField->Add($arCartField);
        }

        return $this;
    }

    /**
     * add LP Order Props
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addCustomersLoyaltyFields(): self
    {
        $persons = PersonTypeTable::query()
            ->setSelect(['ID'])
            ->where([])
            ->fetchCollection();

        foreach ($persons as $person) {
            $personId = $person->getID();
            $groupId = $this->getGroupIdByPersonId($personId);

            if (isset($groupId)) {
                $this->addBonusFieldForLp($personId, $groupId);
            }
        }

        return $this;
    }

    public function getGroupIdByPersonId($personId): ?int
    {
        $lpOrderGroupName = [
                'ru' => 'Программа лояльности',
                'en' => 'Loyalty Program'
            ][Context::getCurrent()->getLanguage()] ?? 'Программа лояльности';

        try {
            $lpGroup = OrderPropsGroupTable::query()
                ->setSelect(['ID'])
                ->where(
                    [
                        ['PERSON_TYPE_ID', '=', $personId],
                        ['NAME', '=', $lpOrderGroupName],
                    ]
                )
                ->fetch();

            if (is_array($lpGroup)) {
                return $lpGroup['ID'];
            }

            if ($lpGroup === false) {
                return OrderPropsGroupTable::add([
                    'PERSON_TYPE_ID' => $personId,
                    'NAME' => $lpOrderGroupName,
                ])->getId();
            }
        } catch (Exception $exception) {
            AddMessage2Log($exception->getMessage());

            return null;
        }

        return null;
    }

    /**
     * @param int|string $personId
     * @param int|string $groupId
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addBonusFieldForLp($personId, $groupId): void
    {
        $bonusProp = OrderPropsTable::query()
            ->setSelect(['ID'])
            ->where([
                ['PERSON_TYPE_ID', '=', $personId],
                ['PROPS_GROUP_ID', '=', $groupId],
            ])
            ->fetchObject();

        if ($bonusProp === null) {
            $lang = Context::getCurrent()->getLanguage();
            $lpBonusInfo = [
                'ru' => 'Бонусов начислено',
                'en' => 'Bonus info'
            ][$lang];
            $lpDiscountInfo = [
                'ru' => 'Персональная скидка',
                'en' => 'Personal discount'
            ][$lang];

            $fields = [
                [
                    'REQUIRED' => 'N',
                    'NAME' => $lpBonusInfo,
                    'TYPE' => 'TEXTAREA',
                    'CODE' => 'LP_BONUS_INFO',
                    'USER_PROPS' => 'Y',
                    'IS_LOCATION' => 'N',
                    'IS_LOCATION4TAX' => 'N',
                    'IS_EMAIL' => 'N',
                    'IS_PROFILE_NAME' => 'N',
                    'IS_PAYER' => 'N',
                    'IS_FILTERED' => 'Y',
                    'PERSON_TYPE_ID' => $personId,
                    'PROPS_GROUP_ID' => $groupId,
                    'DEFAULT_VALUE' => '',
                    'DESCRIPTION' => $lpBonusInfo,
                    'UTIL' => 'Y',
                ],
                [
                    'REQUIRED' => 'N',
                    'NAME' => $lpDiscountInfo,
                    'TYPE' => 'TEXTAREA',
                    'CODE' => 'LP_DISCOUNT_INFO',
                    'USER_PROPS' => 'Y',
                    'IS_LOCATION' => 'N',
                    'IS_LOCATION4TAX' => 'N',
                    'IS_EMAIL' => 'N',
                    'IS_PROFILE_NAME' => 'N',
                    'IS_PAYER' => 'N',
                    'IS_FILTERED' => 'Y',
                    'PERSON_TYPE_ID' => $personId,
                    'PROPS_GROUP_ID' => $groupId,
                    'DEFAULT_VALUE' => '',
                    'DESCRIPTION' => $lpDiscountInfo,
                    'UTIL' => 'Y',
                ],
            ];

            foreach ($fields as $field) {
                CSaleOrderProps::Add($field);
            }
        }
    }

    /**
     * Add USER fields for LP
     */
    public function addLpUserFields(): self
    {
        $this->addCustomUserFields(
            [
                [
                    'name' => 'UF_CARD_NUM_INTARO',
                    'title' => 'Номер карты программы лояльности',
                ],
            ],
            'string'
        );

        $this->addCustomUserFields(
            [
                [
                    'name' => 'UF_LP_ID_INTARO',
                    'title' => 'Номер аккаунта в программе лояльности',
                ],
            ],
            'string',
            ['EDIT_IN_LIST' => 'N']
        );

        $this->addCustomUserFields(
            [
                [
                    'name' => 'UF_REG_IN_PL_INTARO',
                    'title' => 'Зарегистрироваться в программе лояльности',
                ],
                [
                    'name' => 'UF_AGREE_PL_INTARO',
                    'title' => 'Я согласен с условиями программы лояльности',
                ],
                [
                    'name' => 'UF_PD_PROC_PL_INTARO',
                    'title' => 'Согласие на обработку персональных данных',
                ],
                [
                    'name' => 'UF_EXT_REG_PL_INTARO',
                    'title' => 'Активность в программе лояльности',
                ],
            ]
        );

        return $this;
    }

    /**
     * Добавление соглашений для формы регистрации
     *
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function addAgreement(): self
    {
        $isAgreementLoyaltyProgram = AgreementTable::query()
            ->setSelect(['ID'])
            ->where([
                ['CODE', '=', 'AGREEMENT_LOYALTY_PROGRAM_CODE']
            ])
            ->fetch();

        if (!isset($isAgreementLoyaltyProgram['ID'])) {
            /** @var EntityObject|null $agreementLoyaltyProgram */
            $agreementLoyaltyProgram = AgreementTable::createObject();
            $agreementLoyaltyProgram->setCode('AGREEMENT_LOYALTY_PROGRAM_CODE');
            $agreementLoyaltyProgram->setDateInsert(new DateTime());
            $agreementLoyaltyProgram->setActive('Y');
            $agreementLoyaltyProgram->setName(GetMessage('AGREEMENT_LOYALTY_PROGRAM_TITLE'));
            $agreementLoyaltyProgram->setType('C');
            $agreementLoyaltyProgram->setAgreementText(GetMessage('AGREEMENT_LOYALTY_PROGRAM_TEXT'));
            $agreementLoyaltyProgram->save();
        }

        $isAgreementPersonalProgram = AgreementTable::query()
            ->setSelect(['ID'])
            ->where([
                ['CODE', '=', 'AGREEMENT_PERSONAL_DATA_CODE']
            ])
            ->fetch();

        if (!isset($isAgreementPersonalProgram['ID'])) {
            /** @var EntityObject|null $agreementPersonalData */
            $agreementPersonalData = AgreementTable::createObject();
            $agreementPersonalData->setCode('AGREEMENT_PERSONAL_DATA_CODE');
            $agreementPersonalData->setDateInsert(new DateTime());
            $agreementPersonalData->setActive('Y');
            $agreementPersonalData->setName(GetMessage('AGREEMENT_PERSONAL_DATA_TITLE'));
            $agreementPersonalData->setType('C');
            $agreementPersonalData->setAgreementText(GetMessage('AGREEMENT_PERSONAL_DATA_TEXT'));
            $agreementPersonalData->save();
        }

        return $this;
    }

    /**
     * @param array  $fields
     * @param string $filedType
     * @param array  $customProps
     */
    public function addCustomUserFields($fields, string $filedType = 'boolean', array $customProps  = []): void
    {
        foreach ($fields as $field) {
            $arProps = [
                'ENTITY_ID' => 'USER',
                'FIELD_NAME' => $field['name'],
                'USER_TYPE_ID' => $filedType,
                'MULTIPLE' => 'N',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => ['ru' => $field['title']],

            ];
            $props = array_merge($arProps, $customProps);
            $obUserField = new CUserTypeEntity();
            $dbRes = CUserTypeEntity::GetList([], ['FIELD_NAME' => $field['name']])->fetch();

            if (!$dbRes['ID']) {
                $obUserField->Add($props);
            }
        }
    }


    public function updateBonusInfoFieldForLp(): self
    {
        $bonusProps = CSaleOrderProps::GetList([], ['CODE' => 'LP_BONUS_INFO']);
        $lang = Context::getCurrent()->getLanguage();
        $lpBonusInfo = [
            'ru' => 'Бонусов списано',
            'en' => 'Bonuses charged'
        ][$lang];
        $updateInfo = [
            'NAME' => $lpBonusInfo,
            'DESCRIPTION' => $lpBonusInfo,
        ];

        while ($bonusProp = $bonusProps->Fetch())
        {
            CSaleOrderProps::Update($bonusProp['ID'], $updateInfo);
        }

        return $this;
    }

    /**
     * Обновление типов полей с бонусами и перенос информации
     *
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public function updateBonusFieldsTypeInHl(): self
    {
        $hlblock = HL\HighloadBlockTable::query()
            ->addSelect('*')
            ->addFilter('NAME', 'LoyaltyProgramRetailCRM')
            ->exec()
            ->fetch();

        if (isset($hlblock['ID'])) {
            $ufObject = 'HLBLOCK_' . $hlblock['ID'];
            $bonusCountField = CUserTypeEntity::GetList([], [
                "ENTITY_ID" => $ufObject,
                "FIELD_NAME" => 'UF_BONUS_COUNT',
            ])->fetch();
            $bonusTotalCountField = CUserTypeEntity::GetList([], [
                "ENTITY_ID" => $ufObject,
                "FIELD_NAME" => 'UF_BONUS_COUNT_TOTAL',
            ])->fetch();

            if ('integer' === $bonusCountField['USER_TYPE_ID'] && 'integer' === $bonusTotalCountField['USER_TYPE_ID']) {
                $hlblockEntity = HL\HighloadBlockTable::compileEntity($hlblock);
                $manager = $hlblockEntity->getDataClass();

                $bonusHlblockData = $manager::query()
                    ->setSelect(['ID', 'UF_BONUS_COUNT', 'UF_BONUS_COUNT_TOTAL'])
                    ->setFilter(['!=UF_BONUS_COUNT' => 'NULL', 'LOGIC' => 'OR', '!=UF_BONUS_COUNT_TOTAL' => 'NULL'])
                    ->fetchAll();

                $obUserField = new CUserTypeEntity();
                $obUserField->Delete($bonusCountField['ID']);
                $obUserField->Delete($bonusTotalCountField['ID']);

                $newBonusFields = $this->getNewBonusHlFields($ufObject);

                foreach ($newBonusFields as $newBonusField) {
                    $obUserField->Add($newBonusField);
                }

                foreach ($bonusHlblockData as $field) {
                    $manager::update($field['ID'], ['fields' => [
                        'UF_BONUS_COUNT' => $field['UF_BONUS_COUNT'],
                        'UF_BONUS_COUNT_TOTAL' => $field['UF_BONUS_COUNT_TOTAL'],
                    ],]);
                }
            }
        }

        return $this;
    }

    /**
     * Обновление поля скидки по товару
     *
     * @throws Main\ArgumentException
     * @throws Main\ObjectPropertyException
     * @throws Main\SystemException
     */
    public function updateDefDiscountFieldTypeInHl(): self
    {
        $hlblock = HL\HighloadBlockTable::query()
            ->addSelect('*')
            ->addFilter('NAME', 'LoyaltyProgramRetailCRM')
            ->exec()
            ->fetch();

        if (isset($hlblock['ID'])) {
            $ufObject = 'HLBLOCK_' . $hlblock['ID'];
            $defDiscountField = CUserTypeEntity::GetList([], [
                "ENTITY_ID" => $ufObject,
                "FIELD_NAME" => 'UF_DEF_DISCOUNT',
            ])->fetch();

            if (false !== $defDiscountField) {
                $obUserField = new CUserTypeEntity();
                $obUserField->Update($defDiscountField['ID'], [
                    'SETTINGS' => [
                        'PRECISION' => 2,
                    ],
                ]);
            }
        }

        return $this;
    }

    public function getNewBonusHlFields(string $ufObject): array
    {
        return [
            'UF_BONUS_COUNT' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_BONUS_COUNT',
                'USER_TYPE_ID' => 'double',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в позиции',
                    'en' => 'Bonuses for writing off in position',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в позиции',
                    'en' => 'Bonuses for writing off in position',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в позиции',
                    'en' => 'Bonuses for writing off in position',
                ],
                'SETTINGS' => [
                    'PRECISION' => 2,
                ],
            ],
            'UF_BONUS_COUNT_TOTAL' => [
                'ENTITY_ID' => $ufObject,
                'FIELD_NAME' => 'UF_BONUS_COUNT_TOTAL',
                'USER_TYPE_ID' => 'double',
                'MANDATORY' => 'N',
                'EDIT_FORM_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в заказе',
                    'en' => 'Bonuses for writing off in order',
                ],
                'LIST_COLUMN_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в заказе',
                    'en' => 'Bonuses for writing off in order',
                ],
                'LIST_FILTER_LABEL' => [
                    'ru' => 'Количество списываемых бонусов в заказе',
                    'en' => 'Bonuses for writing off in order',
                ],
                'SETTINGS' => [
                    'PRECISION' => 2,
                ],
            ],
        ];
    }
}

class UpdateSubscribe
{
    public function CopyFiles(): self
    {
        $pathFrom = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/intaro.retailcrm/install';

        CopyDirFiles(
            $pathFrom . '/export',
            $_SERVER['DOCUMENT_ROOT'],
            true,
            true,
            false
        );

        $templateNames = [
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
                $templatePath = $_SERVER['DOCUMENT_ROOT']
                    . '/local/templates/.default/components/bitrix/' . $template['name'] . '/' . $directory;

                if (!file_exists($templatePath)) {
                    $pathFrom = $_SERVER['DOCUMENT_ROOT']
                        . '/bitrix/modules/intaro.retailcrm/install/export/local/components/intaro/'
                        . $template['name']
                        . '/templates/' . $template['templateDirectory'];

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

        return $this;
    }

    public function addEvent(): self
    {
        $eventManager = EventManager::getInstance();

        $eventManager->unRegisterEventHandler(
            'main',
            'OnAfterUserRegister',
            'intaro.retailcrm',
            'Intaro\RetailCrm\Component\Handlers\EventsHandlers',
            'OnAfterUserRegisterHandler'
        );

        RegisterModuleDependences('main', 'OnAfterUserRegister', 'intaro.retailcrm', 'RetailCrmEvent', 'OnAfterUserRegister');
        RegisterModuleDependences('main', 'OnAfterUserAdd', 'intaro.retailcrm', 'RetailCrmEvent', 'OnAfterUserAdd');

        return $this;
    }

    public function addCustomUserField(): self
    {
        $arProps     = [
            'ENTITY_ID'       => 'USER',
            'FIELD_NAME'      => 'UF_SUBSCRIBE_USER_EMAIL',
            'USER_TYPE_ID'    => 'boolean',
            'MULTIPLE'        => 'N',
            'MANDATORY'       => 'N',
            'EDIT_FORM_LABEL' => ['ru' => 'Подписка на события'],

        ];

        $props = array_merge($arProps, []);
        $obUserField = new CUserTypeEntity();
        $dbRes       = CUserTypeEntity::GetList([], ['FIELD_NAME' => 'UF_SUBSCRIBE_USER_EMAIL'])->fetch();

        if (!$dbRes['ID']) {
            $obUserField->Add($props);
        }

        return $this;
    }
}

/**
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 * @throws \Bitrix\Main\LoaderException
 */
function update()
{
    Loader::includeModule('sale');
    Loader::includeModule('highloadblock');

    (new LoyaltyProgramUpdater())
        ->updateBonusInfoFieldForLp()
        ->updateBonusFieldsTypeInHl()
        ->updateDefDiscountFieldTypeInHl();

    UnRegisterModuleDependences("main", "OnBeforeProlog", 'intaro.retailcrm', "RetailCrmPricePrchase", "add");
    UnRegisterModuleDependences("main", "OnBeforeProlog", 'intaro.retailcrm', "RetailCrmDc", "add");
    UnRegisterModuleDependences("main", "OnBeforeProlog", 'intaro.retailcrm', "RetailCrmCc", "add");

    (new UpdateSubscribe())
        ->CopyFiles()
        ->addEvent()
        ->addCustomUserField();
}

try {
    update();
} catch (Main\ObjectPropertyException | Main\ArgumentException | Main\SystemException $exception) {
    return;
}
