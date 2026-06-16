<?
define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define('NOT_CHECK_PERMISSIONS', true);

use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Sale\Location;
use Bitrix\Sale\Location\Admin\LocationHelper as Helper;

require_once($_SERVER["DOCUMENT_ROOT"].'/bitrix/modules/main/include/prolog_before.php');

Loader::includeModule('sale');

CUtil::JSPostUnescape();

$result = array(
	'ERRORS' => array(),
	'DATA' => array()
);

$zip = trim((string) ($_REQUEST['ZIP'] ?? ''));
$action = (string) ($_REQUEST['ACT'] ?? '');
$allowedActions = array('', 'GET_LOCS_BY_ZIP');

$siteId = '';
if (isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) && preg_match('/^[A-Za-z0-9_]{2}$/', $_REQUEST['SITE_ID']) === 1)
{
	$siteId = $_REQUEST['SITE_ID'];
}
elseif(strlen(SITE_ID))
{
	$siteId = SITE_ID;
}

if (!in_array($action, $allowedActions, true))
{
	$result['ERRORS'] = array('Invalid action');
}
elseif($zip === '' || mb_strlen($zip) > 20 || preg_match('/^[0-9A-Za-z\\-\\s]+$/u', $zip) !== 1)
{
	$result['ERRORS'] = array('Invalid zip');
}

$response = function (array $result): void {
	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

	print(CUtil::PhpToJSObject(array(
		'result' => empty($result['ERRORS']),
		'errors' => $result['ERRORS'],
		'data' => $result['DATA']
	), false, false, true));
};

if (!empty($result['ERRORS']))
{
	$response($result);

	return;
}

if($action != 'GET_LOCS_BY_ZIP')
{
	$item = Helper::getLocationsByZip($zip, array('limit' => 1))->fetch();

	if(!isset($item['LOCATION_ID']))
	{
		$result['ERRORS'] = array('Not found');
	}
	else
	{
		$result['DATA']['ID'] = intval($item['LOCATION_ID']);

		if(strlen($siteId))
		{
			if(!Location\SiteLocationTable::checkConnectionExists($siteId, $result['DATA']['ID']))
				$result['ERRORS'] = array('Found, but not connected');
		}
	}
}
else
{
	$dbRes = Helper::getLocationsByZip($zip, array('select' => array('PARENT_ID' => 'LOCATION.PARENT_ID')));
	$locationsId = array();

	while($item = $dbRes->fetch())
	{
		if(!isset($item['LOCATION_ID']))
			continue;

		$locationId = intval($item['LOCATION_ID']);

		if(strlen($siteId))
			if(!Location\SiteLocationTable::checkConnectionExists($siteId, $locationId))
				continue;

		$parentId = intval($item['PARENT_ID']);

		if(!is_array($locationsId[$parentId]))
			$locationsId[$parentId] = array();

		$locationsId[$parentId][] = $locationId;
	}

	/* If we have several locations on different levels, choose it with maximal count. */
	if(!empty($locationsId))
	{
		$maxIdsCountParentId = 0;

		foreach($locationsId as $parentId => $ids)
			if(count($ids) > $maxIdsCountParentId)
				$maxIdsCountParentId = $parentId;

		if($maxIdsCountParentId > 0)
		{
			$result['DATA']['PARENT_ID'] = $maxIdsCountParentId;
			$result['DATA']['IDS'] = $locationsId[$maxIdsCountParentId];
		}
	}

	if(!isset($result['DATA']['PARENT_ID']))
	{
		$result['ERRORS'] = array('Not found');
	}
}

$response($result);
