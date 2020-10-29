<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Service
 * @author   retailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Service;

use Bitrix\Catalog\GroupTable;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\Event;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaySystem\Manager;
use CUser;
use Exception;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\PriceType;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\LoyaltyCalculateRequest;
use Intaro\RetailCrm\Model\Api\Request\Order\Loyalty\OrderLoyaltyApplyRequest;
use Intaro\RetailCrm\Model\Api\SerializedOrder;
use Intaro\RetailCrm\Model\Api\SerializedOrderProduct;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Model\Api\SerializedOrderReference;
use Intaro\RetailCrm\Model\Api\SerializedRelationCustomer;
use Intaro\RetailCrm\Repository\PaySystemActionRepository;

/**
 * Class LoyaltyService
 *
 * @package Intaro\RetailCrm\Service
 */
class LoyaltyService
{
    public const STANDARD_FIELDS = [
        'UF_REG_IN_PL_INTARO'  => 'checkbox',
        'UF_AGREE_PL_INTARO'   => 'checkbox',
        'UF_PD_PROC_PL_INTARO' => 'checkbox',
        'UF_CARD_NUM_INTARO'   => 'text',
        'PERSONAL_PHONE'       => 'text',
    ];
    
    /**
     * @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter
     */
    private $client;
    
    /**
     * LoyaltyService constructor.
     */
    public function __construct()
    {
        IncludeModuleLangFile(__FILE__);
        $this->client = ClientFactory::createClientAdapter();
    }
    
    /*
     * Возвращает статус пользователя в системе лояльности
     */
    public static function getLoyaltyPersonalStatus(): bool
    {
        global $USER;
        $userFields = CUser::GetByID($USER->GetID())->Fetch();
        
        return isset($userFields['UF_EXT_REG_PL_INTARO']) && $userFields['UF_EXT_REG_PL_INTARO'] === '1';
    }
    
    /**
     * @param int $orderId
     * @param int $bonusCount
     * @return \Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse|mixed|null
     */
    public function sendBonusPayment(int $orderId, int $bonusCount)
    {
        $request                    = new OrderLoyaltyApplyRequest();
        $request->order             = new SerializedOrderReference();
        $request->order->externalId = $orderId;
        $request->bonuses           = $bonusCount;
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client        = ClientFactory::createClientAdapter();
        $credentials   = $client->getCredentials();
        $request->site = $credentials->sitesAvailable[0];
        
        $result = $this->client->loyaltyOrderApply($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }
        
        return $result;
    }
    
    /**
     * @param array $basketItems
     * @param int   $discountPrice
     * @param int   $discountPercent
     * @return \Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse|mixed|null
     */
    public function calculateBonus(array $basketItems, int $discountPrice, int $discountPercent)
    {
        global $USER;
    
        $request                              = new LoyaltyCalculateRequest();
        $request->order                       = new SerializedOrder();
        $request->order->customer             = new SerializedRelationCustomer();
        $request->order->customer->id         = $USER->GetID();
        $request->order->customer->externalId = $USER->GetID();
        
        if ($discountPrice > 0) {
            $request->order->discountManualAmount = $discountPrice;
        }
        
        if ($discountPercent > 0) {
            $request->order->discountManualPercent = $discountPercent;
        }
        
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client        = ClientFactory::createClientAdapter();
        $credentials   = $client->getCredentials();
        $request->site = $credentials->sitesAvailable[0];
        
        foreach ($basketItems as $item) {
            $product = new SerializedOrderProduct();
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualPercent = $item['DISCOUNT_PRICE_PERCENT'];
            }
            
            if ($item['DISCOUNT_PRICE_PERCENT'] > 0) {
                $product->discountManualAmount = $item['DISCOUNT_PRICE'];
            }
            
            $product->initialPrice      = $item['PRICE'];
            $product->offer = new SerializedOrderProductOffer();
            $product->offer->externalId = $item['ID'];
            $product->offer->id         = $item['ID'];
            $product->offer->xmlId      = $item['XML_ID'];
            $product->quantity          = $item['QUANTITY'];
            
            try {
                $price = GroupTable::query()
                    ->setSelect(['NAME'])
                    ->where(
                        [
                            ['ID', '=', $item['PRICE_TYPE_ID']],
                        ]
                    )
                    ->fetch();
                $product->priceType = new PriceType();
                $product->priceType->code = $price['NAME'];
            } catch (ObjectPropertyException | ArgumentException | SystemException $e) {
                AddMessage2Log('GroupTable query error: ' . $e->getMessage());
            }
            $request->order->items[] = $product;
        }
        
