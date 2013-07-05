<?php 
IncludeModuleLangFile(__FILE__);
$mid = 'intaro.crm';
$uri = $APPLICATION->GetCurPage() . '?mid=' . htmlspecialchars($mid) . '&lang=' . LANGUAGE_ID;

CModule::IncludeModule('intaro.crm');
CModule::IncludeModule('sale');

$_GET['errc'] = htmlspecialchars(trim($_GET['errc']));
$_GET['ok'] = htmlspecialchars(trim($_GET['ok']));

if($_GET['errc']) echo CAdminMessage::ShowMessage(GetMessage($_GET['errc']));
if($_GET['ok'] && $_GET['ok'] == 'Y') echo CAdminMessage::ShowNote(GetMessage('ICRM_OPTIONS_OK'));

$arResult = array();

//update connection settings
if (isset($_POST['Update']) && $_POST['Update']=='Y') {
    $api_host = htmlspecialchars(trim($_POST['api_host']));
    $api_key = htmlspecialchars(trim($_POST['api_key']));
            
    if($api_host && $api_key) {
        $api = new IntaroCrmRestApi($api_host, $api_key);
            
        $api->paymentStatusesList();
            
        //check connection & apiKey valid
        if((int) $api->getStatusCode() != 200) {
            $uri .= '&errc=ERR_' . $api->getStatusCode();
            LocalRedirect($uri);
        } else {
            COption::SetOptionString($mid, 'api_host', $api_host);
            COption::SetOptionString($mid, 'api_key', $api_key);
        }   
    }
	
    //bitrix deliveryTypesList
    $dbDeliveryTypesList = CSaleDelivery::GetList(
        array(
            "SORT" => "ASC",
            "NAME" => "ASC"
        ),
        array(
             "ACTIVE" => "Y",
        ),
        false,
        false,
        array()
     );
            
     //form delivery types ids arr
	 $deliveryTypesArr = array();
     if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
         do {
            $deliveryTypesArr[$arDeliveryTypesList['ID']] = $_POST['delivery-type-' . $arDeliveryTypesList['ID']];   
         } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
      }
		    
      //bitrix paymentTypesList
      $dbPaymentTypesList = CSalePaySystem::GetList(
          array(
              "SORT" => "ASC", 
              "NAME" => "ASC"
          ), 
          array(
              "ACTIVE" => "Y"
          )
      );
        
      //form payment types ids arr
      $paymentTypesArr = array();
      if ($arPaymentTypesList = $dbPaymentTypesList->Fetch()) {
          do {
              $paymentTypesArr[$arPaymentTypesList['ID']] = $_POST['payment-type-' . $arPaymentTypesList['ID']];         
          } while ($arPaymentTypesList = $dbPaymentTypesList->Fetch());
      }
                
      //bitrix paymentStatusesList
      $dbPaymentStatusesList = CSaleStatus::GetList(
          array(
              "SORT" => "ASC", 
              "NAME" => "ASC"
          ), 
          array(
              "LID" => "ru", //ru 
              "ACTIVE" => "Y"
          )
      );
            
      //form payment statuses ids arr
      $paymentStatusesArr = array();
      if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
          do {
              $paymentStatusesArr[$arPaymentStatusesList['ID']] = $_POST['payment-status-' . $arPaymentStatusesList['ID']];     
          } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
      }
		    
      COption::SetOptionString($mid, 'deliv_types_arr', serialize($deliveryTypesArr));
      COption::SetOptionString($mid, 'pay_types_arr', serialize($paymentTypesArr));
      COption::SetOptionString($mid, 'pay_statuses_arr', serialize($paymentStatusesArr));
      
      $uri .= '&ok=Y';
      LocalRedirect($uri);
} else {
    $api_host = COption::GetOptionString($mid, 'api_host', 0);
    $api_key = COption::GetOptionString($mid, 'api_key', 0);

    $api = new IntaroCrmRestApi($api_host, $api_key);

    //prepare crm lists
    //$orderTypes = $api->orderTypesList(); -- no such method
    $arResult['deliveryTypesList'] = $api->deliveryTypesList();
    $arResult['paymentTypesList'] = $api->paymentTypesList();
    $arResult['paymentStatusesList'] = $api->paymentStatusesList();

    //bitrix orderTypesList
    /*
     *  ...some code here...           
     */

    //bitrix deliveryTypesList
    $dbDeliveryTypesList = CSaleDelivery::GetList(
        array(
            "SORT" => "ASC",
            "NAME" => "ASC"
        ),
        array(
             "ACTIVE" => "Y",
        ), 
        false, 
        false, 
        array()
    );

    if ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch()) {
        do {
            $arResult['bitrixDeliveryTypesList'][] = $arDeliveryTypesList;
        } while ($arDeliveryTypesList = $dbDeliveryTypesList->Fetch());
    }

    //bitrix paymentTypesList
    $dbPaymentTypesList = CSalePaySystem::GetList(
        array(
            "SORT" => "ASC",
            "NAME" => "ASC"
        ),
        array(
             "ACTIVE" => "Y"
        )
    );

    if ($arPaymentTypesList = $dbPaymentTypesList->Fetch()) {
        do {
            $arResult['bitrixPaymentTypesList'][] = $arPaymentTypesList;
        } while ($arPaymentTypesList = $dbPaymentTypesList->Fetch());
    }

    //bitrix paymentStatusesList
    $dbPaymentStatusesList = CSaleStatus::GetList(
        array(
            "SORT" => "ASC",
            "NAME" => "ASC"
        ),
        array(
            "LID" => "ru", //ru 
            "ACTIVE" => "Y"
        )
    );

    if ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch()) {
        do {
            $arResult['bitrixPaymentStatusesList'][] = $arPaymentStatusesList;
        } while ($arPaymentStatusesList = $dbPaymentStatusesList->Fetch());
    }

    //saved cat params
    $optionsDelivTypes = unserialize(COption::GetOptionString($mid, 'deliv_types_arr', 0));
    $optionsPayTypes = unserialize(COption::GetOptionString($mid, 'pay_types_arr', 0));
    $optionsPayStatuses = unserialize(COption::GetOptionString($mid, 'pay_statuses_arr', 0));

    $aTabs = array(
        array(
            "DIV" => "edit1",
            "TAB" => GetMessage('ICRM_OPTIONS_GENERAL_TAB'),
            "ICON" => "",
            "TITLE" => GetMessage('ICRM_OPTIONS_GENERAL_CAPTION')
        ),
        array(
            "DIV" => "edit2",
            "TAB" => GetMessage('ICRM_OPTIONS_CATALOG_TAB'),
            "ICON" => '',
            "TITLE" => GetMessage('ICRM_OPTIONS_IMPORT_CAPTION')
        ),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);
    $tabControl->Begin();
