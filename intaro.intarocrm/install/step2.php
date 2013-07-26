<?php
IncludeModuleLangFile(__FILE__);

//bitrix pyament Y/N
$arResult['bitrixPaymentList'][0]['NAME'] = GetMessage('PAYMENT_Y');
$arResult['bitrixPaymentList'][0]['ID'] = 'Y';
$arResult['bitrixPaymentList'][1]['NAME'] = GetMessage('PAYMENT_N');
$arResult['bitrixPaymentList'][1]['ID'] = 'N';

$defaultOrderTypes = array (
    1 => 'eshop-individual',
    2 => 'eshop-legal'
);

$defaultDelivTypes = array (
    1 => 'courier',
    2 => 'self-delivery'
);

$defaultPayTypes = array (
    1 => 'cash',
    5 => 'bank-card',
    6 => 'bank-transfer'
);

$defaultPayStatuses = array (
    'N' => 'new',
    'P' => 'approval',
    'F' => 'complete',
    'Y' => 'cancel-other'
);

$defaultPayment = array(
    'Y' => 'paid',
    'N' => 'not-paid'   
);

?>

<div class="adm-detail-content-item-block">
<form action="<?php echo $APPLICATION->GetCurPage() ?>" method="POST">
    <?php echo bitrix_sessid_post(); ?>
    <input type="hidden" name="lang" value="<?php echo LANGUAGE_ID ?>">
    <input type="hidden" name="id" value="intaro.intarocrm">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="3">

    <table class="adm-detail-content-table edit-table" id="edit1_edit_table">
        <tbody>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('STEP_NAME'); ?></b></td>
            </tr>
            <tr align="center">
                <td colspan="2"><b><?php echo GetMessage('INFO_1'); ?></b></td>
            </tr>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('DELIVERY_TYPES_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixDeliveryTypesList'] as $bitrixDeliveryType): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixDeliveryType['ID']; ?>">
		<?php echo $bitrixDeliveryType['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="delivery-type-<?php echo $bitrixDeliveryType['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach($arResult['deliveryTypesList'] as $deliveryType): ?>
                        <option value="<?php echo $deliveryType['code']; ?>" 
                            <?php if($defaultDelivTypes[$bitrixDeliveryType['ID']] == $deliveryType['code']) echo 'selected'; ?>>
                            <?php echo $APPLICATION->ConvertCharset($deliveryType['name'], 'utf-8', SITE_CHARSET); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_TYPES_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixPaymentTypesList'] as $bitrixPaymentType): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPaymentType['ID']; ?>">
                <?php echo $bitrixPaymentType['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="payment-type-<?php echo $bitrixPaymentType['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach($arResult['paymentTypesList'] as $paymentType): ?>
                        <option value="<?php echo $paymentType['code']; ?>" 
                            <?php if($defaultPayTypes[$bitrixPaymentType['ID']] == $paymentType['code']) echo 'selected'; ?>>
                            <?php echo $APPLICATION->ConvertCharset($paymentType['name'], 'utf-8', SITE_CHARSET); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_STATUS_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixPaymentStatusesList'] as $bitrixPaymentStatus): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPaymentStatus['ID']; ?>">
                    <?php echo $bitrixPaymentStatus['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="payment-status-<?php echo $bitrixPaymentStatus['ID']; ?>" class="typeselect">
                        <option value="" selected=""></option>
                        <?php foreach($arResult['paymentGroupList'] as $orderStatusGroup): if(!empty($orderStatusGroup['statuses'])) : ?>
                        <optgroup label="<?php echo $orderStatusGroup['name']; ?>">
                            <?php foreach($orderStatusGroup['statuses'] as $payment): ?>
                            <option value="<?php echo $arResult['paymentList'][$payment]['code']; ?>" 
                                <?php if ($defaultPayStatuses[$bitrixPaymentStatus['ID']] == $arResult['paymentList'][$payment]['code']) echo 'selected'; ?>>
                                <?php echo $APPLICATION->ConvertCharset($arResult['paymentList'][$payment]['name'], 'utf-8', SITE_CHARSET); ?>
                            </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php endif; endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('PAYMENT_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixPaymentList'] as $bitrixPayment): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixPayment['ID']; ?>">
                    <?php echo $bitrixPayment['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="payment-<?php echo $bitrixPayment['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach($arResult['paymentStatusesList'] as $paymentStatus): ?>
                        <option value="<?php echo $paymentStatus['code']; ?>" 
                            <?php if($defaultPayment[$bitrixPayment['ID']] == $paymentStatus['code']) echo 'selected'; ?>>
                            <?php echo $APPLICATION->ConvertCharset($paymentStatus['name'], 'utf-8', SITE_CHARSET); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr class="heading">
                <td colspan="2"><b><?php echo GetMessage('ORDER_TYPES_LIST'); ?></b></td>
            </tr>
            <?php foreach($arResult['bitrixOrderTypesList'] as $bitrixOrderType): ?>
            <tr>
                <td width="50%" class="adm-detail-content-cell-l" name="<?php echo $bitrixOrderType['ID']; ?>">
                    <?php echo $bitrixOrderType['NAME']; ?>
                </td>
                <td width="50%" class="adm-detail-content-cell-r">
                    <select name="order-type-<?php echo $bitrixOrderType['ID']; ?>" class="typeselect">
                        <option value=""></option>
                        <?php foreach($arResult['orderTypesList'] as $orderType): ?>
                        <option value="<?php echo $orderType['code']; ?>" 
                            <?php if($defaultOrderTypes[$bitrixOrderType['ID']] == $orderType['code']) echo 'selected'; ?>>
                            <?php echo $APPLICATION->ConvertCharset($orderType['name'], 'utf-8', SITE_CHARSET); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br />
     <div style="padding: 1px 13px 2px; height:28px;">
        <div align="right" style="float:right; width:50%; position:relative;">
            <input type="submit" name="inst" value="<?php echo GetMessage("MOD_NEXT_STEP"); ?>" class="adm-btn-save">
        </div>
        <div align="left" style="float:right; width:50%; position:relative;">
            <input type="submit" name="back" value="<?php echo GetMessage("MOD_PREV_STEP"); ?>" class="adm-btn-save">
        </div>
    </div>
</form>
</div>