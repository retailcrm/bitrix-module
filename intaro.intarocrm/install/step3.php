<?php
if (!check_bitrix_sessid())
    return;
IncludeModuleLangFile(__FILE__);

$defaultOrderProps = array(
    1 => array(
        'fio' => 'FIO',
        'index' => 'ZIP',
        'text' => 'ADDRESS',
        'phone' => 'PHONE',
        'email' => 'EMAIL'
    ),
    2 => array(
        'fio' => 'FIO',
        'index' => 'ZIP',
        'text' => 'ADDRESS',
        'phone' => 'PHONE',
        'email' => 'EMAIL'
    )
);

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
     });
</script>

<div class="adm-detail-content-item-block">
<form action="<?php echo $APPLICATION->GetCurPage() ?>" method="POST">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="intaro.intarocrm">
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
            <?php $countProps = 0; foreach($arResult['orderProps'] as $orderProp): ?>
            <?php if($orderProp['ID'] == 'text'): ?>
            <tr class="heading">
                <td colspan="2" style="background-color: transparent;">
                    <b>
                        <label><input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID'];; ?>" value="0" <?php if(count($defaultOrderProps[$bitrixOrderType['ID']]) < 6) echo "checked"; ?>><?php echo GetMessage('ADDRESS_SHORT'); ?></label>
                        <label><input class="addr" type="radio" name="address-detail-<?php echo $bitrixOrderType['ID']; ?>" value="1" <?php if(count($defaultOrderProps[$bitrixOrderType['ID']]) > 5) echo "checked"; ?>><?php echo GetMessage('ADDRESS_FULL'); ?></label>
                    </b>
                </td>
            </tr>
            <?php endif; ?>
            <tr <?php if ($countProps > 4) echo 'class="address-detail-' . $bitrixOrderType['ID'] . '"'; if(($countProps > 4) && (count($defaultOrderProps[$bitrixOrderType['ID']]) < 6)) echo 'style="display:none;"';?>>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $orderProp['ID']; ?>">
                    <?php echo $APPLICATION->ConvertCharset($orderProp['NAME'], 'utf-8', SITE_CHARSET);; ?>
                </td>
        <td width="50%" class="adm-detail-content-cell-r">
            <select name="order-prop-<?php echo $orderProp['ID'] . '-' . $bitrixOrderType['ID']; ?>" class="typeselect">
                <option value=""></option>              
                <?php foreach ($arResult['arProp'][$bitrixOrderType['ID']] as $arProp): ?>
                <option value="<?php echo $arProp['CODE']; ?>" <?php if ($defaultOrderProps[$bitrixOrderType['ID']][$orderProp['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                    <?php echo $APPLICATION->ConvertCharset($arProp['NAME'], 'utf-8', SITE_CHARSET); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <?php $countProps++; endforeach; ?>
    <?php endforeach; ?>
        </tbody>
    </table>
    <br />
    <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; width:50%; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
        <div align="left" style="float:right; width:50%; position:relative; visible: none;">
            <input type="submit" name="start" value="<?php echo GetMessage("MOD_PREV_STEP"); ?>" class="adm-btn-save">
        </div>
    </div>
</form>
</div>