?>
<form method="POST" action="<?php echo $uri; ?>" id="FORMACTION">
<?php 
    echo bitrix_sessid_post();
	$tabControl->BeginNextTab();
?>
    <input type="hidden" name="tab" value="catalog">
    <tr class="heading">
        <td colspan="2"><b><?php echo GetMessage('ICRM_CONN_SETTINGS'); ?></b></td>
    </tr>
    <tr>
        <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_HOST'); ?></td>
        <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_host" name="api_host" value="<?php echo $api_host; ?>"></td>
    </tr>
    <tr>
        <td width="50%" class="adm-detail-content-cell-l"><?php echo GetMessage('ICRM_API_KEY'); ?></td>
        <td width="50%" class="adm-detail-content-cell-r"><input type="text" id="api_key" name="api_key" value="<?php echo $api_key; ?>"></td>
    </tr>
<?php $tabControl->BeginNextTab(); ?>
    <input type="hidden" name="tab" value="catalog">
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
                <option value="<?php echo $deliveryType['code']; ?>" <?php if ($optionsDelivTypes[$bitrixDeliveryType['ID']] == $deliveryType['code']) echo 'selected'; ?>>
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
                <option value="" selected=""></option>
                <?php foreach($arResult['paymentTypesList'] as $paymentType): ?>
                <option value="<?php echo $paymentType['code']; ?>" <?php if ($optionsPayTypes[$bitrixPaymentType['ID']] == $paymentType['code']) echo 'selected'; ?>>
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
                <?php foreach($arResult['paymentStatusesList'] as $paymentStatus): ?>
                <option value="<?php echo $paymentStatus['code']; ?>" <?php if ($optionsPayStatuses[$bitrixPaymentStatus['ID']] == $paymentStatus['code']) echo 'selected'; ?>>
                    <?php echo $APPLICATION->ConvertCharset($paymentStatus['name'], 'utf-8', SITE_CHARSET); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
<?php endforeach; ?>
<?php $tabControl->BeginNextTab(); ?>
<?php $tabControl->Buttons(); ?>
<input type="hidden" name="Update" value="Y" />
<input type="submit" title="<?php echo GetMessage('ICRM_OPTIONS_SUBMIT_TITLE'); ?>" value="<?php echo GetMessage('ICRM_OPTIONS_SUBMIT_VALUE'); ?>" name="btn-update" class="adm-btn-save" />
<?php $tabControl->End(); ?>
</form>

<?php } ?>
