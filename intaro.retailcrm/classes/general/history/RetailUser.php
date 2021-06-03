<?php

/**
 * Class RetailUser
 */
class RetailUser extends CUser
{
    /**
     * @return int|mixed|string|null
     */
    public function GetID()
    {
        $rsUser = CUser::GetList(($by = 'ID'), ($order = 'DESC'), ['LOGIN' => 'retailcrm']);

        if ($arUser = $rsUser->Fetch()) {
            return $arUser['ID'];
        } else {
            $retailUser = new CUser;

            $userPassword = uniqid();

            $arFields = [
                "NAME"             => 'retailcrm',
                "LAST_NAME"        => 'retailcrm',
                "EMAIL"            => 'retailcrm@retailcrm.com',
                "LOGIN"            => 'retailcrm',
                "LID"              => "ru",
                "ACTIVE"           => "Y",
                "GROUP_ID"         => [2],
                "PASSWORD"         => $userPassword,
                "CONFIRM_PASSWORD" => $userPassword,
            ];

            $id = $retailUser->Add($arFields);

            if (!$id) {
                return null;
            } else {
                return $id;
            }
        }
    }
}
