<?php

/**
 * Reproduces the 5445 - 446 case on a COPY of the local demo-shop database.
 * GIFT_TEST_DOCUMENT_ROOT must point to a Bitrix root whose database ends in _test.
 * The API is replaced: this script does not create CRM orders or debit real bonuses.
 */
use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Order;
use Intaro\RetailCrm\Component\Advanced\LoyaltyInstaller;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Handlers\EventsHandlers;
use Intaro\RetailCrm\Component\ServiceLocator;
use Intaro\RetailCrm\Model\Api\CodeValueModel;
use Intaro\RetailCrm\Model\Api\LoyaltyAccount;
use Intaro\RetailCrm\Model\Api\OrderProduct;
use Intaro\RetailCrm\Model\Api\Response\Loyalty\LoyaltyCalculateResponse;
use Intaro\RetailCrm\Model\Api\Response\Order\Loyalty\OrderLoyaltyApplyResponse;
use Intaro\RetailCrm\Model\Api\SerializedLoyaltyOrder;
use Intaro\RetailCrm\Model\Api\SerializedOrderProductOffer;
use Intaro\RetailCrm\Service\LoyaltyGiftRecalculator;
use Intaro\RetailCrm\Service\LoyaltyService;

$root = getenv('GIFT_TEST_DOCUMENT_ROOT');
if (!$root || PHP_SAPI !== 'cli') {
    throw new RuntimeException('Set GIFT_TEST_DOCUMENT_ROOT to an isolated Bitrix installation.');
}
$_SERVER['DOCUMENT_ROOT'] = $root;
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);
define('BX_CRONTAB', true);
require $root . '/bitrix/modules/main/include/prolog_before.php';
set_exception_handler(static function (Throwable $exception): void {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
});
$connection = Application::getConnection();
if (substr($connection->queryScalar('SELECT DATABASE()'), -5) !== '_test') {
    throw new RuntimeException('Refusing to run against a non-test database.');
}
Loader::includeModule('sale');
Loader::includeModule('catalog');
Loader::includeModule('intaro.retailcrm');
$USER->Authorize(1);

// Roll back all fixture changes, including registered handlers and created orders.
$connection->startTransaction();
register_shutdown_function(static function () use ($connection): void {
    $connection->rollbackTransaction();
});

// The copied local fixture contains the demo clothes and the bracelet rule.
$fixture = [];
foreach (Basket::loadItemsForFUser(1, 's1') as $item) {
    $fixture[$item->getProductId()] = $item->getFields()->getValues();
}
foreach ([299, 314, 194, 317] as $productId) {
    if (!isset($fixture[$productId])) {
        fwrite(STDERR, 'Demo basket fixture is missing product ' . $productId . PHP_EOL);
        exit(77);
    }
}

// Remove the real CRM export and leftover handlers in the copied database only.
$connection->queryExecute("DELETE FROM b_module_to_module WHERE TO_MODULE_ID='intaro.retailcrm' AND FROM_MODULE_ID='sale'");
(new LoyaltyInstaller())->addEvents();
$GLOBALS['giftExports'] = [];
function retailCrmBeforeOrderSend($order, $source)
{
    $GLOBALS['giftExports'][] = $order;
    return false; // Stop before any API client or real transport is used.
}
EventManager::getInstance()->addEventHandler('sale', 'OnSaleOrderSaved', static function ($event): void {
    $order = RetailCrmOrder::orderObjToArr($event->getParameter('ENTITY'));
    RetailCrmOrder::orderSend($order, new stdClass(), [
        'optionsOrderTypes' => [], 'optionsPayStatuses' => [], 'optionsOrderProps' => [],
        'optionsDelivTypes' => [], 'optionsPayTypes' => [1 => 'cash', 2 => 'cash'], 'optionsPayment' => [],
    ], false, null, 'ordersCreate');
}, false, 99);

final class GiftTestLoyaltyService extends LoyaltyService
{
    public $actualBonuses;
    public $applyCalls = 0;

    public function getLoyaltyAccounts(int $id): ?LoyaltyAccount
    {
        $account = new LoyaltyAccount();
        $account->amount = 100000;
        return $account;
    }

    public function sendBonusPayment(int $orderId, float $bonusCount): ?OrderLoyaltyApplyResponse
    {
        $this->applyCalls++;
        $order = Order::load($orderId);
        $rows = [];
        foreach ($order->getBasket() as $item) {
            $rows[] = ['ID' => $item->getId(), 'PRODUCT_ID' => $item->getProductId(),
                'BASE_PRICE' => $item->getBasePrice(), 'PRICE' => $item->getProductId() === 317 ? 0 : $item->getBasePrice(),
                'QUANTITY' => $item->getQuantity()];
        }
        $response = new OrderLoyaltyApplyResponse();
        $response->success = true;
        $response->order = giftTestCalculation($rows, $this->actualBonuses ?? $bonusCount, true)->order;
        return $response;
    }
}