        $result = $this->client->loyaltyCalculate($request);
        
        if (isset($result->errorMsg) && !empty($result->errorMsg)) {
            AddMessage2Log($result->errorMsg);
        }
        
        return $result;
    }
    
    //TODO доделать метод проверки регистрации в ПЛ
    /**
     * @return array
     */
    public function checkRegInLp(): array
    {
        global $USER;
        $rsUser     = CUser::GetByID($USER->GetID());
        $userFields = $rsUser->Fetch();
        $regInLp    = [];
        
        if (!$userFields) {
            return [];
        }
        
        //Изъявлял ли ранее пользователь желание участвовать в ПЛ?
        if (isset($userFields['UF_REG_IN_PL_INTARO'])
            && $userFields['UF_REG_IN_PL_INTARO'] === '1'
        ) {
            //ДА. Существует ли у него аккаунт?
            if (isset($userFields['UF_LP_ID_INTARO'])
                && !empty($userFields['UF_LP_ID_INTARO'])
            ) {
                //ДА. Активен ли его аккаунт?
                if (isset($userFields['UF_EXT_REG_PL_INTARO'])
                    && $userFields['UF_EXT_REG_PL_INTARO'] === '1'
                ) {
                    //ДА. Отображаем сообщение "Вы зарегистрированы в Программе лояльности"
                    $regInLp['msg'] = GetMessage('REG_COMPLETE');
                } else {
                    //НЕТ. Акаунт не активен
                    /** @var \Intaro\RetailCrm\Service\UserAccountService $userService */
                    $userService = ServiceLocator::get(UserAccountService::class);
                    $extFields   = $userService->getExtFields($userFields['UF_EXT_REG_PL_INTARO']);
                    
                    //Есть ли обязательные поля, которые нужно заполнить для завершения активации?
                    if (!empty($extFields)) {
                        //Да, есть незаполненные обязательные поля
                        $regInLp = [
                            'msg'  => GetMessage('ACTIVATE_YOUR_ACCOUNT'),
                            'form' => [
                                'button' => [
                                    'name'   => GetMessage('ACTIVATE'),
                                    'action' => 'activateAccount',
                                ],
                                'fields' => $extFields,
                            ],
                        ];
                    } else {
                        //НЕТ. Обязательных незаполненных полей нет. Тогда пробуем активировать аккаунт
                        $activateResponse = $userService->activateLoyaltyAccount($userFields['UF_EXT_REG_PL_INTARO']);
                        
                        if ($activateResponse !== null
                            && isset($activateResponse->loyaltyAccount->active)
                            && $activateResponse->loyaltyAccount->active === true) {
                            $regInLp['msg'] = GetMessage('REG_COMPLETE');
                        }
                        
                        //нужна смс верификация
                        if (isset($activateResponse->verification, $activateResponse->verification->checkId)
                            && $activateResponse !== null
                            && !isset($activateResponse->verification->verifiedAt)
                        ) {
                            $regInLp = [
                                'msg'  => GetMessage('SMS_VERIFICATION'),
                                'form' => [
                                    'button' => [
                                        'name'   => GetMessage('SEND'),
                                        'action' => 'sendSmsCode',
                                    ],
                                    'fields' => [
                                        'smsVerificationCode' => [
                                            'type' => 'text',
                                        ],
                                        'checkId'             => [
                                            'type'  => 'hidden',
                                            'value' => $activateResponse->verification->checkId,
                                        ],
                                    ],
                                ],
                            ];
                        }
                    }
                }
            } else {
                //НЕТ. Выясняем, каких полей не хватает для СОЗДАНИЯ аккаунта, выводим форму
                $fields = $this->getFields($userFields);
                
                //Если все необходимые поля заполнены, то пытаемся его еще раз зарегистрировать
                if (empty($fields)) {
                    $phone          = $userFields['PERSONAL_PHONE'] ?? '';
                    $card           = $userFields['UF_CARD_NUM_INTARO'] ?? '';
                    $customerId     = (string) $userFields['ID'];
                    $customFields   = $userFields['UF_CSTM_FLDS_INTARO'] ?? [];
                    $service        = new UserAccountService();
                    $createResponse = $service->createLoyaltyAccount($phone, $card, $customerId, $customFields);
                    $service->activateLpUserInBitrix($createResponse, $userFields['ID']);

                    if ($createResponse !== null
                        && $createResponse->success === false
                        && isset($createResponse->errorMsg)
                        && !empty($createResponse->errorMsg)
                    ) {
                        if (isset($createResponse->errors) && is_array($createResponse->errors)) {
                            $errorDetails = '';
                            
                            foreach ($createResponse->errors as $error) {
                                $errorDetails .= $error.' ';
                            }
                        }
                        AddMessage2Log(GetMessage('REGISTER_ERROR') . ' ('.$createResponse->errorMsg.' '. $errorDetails .')');
                        
                        $regInLp['msg']  = GetMessage('REGISTER_ERROR') . ' ('.$createResponse->errorMsg.' '. $errorDetails .')';
                    }elseif($createResponse->success === true){
                        //Повторная регистрация оказалась удачной
                        header("Refresh: 0");
                    }
                }else{
                    $regInLp['msg']  = GetMessage('COMPLETE_YOUR_REGISTRATION');
                    $regInLp['form'] = [
                        'button' => [
                            'name'   => GetMessage('CREATE'),
                            'action' => 'createAccount',
                        ],
                        'fields' => $this->getFields($userFields),
                    ];
                }
            }
        } else {
            //НЕТ. Отображаем форму на создание новой регистрации в ПЛ
            $regInLp['msg']  = GetMessage('INVITATION_TO_REGISTER');
            $regInLp['form'] = [
                'button' => [
                    'name'   => GetMessage('CREATE'),
                    'action' => 'createAccount',
                ],
                'fields' => $this->getFields($userFields),
            ];
        }
        
        return $regInLp;
    }
    
    /**
     * @param \Bitrix\Main\Event $event
     */
    public function applyBonusesInOrder(Event $event): void
    {
            /**@var \Bitrix\Sale\Order $order */
            $order      = $event->getParameter("ENTITY");
            $orderId    = $order->getId();
            $bonusCount = (int)$_POST['bonus-input'];
            $response   = $this->sendBonusPayment($orderId, $bonusCount);
        
            if ($response->success) {
                try {
                    $bonusPaySystem    = PaySystemActionRepository::getFirstByWhere(['ID'], [['ACTION_FILE', '=', 'retailcrmbonus']]);
                    $paymentCollection = $order->getPaymentCollection();
                
                    if ($bonusPaySystem !== null) {
                        if (count($paymentCollection) === 1) {
                            /** @var \Bitrix\Sale\Payment $payment */
                            foreach ($paymentCollection as $payment){
                                $oldSum = $payment->getField('SUM');
                            
                                $payment->setField('SUM', $oldSum - $bonusCount);
                                break;
                            }
                        }
                    
                        $service    = Manager::getObjectById($bonusPaySystem->getId());
                        $newPayment = $paymentCollection->createItem($service);
                    
                        $newPayment->setField('SUM', $bonusCount);
                    
                        //если верификация необходима, но не пройдена
                        if (isset($response->verification, $response->verification->checkId)
                            && !isset($response->verification->verifiedAt)
                        ) {
                            $newPayment->setPaid('N');
                            $newPayment->setField('COMMENTS', $response->verification->checkId);
                        }
                    
                        //если верификация не нужна
                        if (!isset($response->verification)) {
                            $newPayment->setPaid('Y');
                        }
                    
                        $order->save();
                    }
                } catch (ObjectPropertyException | ArgumentException | SystemException | Exception $e) {
                    AddMessage2Log('ERROR PaySystemActionRepository: ' . $e->getMessage());
                }
            }
    }
    
    /**
     * @param array $userFields
     * @return array
     */
    private function getStandardFields(array $userFields): array
    {
        $resultFields = [];
        foreach (self::STANDARD_FIELDS as $key => $value) {
            if ($value === 'text' && empty($userFields[$key])) {
                $resultFields[$key] = [
                    'type' => $value,
                ];
            }
            if ($value === 'checkbox' && $userFields[$key] !== '1') {
                $resultFields[$key] = [
                    'type' => $value,
                ];
            }
        }
        return $resultFields;
    }
    
    /**
     * @param array $userFields
     * @return array
     */
    private function getFields(array $userFields)
    {
        $standartFields = $this->getStandardFields($userFields);
        $externalFields = $this->getExternalFields();
        return array_merge($standartFields, $externalFields);
    }
    
    /**
     * TODO реализовать получение кастомных полей из CRM
     * @return array
     */
    private function getExternalFields(): array
    {
        return [];
    }
}
