<?
/*
 * Old profile variables
 * 
 * IBLOCK_EXPORT[0]=3&
 * IBLOCK_EXPORT[1]=6&
 * IBLOCK_PROPERTY_ARTICLE[0]=ARTICLE&
 * IBLOCK_PROPERTY_ARTICLE[1]=ARTNUMBER&
 * SETUP_FILE_NAME=%2Fbitrix%2Fcatalog_export%2Ftestintarocrm.xml
 */

/*
 * New profile variables
 * 
 * IBLOCK_EXPORT[3]=3&
 * IBLOCK_EXPORT[6]=6&
 * 
 * IBLOCK_PROPERTY_SKU[3][article]=&
 * IBLOCK_PROPERTY_SKU[3][manufacturer]=&
 * IBLOCK_PROPERTY_SKU[3][color]=&
 * IBLOCK_PROPERTY_SKU[3][weight]=&
 * IBLOCK_PROPERTY_SKU[3][size]=&
 * 
 * IBLOCK_PROPERTY_SKU[6][article]=&
 * IBLOCK_PROPERTY_SKU[6][manufacturer]=&
 * IBLOCK_PROPERTY_SKU[6][color]=&
 * IBLOCK_PROPERTY_SKU[6][weight]=&
 * IBLOCK_PROPERTY_SKU[6][size]=&
 * 
 * IBLOCK_PROPERTY_PRODUCT[3][article]=ARTNUMBER&
 * IBLOCK_PROPERTY_PRODUCT[3][manufacturer]=&
 * IBLOCK_PROPERTY_PRODUCT[3][color]=&
 * IBLOCK_PROPERTY_PRODUCT[3][weight]=&
 * IBLOCK_PROPERTY_PRODUCT[3][size]=&
 * 
 * IBLOCK_PROPERTY_PRODUCT[6][article]=ART&
 * IBLOCK_PROPERTY_PRODUCT[6][manufacturer]=&
 * IBLOCK_PROPERTY_PRODUCT[6][color]=&
 * IBLOCK_PROPERTY_PRODUCT[6][weight]=&
 * IBLOCK_PROPERTY_PRODUCT[6][size]=&
 * 
 * SETUP_FILE_NAME=%2Fbitrix%2Fcatalog_export%2Fintarocrm.xml
 */

if (!CModule::IncludeModule("iblock"))
    return;
if (!CModule::IncludeModule("catalog"))
    return;

$dbProfile = CCatalogExport::GetList(
    array(),
    array("!DEFAULT_PROFILE" => "Y", "FILE_NAME" => "intarocrm")
);
while ($arProfile = $dbProfile->Fetch())
{
    $PROFILE_ID = intval($arProfile["ID"]);

    parse_str($arProfile['SETUP_VARS']);
    
    $propertiesSKU = Array();
    $propertiesProduct = Array();
    
    foreach ($IBLOCK_EXPORT as $iblock) {
        
        $propertiesSKU[$iblock] = Array(
            "article"       => null,
            "manufacturer"  => null,
            "color"         => null,
            "weight"        => null,
            "size"          => null,
        );

        $propertiesProduct[$iblock] = Array(
            "article"       => $IBLOCK_PROPERTY_ARTICLE[$iblock],
            "manufacturer"  => null,
            "color"         => null,
            "weight"        => null,
            "size"          => null,
        );
    }
    
    $strVars = GetProfileSetupVars($IBLOCK_EXPORT, $propertiesProduct, $propertiesSKU, $SETUP_FILE_NAME);
    
    CCatalogExport::Update(
        $PROFILE_ID,
        array(
            "SETUP_VARS" => $strVars
        )
    );
         
    
}

function GetProfileSetupVars($iblocks, $propertiesProduct, $propertiesSKU, $filename) {

    $strVars = "";
    foreach ($iblocks as $key => $val) 
        $strVars .= 'IBLOCK_EXPORT[' . $key . ']=' . $val . '&';
    foreach ($propertiesSKU as $iblock => $arr) 
        foreach ($arr as $id => $val)
            $strVars .= 'IBLOCK_PROPERTY_SKU[' . $iblock . '][' . $id . ']=' . $val . '&';
    foreach ($propertiesProduct as $iblock => $arr) 
        foreach ($arr as $id => $val)
            $strVars .= 'IBLOCK_PROPERTY_PRODUCT[' . $iblock . '][' . $id . ']=' . $val . '&';

    $strVars .= 'SETUP_FILE_NAME=' . urlencode($filename);

    return $strVars;
}
