<?php

/**
 * PHP version 7.1
 *
 * @category Integration
 * @package  Intaro\RetailCrm\Component\Builder\API
 * @author   RetailCRM <integration@retailcrm.ru>
 * @license  MIT
 * @link     http://retailcrm.ru
 * @see      http://retailcrm.ru/docs
 */

namespace Intaro\RetailCrm\Component\Builder\Api\Request;

use Bitrix\Main\Type\DateTime;
use Intaro\RetailCrm\Component\Builder\BuilderInterface;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;
use Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountEditRequest;

/**
 * Class LoyaltyAccountEditRequestBuilder
 *
 *
 * @package Intaro\RetailCrm\Component\Builder\Api\Request
 */
class LoyaltyAccountEditRequestBuilder implements BuilderInterface
{
    /**
     * @var array
     */
    private $formFields;

    /**
     * @var \Intaro\RetailCrm\Model\Api\Request\Loyalty\Account\LoyaltyAccountEditRequest
     */
    private $request;

    /**
     * @param array $formFields
     */
    public function setFormFields(array $formFields): LoyaltyAccountEditRequestBuilder
    {
        $this->formFields = $formFields;

        return $this;
    }

    public function build(): BuilderInterface
    {
        $this->request = new LoyaltyAccountEditRequest();
        $this->request->loyaltyAccount = new LoyaltyAccount();
        $this->request->loyaltyAccount->customFields = [];

        foreach ($this->formFields as $type => $fields) {
            $this->request->loyaltyAccount->customFields = array_merge(
                $this->request->loyaltyAccount->customFields,
                $this->handleFields($type, $fields)
            );
        }

        return $this;
    }

    public function reset(): BuilderInterface
    {
        $this->request = new LoyaltyAccountEditRequest();

        return $this;
    }

    public function getResult(): LoyaltyAccountEditRequest
    {
        return $this->request;
    }

    /**
     * @param string $type
     * @param array  $fields
     *
     * @return array
     */
    private function handleFields(string $type, array $fields): array
    {
        $newFields = [];

        foreach ($fields as $field) {
            if ($type === 'checkboxes') {
                $newFields[$field['code']] = (bool) $field['value'];
            }

            if ($type === 'numbers') {
                if ($field['code'] === 'idInLoyalty') {
                    $this->request->id = (int) $field['value'];

                    continue;
                }

                $newFields[$field['code']] = (int) $field['value'];
            }

            if ($type === 'strings') {
                if ($field['code'] === 'PERSONAL_PHONE') {
                    $this->request->loyaltyAccount->phoneNumber = htmlspecialchars(trim($field['value']));

                    continue;
                }

                if ($field['code'] === 'UF_CARD_NUM_INTARO') {
                    $this->request->loyaltyAccount->cardNumber = htmlspecialchars(trim($field['value']));

                    continue;
                }

                $newFields[$field['code']] = htmlspecialchars(trim($field['value']));
            }

            if ($type === 'dates') {
                $newFields[$field['code']] = strtotime($field['value']);
            }

            if ($type === 'options') {
                $newFields[$field['code']] = htmlspecialchars(trim($field['value']));
            }
        }

        return $newFields;
    }
}
