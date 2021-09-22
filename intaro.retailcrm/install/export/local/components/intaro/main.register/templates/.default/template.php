<?php

/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2014 Bitrix
 */

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

        <p><?php echo GetMessage("MAIN_REGISTER_AUTH") ?></p>

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
                                            name="<?=$externalField['code']?>"
                                            id="external_<?=$externalField['code']?>"
                                            type="<?=$externalField['type']?>"
                                        >
                                        <?=$externalField['name']?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'boolean') { ?>
                                        <input
                                            name="<?=$externalField['code']?>"
                                            id="external_<?=$externalField['code']?>"
                                            type="checkbox"
                                        >
                                        <?=$externalField['name']?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'text') { ?>
                                        <textarea
                                            name="<?=$externalField['code']?>"
                                            id="external_<?=$externalField['code']?>"
                                            cols="30"
                                            rows="10"
                                        ></textarea>
                                        <?=$externalField['name']?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'integer' || $externalField['type'] === 'numeric') { ?>
                                        <input
                                            name="<?=$externalField['code']?>"
                                            id="external_<?=$externalField['code']?>"
                                            type="number"
                                        >
                                        <?=$externalField['name']?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'email') { ?>
                                        <input
                                            name="<?=$externalField['code']?>"
                                            id="external_<?=$externalField['code']?>"
                                            type="email"
                                        >
                                        <?=$externalField['name']?>
                                    <?php } ?>

                                    <?php
                                    if ($externalField['type'] === 'dictionary') { ?>
                                        <select name="<?=$externalField['code']?>">
                                            <?php
                                            foreach ($externalField['dictionaryElements'] as $dictionaryElement) {
                                                ?>
                                                <option value="<?=$dictionaryElement['code']?>"><?=$dictionaryElement['name']?> </option>
                                                <?php
                                            }
                                            ?>
                                        </select>
                                        <?=$externalField['name']?>
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
    <?
    if (count($arResult["ERRORS"]) > 0):
        foreach ($arResult["ERRORS"] as $key => $error) {
            if (intval($key) == 0 && $key !== 0) {
                $arResult["ERRORS"][$key] = str_replace("#FIELD_NAME#", "&quot;" . GetMessage("REGISTER_FIELD_" . $key) . "&quot;", $error);
            }
        }

        ShowError(implode("<br />", $arResult["ERRORS"]));

    elseif ($arResult["USE_EMAIL_CONFIRMATION"] === "Y"):
    ?>
        <p><? echo GetMessage("REGISTER_EMAIL_WILL_BE_SENT") ?></p>
    <? endif ?>

    <? if ($arResult["SHOW_SMS_FIELD"] == true): ?>

        <form method="post" action="<?=POST_FORM_ACTION_URI?>" name="regform">
            <?
            if ($arResult["BACKURL"] <> ''):
                ?>
                <input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>"/>
            <?
            endif;
            ?>
            <input type="hidden" name="SIGNED_DATA" value="<?=htmlspecialcharsbx($arResult["SIGNED_DATA"])?>"/>
            <table>
                <tbody>
                <tr>
                    <td><? echo GetMessage("main_register_sms") ?><span class="starrequired">*</span></td>
                    <td><input size="30" type="text" name="SMS_CODE" value="<?=htmlspecialcharsbx($arResult["SMS_CODE"])?>" autocomplete="off"/></td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                    <td></td>
                    <td><input type="submit" name="code_submit_button" value="<? echo GetMessage("main_register_sms_send") ?>"/></td>
                </tr>
                </tfoot>
            </table>
        </form>

        <script>
            new BX.PhoneAuth({
                containerId:      'bx_register_resend',
                errorContainerId: 'bx_register_error',
                interval:         <?=$arResult["PHONE_CODE_RESEND_INTERVAL"]?>,
                data:
                                  <?=CUtil::PhpToJSObject([
                                      'signedData' => $arResult["SIGNED_DATA"],
                                  ])?>,
                onError:
                                  function(response) {
                                      var errorDiv        = BX('bx_register_error');
                                      var errorNode       = BX.findChildByClassName(errorDiv, 'errortext');
                                      errorNode.innerHTML = '';
                                      for (var i = 0; i < response.errors.length; i++) {
                                          errorNode.innerHTML = errorNode.innerHTML + BX.util.htmlspecialchars(response.errors[i].message) + '<br>';
                                      }
                                      errorDiv.style.display = '';
                                  }
            });
        </script>

        <div id="bx_register_error" style="display:none"><? ShowError("error") ?></div>

        <div id="bx_register_resend"></div>

    <? else: ?>

        <form method="post" action="<?=POST_FORM_ACTION_URI?>" name="regform" enctype="multipart/form-data">
            <?
            if ($arResult["BACKURL"] <> ''):
                ?>
                <input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>"/>
            <?
            endif;
            ?>

            <table>
                <thead>
                <tr>
                    <td colspan="2"><b><?=GetMessage("AUTH_REGISTER")?></b></td>
                </tr>
                </thead>
                <tbody>
                <? foreach ($arResult["SHOW_FIELDS"] as $FIELD): ?>
                    <? if ($FIELD == "AUTO_TIME_ZONE" && $arResult["TIME_ZONE_ENABLED"] == true): ?>
                        <tr>
                            <td><? echo GetMessage("main_profile_time_zones_auto") ?><? if ($arResult["REQUIRED_FIELDS_FLAGS"][$FIELD] == "Y"): ?><span class="starrequired">*</span><? endif ?></td>
                            <td>
                                <select name="REGISTER[AUTO_TIME_ZONE]" onchange="this.form.elements['REGISTER[TIME_ZONE]'].disabled=(this.value != 'N')">
                                    <option value=""><? echo GetMessage("main_profile_time_zones_auto_def") ?></option>
                                    <option value="Y"<?=$arResult["VALUES"][$FIELD] == "Y" ? " selected=\"selected\"" : ""?>><? echo GetMessage("main_profile_time_zones_auto_yes") ?></option>
                                    <option value="N"<?=$arResult["VALUES"][$FIELD] == "N" ? " selected=\"selected\"" : ""?>><? echo GetMessage("main_profile_time_zones_auto_no") ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><? echo GetMessage("main_profile_time_zones_zones") ?></td>
                            <td>
                                <select name="REGISTER[TIME_ZONE]"<? if (!isset($_REQUEST["REGISTER"]["TIME_ZONE"])) echo 'disabled="disabled"' ?>>
                                    <? foreach ($arResult["TIME_ZONE_LIST"] as $tz => $tz_name): ?>
                                        <option value="<?=htmlspecialcharsbx($tz)?>"<?=$arResult["VALUES"]["TIME_ZONE"]
                                        == $tz ? " selected=\"selected\"" : ""?>><?=htmlspecialcharsbx($tz_name)?></option>
                                    <? endforeach ?>
                                </select>
                            </td>
                        </tr>
                    <? else: ?>
                        <tr>
                            <td><?=GetMessage("REGISTER_FIELD_" . $FIELD)?>:<? if ($arResult["REQUIRED_FIELDS_FLAGS"][$FIELD] == "Y"): ?><span class="starrequired">*</span><? endif ?></td>
                            <td><?
                                switch ($FIELD) {
                                    case "PASSWORD":
                                        ?><input size="30" type="password" name="REGISTER[<?=$FIELD?>]" value="<?=$arResult["VALUES"][$FIELD]?>" autocomplete="off" class="bx-auth-input"/>
                                    <? if ($arResult["SECURE_AUTH"]): ?>
                                        <span class="bx-auth-secure" id="bx_auth_secure" title="<? echo GetMessage("AUTH_SECURE_NOTE") ?>" style="display:none">
					<div class="bx-auth-secure-icon"></div>
				</span>
                                        <noscript>
				<span class="bx-auth-secure" title="<? echo GetMessage("AUTH_NONSECURE_NOTE") ?>">
					<div class="bx-auth-secure-icon bx-auth-secure-unlock"></div>
				</span>
                                        </noscript>
                                        <script type="text/javascript">
                                            document.getElementById('bx_auth_secure').style.display = 'inline-block';
                                        </script>
                                    <? endif ?>
                                        <?
                                        break;
                                    case "CONFIRM_PASSWORD":
                                        ?><input size="30" type="password" name="REGISTER[<?=$FIELD?>]" value="<?=$arResult["VALUES"][$FIELD]?>" autocomplete="off" /><?
                                        break;

                                    case "PERSONAL_GENDER":
                                        ?><select name="REGISTER[<?=$FIELD?>]">
                                        <option value=""><?=GetMessage("USER_DONT_KNOW")?></option>
                                        <option value="M"<?=$arResult["VALUES"][$FIELD] == "M" ? " selected=\"selected\"" : ""?>><?=GetMessage("USER_MALE")?></option>
                                        <option value="F"<?=$arResult["VALUES"][$FIELD] == "F" ? " selected=\"selected\"" : ""?>><?=GetMessage("USER_FEMALE")?></option>
                                        </select><?
                                        break;

                                    case "PERSONAL_COUNTRY":
                                    case "WORK_COUNTRY":
                                        ?><select name="REGISTER[<?=$FIELD?>]"><?
                                        foreach ($arResult["COUNTRIES"]["reference_id"] as $key => $value) {
                                            ?>
                                            <option value="<?=$value?>"<? if ($value
                                                == $arResult["VALUES"][$FIELD]): ?> selected="selected"<? endif ?>><?=$arResult["COUNTRIES"]["reference"][$key]?></option>
                                            <?
                                        }
                                        ?></select><?
                                        break;

                                    case "PERSONAL_PHOTO":
                                    case "WORK_LOGO":
                                        ?><input size="30" type="file" name="REGISTER_FILES_<?=$FIELD?>" /><?
                                        break;

                                    case "PERSONAL_NOTES":
                                    case "WORK_NOTES":
                                        ?><textarea cols="30" rows="5" name="REGISTER[<?=$FIELD?>]"><?=$arResult["VALUES"][$FIELD]?></textarea><?
                                        break;
                                    default:
                                        if ($FIELD == "PERSONAL_BIRTHDAY"):?><small><?=$arResult["DATE_FORMAT"]?></small><br/><?endif;
                                        ?><input size="30" type="text" name="REGISTER[<?=$FIELD?>]" value="<?=$arResult["VALUES"][$FIELD]?>" /><?
                                        if ($FIELD == "PERSONAL_BIRTHDAY") {
                                            $APPLICATION->IncludeComponent(
                                                'bitrix:main.calendar',
                                                '',
                                                [
                                                    'SHOW_INPUT' => 'N',
                                                    'FORM_NAME'  => 'regform',
                                                    'INPUT_NAME' => 'REGISTER[PERSONAL_BIRTHDAY]',
                                                    'SHOW_TIME'  => 'N',
                                                ],
                                                null,
                                                ["HIDE_ICONS" => "Y"]
                                            );
                                        }
                                        ?><?
                                } ?></td>
                        </tr>
                    <? endif ?>
                <? endforeach ?>
                <? // ********************* User properties ***************************************************?>
                <? if ($arResult["USER_PROPERTIES"]["SHOW"] == "Y"): ?>
                    <tr>
                        <td colspan="2"><?=strlen(trim($arParams["USER_PROPERTY_NAME"])) > 0 ? $arParams["USER_PROPERTY_NAME"] : GetMessage("USER_TYPE_EDIT_TAB")?></td>
                    </tr>
                    <? foreach ($arResult["USER_PROPERTIES"]["DATA"] as $FIELD_NAME => $arUserField): ?>
                        <tr>
                            <td><?=$arUserField["EDIT_FORM_LABEL"]?>:<? if ($arUserField["MANDATORY"] == "Y"): ?><span class="starrequired">*</span><? endif; ?></td>
                            <td>
                                <? $APPLICATION->IncludeComponent(
                                    "bitrix:system.field.edit",
                                    $arUserField["USER_TYPE"]["USER_TYPE_ID"],
                                    ["bVarsFromForm" => $arResult["bVarsFromForm"], "arUserField" => $arUserField, "form_name" => "regform"], null, ["HIDE_ICONS" => "Y"]); ?></td>
                        </tr>
                    <? endforeach; ?>
                <? endif; ?>
                <?php if ($arResult['LOYALTY_STATUS'] === 'Y'): ?>
                    <tr>
                        <td></td>
                        <td>
                            <div class="fields boolean" id="main_UF_REG_IN_PL_INTARO">
                                <div class="fields boolean">
                                    <input type="hidden" value="0" name="UF_REG_IN_PL_INTARO">
                                    <label>
                                        <input type="checkbox" value="1" name="UF_REG_IN_PL_INTARO" onchange="lpFieldToggle()" id="checkbox_UF_REG_IN_PL_INTARO"> <?=GetMessage("UF_REG_IN_PL_INTARO")?>
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr class="lp_toggled_block" style="display: none">
                        <td><?=GetMessage("BONUS_CARD_NUMBER")?></td>
                        <td><input size="30" type="text" name="UF_CARD_NUM_INTARO" value=""></td>
                    </tr>
                    <tr class="lp_toggled_block" style="display: none">
                        <td>
                            <?=GetMessage("REGISTER_FIELD_PERSONAL_PHONE")?>
                        </td>
                        <td>
                            <input size="30" type="text" name="REGISTER[PERSONAL_PHONE]" value>
                        </td>
                    </tr>
                    <tr class="lp_toggled_block" style="display: none">
                        <td>
                            <div class="fields boolean" id="main_UF_AGREE_PL_INTARO">
                                <div class="fields boolean">
                                    <input type="hidden" value="0" name="UF_AGREE_PL_INTARO">
                                    <label>
                                        <input class="lp_agree_checkbox" type="checkbox" value="1" name="UF_AGREE_PL_INTARO"> <?=GetMessage("YES")?>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?=GetMessage("I_AM_AGREE")?> <a class="lp_agreement_link" href="javascript:void(0)"><?=GetMessage("UF_AGREE_PL_INTARO")?></a>
                        </td>
                    </tr>
                    <tr class="lp_toggled_block" style="display: none">
                        <td>
                            <div class="fields boolean" id="main_UF_PD_PROC_PL_INTARO">
                                <div class="fields boolean"><input type="hidden" value="0" name="UF_PD_PROC_PL_INTARO">
                                    <label>
                                        <input class="lp_agree_checkbox" type="checkbox" value="1" name="UF_PD_PROC_PL_INTARO"> <?=GetMessage("YES")?>
                                    </label>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?=GetMessage("I_AM_AGREE")?> <a class="personal_data_agreement_link" href="javascript:void(0)"><?=GetMessage("UF_PD_PROC_PL_INTARO")?></a>
                        </td>
                    </tr>
                <?php endif; ?>
                <? // ******************** /User properties ***************************************************?>
                <?
                /* CAPTCHA */
                if ($arResult["USE_CAPTCHA"] == "Y") {
                    ?>
                    <tr>
                        <td colspan="2"><b><?=GetMessage("REGISTER_CAPTCHA_TITLE")?></b></td>
                    </tr>
                    <tr>
                        <td></td>
                        <td>
                            <input type="hidden" name="captcha_sid" value="<?=$arResult["CAPTCHA_CODE"]?>"/>
                            <img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA"/>
                        </td>
                    </tr>
                    <tr>
                        <td><?=GetMessage("REGISTER_CAPTCHA_PROMT")?>:<span class="starrequired">*</span></td>
                        <td><input type="text" name="captcha_word" maxlength="50" value="" autocomplete="off"/></td>
                    </tr>
                    <?
                }
                /* !CAPTCHA */
                ?>
                </tbody>
                <tfoot>
                <tr>
                    <td></td>
                    <td><input type="submit" name="register_submit_button" value="<?=GetMessage("AUTH_REGISTER")?>"/></td>
                </tr>
                </tfoot>
            </table>
        </form>

        <p><? echo $arResult["GROUP_POLICY"]["PASSWORD_REQUIREMENTS"]; ?></p>

    <? endif //$arResult["SHOW_SMS_FIELD"] == true ?>

        <p><span class="starrequired">*</span><?=GetMessage("AUTH_REQ")?></p>

    <? endif ?>
</div>
