<?php
if (!check_bitrix_sessid())
    return;
IncludeModuleLangFile(__FILE__);

$defaultOrderProps = array(
    'fio'   => 'FIO',
    'index' => 'ZIP',
    'text'  => 'ADDRESS',
    'phone' => 'PHONE',
    'email' => 'EMAIL'
);

?>
<script type="text/javascript" src="/bitrix/js/main/jquery/jquery-1.7.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() { 
        $('input[name="address-detail"]').change(function(){  
            if(parseInt($(this).val()) === 1)
                $('tr.address-detail').show('slow');
            else if(parseInt($(this).val()) === 0)
                $('tr.address-detail').hide('slow');
                
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
            <?php $countProps = 0;
            foreach ($arResult['orderProps'] as $orderProp): ?>
            <?php if ($orderProp['ID'] == 'text'): ?>
            <tr class="heading">
                <td colspan="2">
                    <b>
                        <label><input type="radio" name="address-detail" value="0" <?php if (count($defaultOrderProps) < 6) echo "checked"; ?>><?php echo GetMessage('ADDRESS_SHORT'); ?></label>
                        <label><input type="radio" name="address-detail" value="1" <?php if (count($defaultOrderProps) > 5) echo "checked"; ?>><?php echo GetMessage('ADDRESS_FULL'); ?></label>
                    </b>
                </td>
            </tr>
            <?php endif; ?>
            <tr <?php if ($countProps > 5) echo 'class="address-detail"'; if (($countProps > 5) && (count($defaultOrderProps) < 6)) echo 'style="display:none;"'; ?>>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $orderProp['ID']; ?>">
                    <?php echo $orderProp['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="order-prop-<?php echo $orderProp['ID']; ?>" class="typeselect">
                        <option value=""></option>              
                            <?php foreach ($arResult['arProp'] as $arProp): ?>
                                <option value="<?php echo $arProp['CODE']; ?>" <?php if ($defaultOrderProps[$orderProp['ID']] == $arProp['CODE']) echo 'selected'; ?>>
                                    <?php echo $arProp['NAME']; ?>
                                </option>
                            <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php $countProps++; endforeach; ?>
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