function giftTestCalculation(array $rows, float $bonuses, bool $withBasketIds): LoyaltyCalculateResponse
{
    $response = new LoyaltyCalculateResponse();
    $response->success = true;
    $response->order = new SerializedLoyaltyOrder();
    $response->order->items = [];
    $response->order->bonusesCreditTotal = 0;
    $remaining = $bonuses;
    $paidRows = array_filter($rows, static function ($row): bool { return (int) $row['PRODUCT_ID'] !== 317; });
    $last = array_key_last($paidRows);
    foreach ($rows as $key => $row) {
        $item = new OrderProduct();
        $item->initialPrice = $row['BASE_PRICE'];
        $item->quantity = $row['QUANTITY'];
        $item->offer = new SerializedOrderProductOffer();
        $item->offer->externalId = (string) $row['PRODUCT_ID'];
        $item->externalIds = [];
        if ($withBasketIds) {
            $id = new CodeValueModel();
            $id->code = 'bitrixBasketId';
            $id->value = $row['ID'] . '#position';
            $item->externalIds = [$id];
        }
        if ((int) $row['PRODUCT_ID'] === 317) {
            $item->discountTotal = $row['BASE_PRICE'];
            $item->bonusesChargeTotal = 0;
        } else {
            $discount = $key === $last ? $remaining / $row['QUANTITY'] : floor($bonuses * $row['BASE_PRICE'] / 5445);
            $remaining -= $discount * $row['QUANTITY'];
            $item->discountTotal = $discount;
            $item->bonusesChargeTotal = $discount;
        }
        $response->order->items[] = $item;
    }
    // Reverse response rows to catch accidental position-based matching.
    $response->order->items = array_reverse($response->order->items);
    return $response;
}

