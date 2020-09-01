<?php
namespace Tests\Intaro\RetailCrm\Component\Factory;

use Intaro\RetailCrm\Component\ApiClient\ClientAdapter;
use Intaro\RetailCrm\Component\ConfigProvider;
use Intaro\RetailCrm\Component\Factory\ClientFactory;
use PHPUnit\Framework\TestCase;

class ClientFactoryTest extends TestCase
{
    
    public function testCreacteClientAdapter(): void
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        
        $configProvider->method('getApiUrl')
            ->willReturn('http://test.ru');
        $configProvider->method('getApiKey')
            ->willReturn('qwerty123');
        
        $client = ClientFactory::creacteClientAdapter();
    
        self::assertEquals(ClientAdapter::class, get_class($client));
        $configProvider->method('getApiUrl')
            ->willReturn('');
    
        $configProvider->method('getApiKey')
            ->willReturn('');
        
        $client = ClientFactory::creacteClientAdapter();
        
        self::assertEquals(null, $client);
    }
}