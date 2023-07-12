<?php

namespace Intaro\RetailCrm\Service;

class SubscriberService
{

    public static function getSubscribeStatusUser(): bool
    {
        global $USER;

        $userFields = CUser::GetByID($USER->GetID())->Fetch();

        return isset($userFields['UF_SUBSCRIBE_EMAIL']) && $userFields['UF_SUBSCRIBE_EMAIL'] == '1';
    }
}