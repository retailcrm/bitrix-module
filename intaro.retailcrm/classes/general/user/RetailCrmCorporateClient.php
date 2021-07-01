<?php

use Bitrix\Main\UserTable;
use RetailCrm\ApiClient;
use RetailCrm\Response\ApiResponse;

IncludeModuleLangFile(__FILE__);
class RetailCrmCorporateClient
{
    const CORP_PREFIX = 'corp';

    public static function clientSend($arOrder, $api, $contragentType, $send = false, $fillCorp = false, $site = null)
    {
        if (!$api || empty($contragentType)) {
            return false;
        }

        $address = array();
        $contragent = array();
        $shops = RetailcrmConfigProvider::getSitesListCorporate();
        $optionsLegalDetails = RetailcrmConfigProvider::getLegalDetails();
        $arUser = UserTable::getById($arOrder['USER_ID'])->fetch();

        if (count($shops) == 0) {
            RCrmActions::eventLog('RetailCrmCorporateClient::clientSend()', '$shops', 'No stores selected for download');

            return false;
        }

        foreach ($arOrder['PROPS']['properties'] as $prop) {
            if ($prop['CODE'] == RetailcrmConfigProvider::getCorporateClientName()) {
                $nickName = $prop['VALUE'][0];
            }

            if ($prop['CODE'] == RetailcrmConfigProvider::getCorporateClientAddress()) {
                $address = $prop['VALUE'][0];
            }

            if (!empty($optionsLegalDetails)
                && $search = array_search($prop['CODE'], $optionsLegalDetails[$arOrder['PERSON_TYPE_ID']])
            ) {
                $contragent[$search] = $prop['VALUE'][0];//legal order data
            }
        }

        if (empty($nickName)) {
            $nickName = $arUser['WORK_COMPANY'];
        }

        if (!empty($contragentType)) {
            $contragent['contragentType'] = $contragentType;
        }

        foreach ($shops as $shop) {
            $customerCorporate = [
                'createdAt' => $arOrder['DATE_INSERT'],
                "nickName" => $nickName,
            ];

            if ($fillCorp) {
                $customerCorporate = array_merge(
                    $customerCorporate,
                    [
                        'customerContacts' => [
                            [
                                'isMain' => true,
                                'customer' => [
                                    'externalId' => $arUser['ID'],
                                    'site' => $shop,
                                ],
                            ],
                        ],
                        'companies' => [
                            [
                                'name' => $nickName,
                                'isMain' => true,
                            ],
                        ],
                        'addresses' => [
                            [
                                'name' => $nickName,
                                'isMain' => true,
                                'text' => $address,
                            ],
                        ],
                    ]
                );
            }
        }

        if (isset($customerCorporate)) {
            if ($send && isset($_COOKIE['_rc']) && $_COOKIE['_rc'] != '') {
                $customerCorporate['browserId'] = $_COOKIE['_rc'];
            }

            $normalizer = new RestNormalizer();
            $customerCorporate = $normalizer->normalize($customerCorporate, 'customerCorporate');

            Logger::getInstance()->write($customerCorporate, 'clientCorporate');

            if ($send) {
                $result = RCrmActions::apiMethod($api, 'customersCorporateCreate', __METHOD__, $customerCorporate, $site);

                if (!$result) {
                    return false;
                }

                $customerCorporate['id'] = $result['id'];
            }

            return $customerCorporate;
        }

        return array();
    }

    public static function addCustomersCorporateAddresses($customeId, $legalName, $adress, $api, $site)
    {
        $found = false;
        $addresses = $api->customersCorporateAddresses(
            $customeId,
            array(),
            null,
            100,
            'id',
            $site
        );

        if ($addresses && $addresses->isSuccessful() && $addresses->offsetExists('addresses')) {
            foreach ($addresses['addresses'] as $corpAddress) {
                if (isset($corpAddress['text']) && $corpAddress['text'] == $adress) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                $customerCorporateAddress = array(
                    'name' => $legalName,
                    'text' => $adress
                );

                $addressResult = $api->customersCorporateAddressesCreate(
                    $customeId,
                    $customerCorporateAddress,
                    'id',
                    $site
                );

                if (!$addressResult || ($addressResult && !$addressResult->isSuccessful())) {
                    Logger::getInstance()->write(sprintf(
                        'error while trying to append address to corporate customer%s%s',
                        PHP_EOL,
                        print_r(array(
                            'address' => $customerCorporateAddress,
                            'customer' => $customeId
                        ), true)
                    ), 'apiErrors');
                }
            }
        }
    }

    /**
     * Проверяет, существует ли корпоративный клиент с указанным externalId
     *
     * @param                      $bitrixUserId
     * @param \RetailCrm\ApiClient $api
     *
     * @return bool
     */
    public static function isCorpTookExternalId($bitrixUserId, ApiClient $api): bool
    {
        $response = RCrmActions::apiMethod(
            $api,
            'customersCorporateGet',
            __METHOD__,
            $bitrixUserId
        );

        if (false === $response) {
            return false;
        }

        if ($response instanceof ApiResponse && $response->offsetGet('customerCorporate')) {
            return true;
        }

        return false;
    }

    /**
     * @param                      $externalId
     * @param \RetailCrm\ApiClient $api
     */
    public static function setPrefixForExternalId($externalId, ApiClient $api)
    {
        $response = RCrmActions::apiMethod(
            $api,
            'customersCorporateEdit',
            __METHOD__,
            [
                'urlId' => $externalId,
                'externalId' => self::CORP_PREFIX . $externalId
            ]
        );

        if (false === $response) {
            Logger::getInstance()->write(
                sprintf('Не удалось добавить префикс для корпоративного клиента %s',  $externalId),
                'clientCorporate'
            );
        }
    }
}
