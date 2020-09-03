<?php

namespace Tests\Intaro\RetailCrm\Component\Factory;

use Bitrix\Main\Config\Option;
use Intaro\RetailCrm\Component\ApiClient\ClientAdapter;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use PHPUnit\Framework\TestCase;

class ClientFactoryTest extends TestCase
{
    public function testCreacteClientAdapter(): void
    {
        $client = ClientFactory::creacteClientAdapter();
        if (empty(ConfigProvider::getApiUrl())) {
            self::assertEquals(null, $client);
        }else{
            self::assertEquals(ClientAdapter::class, get_class($client));
        }
    }
}
