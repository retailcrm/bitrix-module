<?php if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Service\CookieService;
use Intaro\RetailCrm\Service\LoyaltyService;
use Intaro\RetailCrm\Service\LoyaltyAccountService;

/**
 * @var array $arParams
 * @var array $arResult
 * @var       $APPLICATION CMain
 */

if ($arParams["SET_TITLE"] === "Y") {
    $APPLICATION->SetTitle(Loc::getMessage("SOA_ORDER_COMPLETE"));
}
?>
<?php if (!empty($arResult["ORDER"])): ?>
    <?php
    if ($arResult['LOYALTY_STATUS'] === 'Y' && $arResult['PERSONAL_LOYALTY_STATUS'] === true) {
        /** @var LoyaltyService $loyaltyService */
        $loyaltyService = ServiceLocator::get(LoyaltyService::class);
    
        /** @var CookieService $cookieService */
        $cookieService = ServiceLocator::get(CookieService::class);
        $isDebited     = $loyaltyService->isBonusDebited($arResult["ORDER"]['ID']);
        
        //если есть бонусная оплата и она не оплачена, то отрисовываем форму введения кода верификации
        if ($isDebited !== null && $isDebited === false) {
            
            $smsCookie = $cookieService->getSmsCookie('lpOrderBonusConfirm');

            //если куки пустые (страница обновляется после длительного перерыва), то пробуем снова отправить бонусы
            if ($smsCookie === null || empty($smsCookie->checkId)) {
                $smsCookie = $loyaltyService->resendBonusPayment($arResult["ORDER"]['ID']);
            }
            
            if ($smsCookie === false) { ?>
                <div><?=GetMessage('BONUS_ERROR')?></div>
            <?php } elseif ($smsCookie === true) { ?>
                <div><?=GetMessage('BONUS_SUCCESS')?></div>
            <?php } else {
                CUtil::InitJSCore(['intaro_countdown']);
                ?>
                <div id="orderConfirm">
                    <b><?=GetMessage('CONFIRM_MESSAGE')?></b><br>
                    <div id="orderVerificationCodeBlock">
                        <b><?=GetMessage('SEND_VERIFICATION_CODE')?></b><br>
                        <label for="orderVerificationCode"></label>
                        <input type="text" id="orderVerificationCode" placeholder="<?=GetMessage('VERIFICATION_CODE')?>">
                        <input type="hidden" id="orderIdVerify" value="<?=$arResult["ORDER"]['ID']?>">
                        <input type="hidden" id="checkIdVerify" value="<?=$smsCookie->checkId?>">
                        <input type="button" onclick="sendOrderVerificationCode()" value="<?=GetMessage('SEND')?>"/>
                    </div>
                    <div>
                        <script>
                            $(function() {
                                const deadline = new Date('<?= $smsCookie->resendAvailable->format('Y-m-d H:i:s') ?>');
                                initializeClock("countdown", deadline);
                            });
                        </script>
                        <div id="countdownDiv"> <?=GetMessage('RESEND_POSSIBLE')?> <span id="countdown"></span> <?=GetMessage('SEC')?></div>
                        <div id="deadlineMessage" style="display: none;">
                            <input type="button" onclick="resendOrderSms(<?=$arResult["ORDER"]['ID']?>)" value="<?=GetMessage('RESEND_SMS')?>">
                        </div>
                    </div>
                    <div id="msg"></div>
                </div>
                <br><br>
                <?php
            }
        }
    }
    ?>
    <table class="sale_order_full_table">
        <tr>
            <td>
                <?=Loc::getMessage("SOA_ORDER_SUC", [
                    "#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format('d.m.Y H:i'),
                    "#ORDER_ID#"   => $arResult["ORDER"]["ACCOUNT_NUMBER"],
                ])?>
                <?php if ($arParams['NO_PERSONAL'] !== 'Y'): ?>
                    <br/><br/>
                    <?=Loc::getMessage('SOA_ORDER_SUC1', ['#LINK#' => $arParams['PATH_TO_PERSONAL']])?>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    
    <?php
    if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y') {
        if (!empty($arResult["PAYMENT"])) {
            foreach ($arResult["PAYMENT"] as $payment) {
                if ($payment["PAID"] !== 'Y') {
                    if (!empty($arResult['PAY_SYSTEM_LIST'])
                        && array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
                    ) {
                        $arPaySystem = $arResult['PAY_SYSTEM_LIST_BY_PAYMENT_ID'][$payment["ID"]];
                        
                        if (empty($arPaySystem["ERROR"])) {
                            ?>
                            <br/><br/>

                            <table class="sale_order_full_table">
                                <tr>
                                    <td class="ps_logo">
                                        <div class="pay_name"><?=Loc::getMessage("SOA_PAY")?></div>
                                        <?=CFile::ShowImage($arPaySystem["LOGOTIP"], 100, 100, "border=0\" style=\"width:100px\"", "", false)?>
                                        <div class="paysystem_name"><?=$arPaySystem["NAME"]?></div>
                                        <br/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php if (strlen($arPaySystem["ACTION_FILE"]) > 0 && $arPaySystem["NEW_WINDOW"] === "Y" && $arPaySystem["IS_CASH"] != "Y"): ?>
                                            <?php
                                            $orderAccountNumber   = urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]));
                                            $paymentAccountNumber = $payment["ACCOUNT_NUMBER"];
                                            ?>
                                            <script>
                                                window.open('<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=$orderAccountNumber?>&PAYMENT_ID=<?=$paymentAccountNumber?>');
                                            </script>
                                        <?=Loc::getMessage("SOA_PAY_LINK", ["#LINK#" => $arParams["PATH_TO_PAYMENT"] . "?ORDER_ID=" . $orderAccountNumber . "&PAYMENT_ID=" . $paymentAccountNumber])?>
                                        <?php if (CSalePdf::isPdfAvailable() && $arPaySystem['IS_AFFORD_PDF']): ?>
                                        <br/>
                                            <?=Loc::getMessage("SOA_PAY_PDF", ["#LINK#" => $arParams["PATH_TO_PAYMENT"] . "?ORDER_ID=" . $orderAccountNumber . "&pdf=1&DOWNLOAD=Y"])?>
                                        <?php endif ?>
                                        <?php else: ?>
                                            <?=$arPaySystem["BUFFERED_OUTPUT"]?>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            </table>
    
                            <?php
                        } else {
                            ?>
                            <span style="color:red;"><?=Loc::getMessage("SOA_ORDER_PS_ERROR")?></span>
                            <?php
                        }
                    } else {
                        ?>
                        <span style="color:red;"><?=Loc::getMessage("SOA_ORDER_PS_ERROR")?></span>
                        <?php
                    }
                }
            }
        }
    } else {
        ?>
        <br/><strong><?=$arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR']?></strong>
        <?php
    }
    ?>

<?php else: ?>

    <b><?=Loc::getMessage("SOA_ERROR_ORDER")?></b>
    <br/><br/>

    <table class="sale_order_full_table">
        <tr>
            <td>
                <?=Loc::getMessage("SOA_ERROR_ORDER_LOST", ["#ORDER_ID#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"])])?>
                <?=Loc::getMessage("SOA_ERROR_ORDER_LOST1")?>
            </td>
        </tr>
    </table>

<?php endif ?>