function giftTestAssert($condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

foreach ([['Y', 446, null, false], ['N', 446, null, true], ['Y', 445, null, true],
    ['Y', 0, null, true], ['Y', 445, 446, false], ['Y', 446, null, false, true],
    ['Y', 446, null, false, false, true]] as $case) {
    [$enabled, $bonuses, $actual, $giftExpected, $preview, $failure] = array_pad($case, 6, false);
    ConfigProvider::setLoyaltyProgramStatus('Y');
    ConfigProvider::setLoyaltyBonusesForBasketRules($enabled);
    ServiceLocator::set(LoyaltyGiftRecalculator::class, new LoyaltyGiftRecalculator());
    $service = new GiftTestLoyaltyService();
    $service->actualBonuses = $actual;
    $client = new class {
        public $failure = false;
        public $calls = 0;
        public function loyaltyCalculate($request)
        {
            $this->calls++;
            if ($this->failure) {
                $response = new LoyaltyCalculateResponse();
                $response->success = false;
                return $response;
            }
            $rows = [];
            foreach ($request->order->items as $item) {
                $xmlId = $item->offer->xmlId;
                $productId = ['610#615' => 299, '637#642' => 314, '438#443' => 194, '317' => 317][$xmlId];
                $rows[] = ['ID' => $item->externalIds[0]->value, 'PRODUCT_ID' => $productId,
                    'BASE_PRICE' => $item->initialPrice, 'PRICE' => $item->initialPrice - $item->discountManualAmount,
                    'QUANTITY' => $item->quantity];
            }
            return giftTestCalculation($rows, $request->bonuses, false);
        }
    };
    $client->failure = $failure;
    $property = new ReflectionProperty(LoyaltyService::class, 'client');
    $property->setAccessible(true);
    $property->setValue($service, $client);
    ServiceLocator::set(LoyaltyService::class, $service);
    $basket = Basket::create('s1');
    foreach ($fixture as $productId => $fields) {
        $item = $basket->createItem('catalog', $productId);
        $item->setFields(array_intersect_key($fields, array_flip([
            'NAME', 'QUANTITY', 'CURRENCY', 'LID', 'PRODUCT_PROVIDER_CLASS', 'PRODUCT_XML_ID',
            'CATALOG_XML_ID', 'PRICE_TYPE_ID', 'CAN_BUY', 'WEIGHT', 'VAT_RATE', 'VAT_INCLUDED',
        ])) + ['PRICE' => $fields['BASE_PRICE'], 'BASE_PRICE' => $fields['BASE_PRICE'], 'CUSTOM_PRICE' => 'N']);
    }
    $fuser = \Bitrix\Sale\Fuser::add();
    $basket->setFUserId($fuser->getId());
    $basketSave = $basket->save();
    giftTestAssert($basketSave->isSuccess(), 'Cannot save fixture basket: ' . implode('; ', $basketSave->getErrorMessages()));
    $basket = Basket::loadItemsForFUser($fuser->getId(), 's1')->getOrderableItems();
    $giftBasketId = 0;
    foreach ($basket as $item) {
        if ($item->getProductId() === 317) { $giftBasketId = $item->getId(); }
    }
    $order = Order::create('s1', 1, 'RUB');
    $order->setPersonTypeId(1);
    $order->appendBasket($basket);
    $shipment = $order->getShipmentCollection()->createItem();
    $shipment->setFields(['DELIVERY_ID' => 3, 'DELIVERY_NAME' => 'Test pickup']);
    foreach ($basket as $item) {
        $shipment->getShipmentItemCollection()->createItem($item)->setQuantity($item->getQuantity());
    }
    $payment = $order->getPaymentCollection()->createItem();
    $payment->setFields(['PAY_SYSTEM_ID' => 2, 'PAY_SYSTEM_NAME' => 'Test payment', 'SUM' => 5445, 'CURRENCY' => 'RUB']);
    $order->doFinalAction(true);
    $_POST = ['bonus-input' => $bonuses];
    if ($preview) {
        $rows = [];
        $grid = [];
        foreach ($basket as $item) {
            $row = $item->getFields()->getValues();
            $row['XML_ID'] = $item->getField('PRODUCT_XML_ID');
            $rows[] = $row;
            $grid[$item->getId()] = ['data' => $row];
        }
        $request = new \Bitrix\Main\HttpRequest(new \Bitrix\Main\Server([]), [],
            ['bonus-input' => $bonuses, 'charge-rate' => 1], [], []);
        $result = ['BASKET_ITEMS' => $rows, 'GRID' => ['ROWS' => $grid],
            'JS_DATA' => ['TOTAL' => ['ORDER_TOTAL_PRICE' => $order->getPrice()]]];
        EventsHandlers::OnSaleComponentOrderResultPreparedHandler($order, [], $request, [], $result);
        giftTestAssert(count($result['BASKET_ITEMS']) === 3, 'Gift still in checkout data');
        giftTestAssert($result['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] == 4999, 'Wrong checkout total');
    }
    $save = $order->save();
    if ($failure) {
        giftTestAssert(!$save->isSuccess() && $order->getId() === 0 && $service->applyCalls === 0,
            'Failed calculation must return an error before creating an order or applying bonuses');
        giftTestAssert(strpos(implode(' ', $save->getErrorMessages()), 'RETAILCRM_LOYALTY_RECALCULATION_ERROR') === false,
            'Error message was not localized');
        echo "PASS failed calculation returns a checkout error before saving\n";
        continue;
    }
    if ($preview) {
        giftTestAssert($client->calls === 1, 'Preview discount was calculated/applied twice for the same order');
    }
    giftTestAssert($save->isSuccess(), implode('; ', $save->getErrorMessages()));
    $saved = Order::load($order->getId());
    $hasGift = false;
    foreach ($saved->getBasket() as $item) {
        $hasGift = $hasGift || $item->getProductId() === 317;
    }
    giftTestAssert($hasGift === $giftExpected, 'Wrong saved gift state');
    if (!$giftExpected) {
        giftTestAssert(!\Bitrix\Sale\Internals\BasketTable::getById($giftBasketId)->fetch(),
            'Removed gift was left in the customer cart');
    }
    giftTestAssert($service->applyCalls === ($bonuses > 0 ? 1 : 0), 'Bonuses applied more than once');
    $lastExport = end($GLOBALS['giftExports']);
    $exportHasGift = in_array('317', array_map('strval', array_column(array_column($lastExport['items'], 'offer'), 'externalId')), true);
    giftTestAssert($exportHasGift === $giftExpected, 'Wrong exported gift state');
    foreach ($lastExport['items'] as $item) {
        if ($enabled === 'Y' && (int) $item['offer']['externalId'] !== 317) {
            giftTestAssert((float) $item['discountManualAmount'] === 0.0, 'Bonus counted again as a manual CRM discount');
        }
    }
    $expectedPrice = 5445 - ($actual ?? $bonuses);
    giftTestAssert(abs($saved->getPrice() - $expectedPrice) < 0.01, 'Wrong saved total');
    giftTestAssert(abs($saved->getPaymentCollection()->getSum() - $expectedPrice) < 0.01, 'Wrong payment total');
    echo "PASS setting=$enabled bonuses=$bonuses actual=" . ($actual ?? $bonuses) . " gift=" . ($hasGift ? 'yes' : 'no') . " total=" . $saved->getPrice() . PHP_EOL;
}
