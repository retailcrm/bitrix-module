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

if ($arResult["LOYALTY_CONNECTION_ERROR"] === true) {
    echo GetMessage('LOYALTY_CONNECTION_ERROR');

    die();
}

$allowedFormActions = ['resetUserLpFields', 'saveUserLpFields', 'sendVerificationCode', 'activateAccount'];
$formButtonAction = (string) ($arResult['LP_REGISTER']['form']['button']['action'] ?? '');
$safeFormButtonAction = in_array($formButtonAction, $allowedFormActions, true) ? $formButtonAction : '';

$sanitizer = new \CBXSanitizer();
$sanitizer->SetLevel(\CBXSanitizer::SECURE_LEVEL_MIDDLE);
$safeLoyaltyAgreement = $sanitizer->SanitizeHtml((string) ($arResult['AGREEMENT_LOYALTY_PROGRAM'] ?? ''));
$safePersonalDataAgreement = $sanitizer->SanitizeHtml((string) ($arResult['AGREEMENT_PERSONAL_DATA'] ?? ''));
?>
<?php CUtil::InitJSCore(['ajax', 'jquery', 'popup']); ?>
<div id="uf_agree_pl_intaro_popup" style="display:none;">
    <?= $safeLoyaltyAgreement ?>
</div>
<div id="uf_pd_proc_pl_intaro_popup" style="display:none;">
    <?= $safePersonalDataAgreement ?>
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
        <?php if ('Y' === $arResult['LOYALTY_STATUS']): ?>
            <?php $this->addExternalJs(SITE_TEMPLATE_PATH . '/script.js'); ?>
    <div id="regBody">
            <?php if (isset($arResult['LP_REGISTER']['msg'])) { ?>
                <div id="lpRegMsg" class="lpRegMsg"><?= htmlspecialcharsbx((string) $arResult['LP_REGISTER']['msg']) ?></div>
            <?php } ?>

            <?php if (isset($arResult['LP_REGISTER']['form']['button']) && !isset($arResult['LP_REGISTER']['form']['fields'])) { ?>
                <input type="button" onclick="<?= htmlspecialcharsbx($safeFormButtonAction) ?>()" value="<?=GetMessage('TRY_AGAIN')?>">
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
                                    name="<?= htmlspecialcharsbx((string) $key) ?>"
                                    id="<?= htmlspecialcharsbx((string) $key) ?>Field"
                                    type="<?= htmlspecialcharsbx((string) $field['type']) ?>"
                                    <?php if (isset($field['value'])) { ?>
                                        value="<?= htmlspecialcharsbx((string) $field['value']) ?>"
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
                            <br>
                        <?php
                        if ($field['type'] === 'checkbox') { ?>
                            <br>
                            <?php } ?>
                        <?php } ?>

                        <?php
                        if ($arResult['ACTIVATE'] === true) {
                            foreach ($arResult['LP_REGISTER']['form']['externalFields'] as $externalField) {
                                ?>
                                <lable>
                                    <?php
                                    if ($externalField['type'] === 'string' || $externalField['type'] === 'date') { ?>
                                        <input
                                            name="<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            id="external_<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            type="<?= htmlspecialcharsbx((string) $externalField['type']) ?>"
                                        >
                                        <?= htmlspecialcharsbx((string) $externalField['name']) ?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'boolean') { ?>
                                        <input
                                            name="<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            id="external_<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            type="checkbox"
                                        >
                                        <?= htmlspecialcharsbx((string) $externalField['name']) ?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'text') { ?>
                                        <textarea
                                            name="<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            id="external_<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            cols="30"
                                            rows="10"
                                        ></textarea>
                                        <?= htmlspecialcharsbx((string) $externalField['name']) ?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'integer' || $externalField['type'] === 'numeric') { ?>
                                        <input
                                            name="<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            id="external_<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            type="number"
                                        >
                                        <?= htmlspecialcharsbx((string) $externalField['name']) ?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'email') { ?>
                                        <input
                                            name="<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            id="external_<?= htmlspecialcharsbx((string) $externalField['code']) ?>"
                                            type="email"
                                        >
                                        <?= htmlspecialcharsbx((string) $externalField['name']) ?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'dictionary') { ?>
                                        <select name="<?= htmlspecialcharsbx((string) $externalField['code']) ?>">
                                            <?php
                                            foreach ($externalField['dictionaryElements'] as $dictionaryElement) {
                                                ?>
                                                <option value="<?= htmlspecialcharsbx((string) $dictionaryElement['code']) ?>"><?= htmlspecialcharsbx((string) $dictionaryElement['name']) ?> </option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                        <?= htmlspecialcharsbx((string) $externalField['name']) ?>
                                    <?php } ?>
                                </lable>
                                <br>
                                <?php
                            }
                        }
                        ?>
                    </form>
                    <?php
                    if (isset($arResult['LP_REGISTER']['resendAvailable']) && !empty($arResult['LP_REGISTER']['resendAvailable'])) {
                        CUtil::InitJSCore(['intaro_countdown']);
                        ?>
                        <script>
                            $(function() {
                                const deadline = new Date('<?= CUtil::JSEscape((string) $arResult['LP_REGISTER']['resendAvailable']) ?>');
                                initializeClock("countdown", deadline);
                            });
                        </script>
                        <div id="countdownDiv"> <?=GetMessage('RESEND_POSSIBLE')?> <span id="countdown"></span> <?=GetMessage('SEC')?></div>
                        <div id="deadlineMessage" style="display: none;">
                            <input type="button" onclick="resendRegisterSms(<?= (int) $arResult['LP_REGISTER']['idInLoyalty'] ?>)" value="<?=GetMessage('RESEND_SMS')?>">
                        </div>
                    <?php } ?>
                    <input type="button" onclick="<?= htmlspecialcharsbx($safeFormButtonAction) ?>()" value="<?=GetMessage('SEND')?>">
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
