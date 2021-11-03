<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Intaro\RetailCrm\Component\Installer\LoyaltyInstallerTrait;
use Intaro\RetailCrm\Service\OrderLoyaltyDataService;

try {
    update();
} catch (Main\ObjectPropertyException | Main\ArgumentException | Main\SystemException $exception) {
    return;
}

/**
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 */
function update()
{
    /** @var EntityObject $exportSystem */
    $exportSystem = UpdaterRetailExportTable::query()
        ->addSelect('*')
        ->where('FILE_NAME', 'retailcrm')
        ->fetchObject();

    if ($exportSystem instanceof EntityObject) {
        replaceExportVars($exportSystem);
    }

    //updates fo 6.0.0
    $updater = new LoyaltyProgramUpdater();

    $updater->addLPEvents();
    $updater->CopyFiles();
    $updater->addLPUserFields();
    $updater->addAgreement();

    OrderLoyaltyDataService::createLoyaltyHlBlock();

    $service = new OrderLoyaltyDataService();
    $service->addCustomersLoyaltyFields();
}

class LoyaltyProgramUpdater
{
    use LoyaltyInstallerTrait;
}

/**
 * @throws \Bitrix\Main\SystemException
 * @throws \Bitrix\Main\ArgumentException
 */
function replaceExportVars(EntityObject $exportSystem)
{
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
        array_column($replaceableVars,'search'),
        array_column($replaceableVars, 'replace'),
        $setupVars
    );

    $exportSystem->set('SETUP_VARS', $newSetupVars);
    $exportSystem->save();
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
