<?php
if (!check_bitrix_sessid())
    return;
IncludeModuleLangFile(__FILE__);

if(isset($arResult['ORDER_PROPS'])){
    $defaultOrderProps = $arResult['ORDER_PROPS'];
}
else{
    $defaultOrderProps = array(
        1 => array(
            'fio' => 'FIO',
            'index' => 'ZIP',
            'text' => 'ADDRESS',
            'phone' => 'PHONE',
            'email' => 'EMAIL'
        ),
        2 => array(
            'fio' => 'CONTACT_PERSON',
            'index' => 'ZIP',
            'text' => 'ADDRESS',
            'phone' => 'PHONE',
            'email' => 'EMAIL'
        )
    );
}
?>
<script type="text/javascript" src="/bitrix/js/main/jquery/jquery-1.7.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() { 
        $('input.addr').change(function(){
            splitName = $(this).attr('name').split('-');
            orderType = splitName[2];
            
            if(parseInt($(this).val()) === 1)
                $('tr.address-detail-' + orderType).show('slow');
            else if(parseInt($(this).val()) === 0)
                $('tr.address-detail-' + orderType).hide('slow');
        });
        
        $('tr.contragent-type select').change(function(){
            splitName = $(this).attr('name').split('-');
            contragentType = $(this).val();
            orderType = splitName[2];
            
            $('tr.legal-detail-' + orderType).hide();
            $('.legal-detail-title-' + orderType).hide();

            $('tr.legal-detail-' + orderType).each(function(){
                if($(this).hasClass(contragentType)){
                    $(this).show();
                    $('.legal-detail-title-' + orderType).show();
                }
            });
        });
     });
</script>

<div class="adm-detail-content-item-block">
<form action="<?php echo $APPLICATION->GetCurPage() ?>" method="POST">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="intaro.retailcrm">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="4">
    <input type="hidden" name="continue" value="3">

    <table class="adm-detail-content-table edit-table" id="edit1_edit_table">
        <tbody>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('STEP_NAME'); ?></b></td>
            </tr>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('ORDER_PROPS'); ?></b></td>
            </tr>
            <tr align="center">
                <td colspan="2"><b><?php echo GetMessage('INFO_2'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixOrderTypesList'] as $bitrixOrderType): ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('ORDER_TYPE_INFO') . ' ' . $bitrixOrderType['NAME']; ?></b></td>
            </tr>
            <tr class="contragent-type">
                <td width="50%" class="adm-detail-content-cell-l">
                    <?php echo GetMessage('CONTRAGENT_TYPE'); ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="contragent-type-<?php echo $bitrixOrderType['ID']; ?>" class="typeselect">         
                        <?php foreach ($arResult['contragentType'] as $contragentType): ?>
                        <option value="<?php echo $contragentType["ID"]; ?>" <?php if (isset($arResult['CONTRAGENT_TYPES'][$bitrixOrderType['ID']]) && $arResult['CONTRAGENT_TYPES'][$bitrixOrderType['ID']] == $contragentType["ID"]) echo 'selected'; ?>>
                            <?php echo $contragentType["NAME"]; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <?php $countProps = 0; foreach($arResult['orderProps'] as $orderProp): ?>
            <?php if($orderProp['ID'] == 'text'): ?>
            <tr class="heading">
                <td colspan="2" style="background-color: transparent;">
                    <b>
                        <label><input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="0" <?php if(count($defaultOrderProps[$bitrixOrderType['ID']]) < 6) echo "checked"; ?>><?php echo GetMessage('ADDRESS_SHORT'); ?></label>
                        <label><input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="1" <?php if(count($defaultOrderProps[$bitrixOrderType['ID']]) > 5) echo "checked"; ?>><?php echo GetMessage('ADDRESS_FULL'); ?></label>
                    </b>
                </td>
            </tr>
            <?php endif; ?>
            
            <tr <?php if ($countProps > 3) echo 'class="address-detail-' . $bitrixOrderType['ID'] . '"'; if(($countProps > 3) && (count($defaultOrderProps[$bitrixOrderType['ID']]) < 6)) echo 'style="display:none;"';?>>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $orderProp['ID']; ?>">
                    <?php echo $orderProp['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="order-prop-<?php echo $orderProp['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                        <option value=""></option>              
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                        <option value="<?php echo $arProp['CODE']; ?>" <?php if ($defaultOrderProps[$bitrixOrderType['ID']][$orderProp['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                            <?php echo $arProp['NAME']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php $countProps++; endforeach; ?>

            <?if (isset($arResult['customFields']) && count($arResult['customFields']) > 0):?>
                <tr class="heading custom-detail-title">
                    <td colspan="2" style="background-color: transparent;">
                        <b>
                            <?=GetMessage("ORDER_CUSTOM"); ?>
                        </b>
                    </td>
                </tr>
                <?foreach($arResult['customFields'] as $customFields):?>
                    <tr class="custom-detail-<?=$customFields['ID'];?>">
                        <td width="50%" class="" name="">
                            <?=$customFields['NAME']; ?>
                        </td>
                        <td width="50%" class="">
                            <select name="custom-fields-<?=$customFields['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                                <option value=""></option>              
                                <?foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp):?>
                                    <option value="<?=$arProp['CODE']?>" <?php if (isset($arResult['CUSTOM_FIELDS'][$bitrixOrderType['ID']][$customFields['ID']]) && $arResult['CUSTOM_FIELDS'][$bitrixOrderType['ID']][$customFields['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                                    <?=$arProp['NAME']; ?>
                                    </option>
                                <?endforeach;?>
                            </select>
                        </td>
                    </tr>
                <?endforeach;?>
            <?endif;?>
    
            <tr class="heading legal-detail-title-<?php echo $bitrixOrderType['ID'];?>" style="display:none">
                <td colspan="2" style="background-color: transparent;">
                    <b>
                        <?php echo GetMessage("ORDER_LEGAL_INFO"); ?>
                    </b>
                </td>
            </tr>
            
            <?php foreach($arResult['legalDetails'] as $legalDetails): ?>
            <tr class="legal-detail-<?php echo $bitrixOrderType['ID'];?> <?php foreach($legalDetails['GROUP'] as $gr) echo $gr . ' ';?>" style="display:none">
                <td width="50%" class="adm-detail-content-cell-l">
                    <?php echo $legalDetails['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="legal-detail-<?php echo $legalDetails['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                        <option value=""></option>              
                        <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                        <option value="<?php echo $arProp['CODE']; ?>" <?php if (isset($arResult['LEGAL_DETAILS'][$bitrixOrderType['ID']][$legalDetails['ID']]) && $arResult['LEGAL_DETAILS'][$bitrixOrderType['ID']][$legalDetails['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                            <?php echo $arProp['NAME']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>   
            <?php endforeach; ?>

            <?php endforeach; ?>
        </tbody>
    </table>
    <br />
    <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; width:50%; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
        <div align="left" style="float:right; width:50%; position:relative; visible: none;">
            <input type="submit" name="back" value="<?php echo GetMessage("MOD_PREV_STEP"); ?>" class="adm-btn-save">
        </div>
    </div>
</form>
</div>