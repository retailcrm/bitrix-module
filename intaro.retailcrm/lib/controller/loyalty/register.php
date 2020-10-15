<?php

namespace Intaro\RetailCrm\Controller\Loyalty;

use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Controller;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Constants;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountActivateRequest;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountCreateRequest;
use Intaro\RetailCrm\Model\Api\SerializedCreateLoyaltyAccount;

class Register extends Controller
{
    public function configureActions(): array
    {
        return [
            'accountCreate' => [
                '-prefilters' => [
                    Authentication::class,
                ],
            ],
        ];
    }
    
    /**
     * @return string[]
     */
    public function accountCreateAction($loyaltyAccount): array
    {
        /** @var \Intaro\RetailCrm\Component\ApiClient\ClientAdapter $client */
        $client      = ClientFactory::createClientAdapter();
        $credentials = $client->getCredentials();
    
        $createRequest                               = new LoyaltyAccountCreateRequest();
        $createRequest->site                         = $credentials->sitesAvailable[0];
        $createRequest->loyaltyAccount               = new SerializedCreateLoyaltyAccount();
        $createRequest->loyaltyAccount->phoneNumber  = $loyaltyAccount->phone;
        $createRequest->loyaltyAccount->cardNumber   = $loyaltyAccount->card;
        $createRequest->loyaltyAccount->customerId   = $loyaltyAccount->customerId;
        $createRequest->loyaltyAccount->customFields = $loyaltyAccount->customFields;
    
        $createResponse = $client->createLoyaltyAccount($createRequest);
        
        if ($createResponse !== null) {
            
            //если участник ПЛ создан и активирован
            if ($createResponse->loyaltyAccount->active) {
                return ['status' => 'activate'];
            }
            
            $activateRequest = new LoyaltyAccountActivateRequest();
            $activateRequest->loyaltyId = $createResponse->loyaltyAccount->id;
            $activateResponse = $client->activateLoyaltyAccount($activateRequest);
            
            if (isset($activateResponse->verification)) {
                return ['status' => 'smsVerification'];
            }
            
        }
        
        
        return ['newStatus' => $newStatus];
    }
}
