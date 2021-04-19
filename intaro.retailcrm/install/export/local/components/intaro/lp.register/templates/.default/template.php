<?php

/**
 * Bitrix vars
 * @param array                    $arParams
 * @param array                    $arResult
 * @param CBitrixComponentTemplate $this
 * @global CUser                   $USER
 * @global CMain                   $APPLICATION
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$APPLICATION->SetTitle(GetMessage("REGISTER_LP_TITLE"));

if ($arResult["SHOW_SMS_FIELD"] == true) {
    CJSCore::Init('phone_auth');
}
?>
<?php CUtil::InitJSCore(['ajax', 'jquery', 'popup']); ?>
<div id="uf_agree_pl_intaro_popup" style="display:none;">
    <?=$arResult['AGREEMENT_LOYALTY_PROGRAM']?>
</div>
<div id="uf_pd_proc_pl_intaro_popup" style="display:none;">
    <?=$arResult['AGREEMENT_PERSONAL_DATA']?>
</div>
<script>
    BX.ready(function() {
        const lpAgreementPopup = new BX.PopupWindow('lp_agreement_popup', window.body, {
            autoHide:    true,
            offsetTop:   1,
            offsetLeft:  0,
            lightShadow: true,
            closeIcon:   true,
            closeByEsc:  true,
            overlay:     {
                backgroundColor: 'grey', opacity: '30'
            }
        });
        lpAgreementPopup.setContent(BX('uf_agree_pl_intaro_popup'));
        BX.bindDelegate(
            document.body, 'click', {className: 'lp_agreement_link'},
            BX.proxy(function(e) {
                if (!e)
                    e = window.event;
                lpAgreementPopup.show();
                return BX.PreventDefault(e);
            }, lpAgreementPopup)
        );

        const personalDataAgreementPopup = new BX.PopupWindow('personal_data_agreement_popup', window.body, {
            autoHide:    true,
            offsetTop:   1,
            offsetLeft:  0,
            lightShadow: true,
            closeIcon:   true,
            closeByEsc:  true,
            overlay:     {
                backgroundColor: 'grey', opacity: '30'
            }
        });
        personalDataAgreementPopup.setContent(BX('uf_pd_proc_pl_intaro_popup'));
        BX.bindDelegate(
            document.body, 'click', {className: 'personal_data_agreement_link'},
            BX.proxy(function(e) {
                if (!e)
                    e = window.event;
                personalDataAgreementPopup.show();
                return BX.PreventDefault(e);
            }, personalDataAgreementPopup)
        );
    });
</script>

<div class="bx-auth-reg">
    <?php if ($USER->IsAuthorized()): ?>
        <?php if ($arResult['LOYALTY_STATUS'] === 'Y'): ?>
            <?php $this->addExternalJs(SITE_TEMPLATE_PATH . '/script.js'); ?>
    <div id="regBody">
            <?php if (isset($arResult['LP_REGISTER']['msg'])) { ?>
                <div id="lpRegMsg" class="lpRegMsg"><?=$arResult['LP_REGISTER']['msg']?></div>
            <?php } ?>
        
            <?php
            if (isset($arResult['LP_REGISTER']['form']['fields'])) { ?>
                <div id="lpRegForm">
                    <div id="errMsg"></div>
                    <form id="lpRegFormInputs">
                        <?php
                        foreach ($arResult['LP_REGISTER']['form']['fields'] as $key => $field) {
                            ?>
                            <label>
                                <input
                                    name="<?=$key?>"
                                    id="<?=$key?>Field"
                                    type="<?=$field['type']?>"
                                    <?php if (isset($field['value'])) { ?>
                                        value="<?=$field['value']?>"
                                    <?php } ?>
                                >
                                <?php
                                if ($key === 'UF_AGREE_PL_INTARO') { ?>
                                <?=GetMessage('I_AM_AGREE')?><a class="lp_agreement_link" href="javascript:void(0)">
                                    <?php } ?>
                                    <?php
                                    if ($key === 'UF_PD_PROC_PL_INTARO') { ?>
                                <?=GetMessage('I_AM_AGREE')?><a class="personal_data_agreement_link" href="javascript:void(0)">
                                        <?php } ?>
                                        <?=GetMessage($key)?>
                                        <?php
                                        if ($key === 'UF_PD_PROC_PL_INTARO' || $key === 'UF_AGREE_PL_INTARO') { ?></a><?php } ?>
                            </label>
                        <?php
                        if ($field['type'] === 'checkbox') { ?>
                            <br>
                            <?php } ?>
                        <?php } ?>
                    </form>
                    <?php
                    if (isset($arResult['LP_REGISTER']['resendAvailable']) && !empty($arResult['LP_REGISTER']['resendAvailable'])) {
                        CUtil::InitJSCore(['intaro_countdown']);
                        ?>
                        <script>
                            $(function() {
                                const deadline = new Date('<?= $arResult['LP_REGISTER']['resendAvailable'] ?>');
                                initializeClock("countdown", deadline);
                            });
                        </script>
                        <div id="countdownDiv"> <?=GetMessage('RESEND_POSSIBLE')?> <span id="countdown"></span> <?=GetMessage('SEC')?></div>
                        <div id="deadlineMessage" style="display: none;">
                            <input type="button" onclick="resendRegisterSms(<?=$arResult['LP_REGISTER']['idInLoyalty']?>)" value="<?=GetMessage('RESEND_SMS')?>">
                        </div>
                    <?php } ?>
                    <input type="button" onclick="<?=$arResult['LP_REGISTER']['form']['button']['action']?>()" value="<?=GetMessage('SEND')?>">
                </div>
            <?php } ?>
    </div>
        <?php else: ?>
            <?=GetMessage('LP_NOT_ACTIVE')?>
        <?php endif; ?>
    <?php else: ?>
        <?=GetMessage('NOT_AUTHORIZED')?>
    <?php endif; ?>
</div>