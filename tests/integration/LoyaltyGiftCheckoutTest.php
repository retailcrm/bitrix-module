<?php

use Bitrix\Main\Application;

class LoyaltyGiftCheckoutTest extends \PHPUnit\Framework\TestCase
{
    public function testGiftOrderFlow(): void
    {
        $testRoot = getenv('GIFT_TEST_DOCUMENT_ROOT') ?: $_SERVER['DOCUMENT_ROOT'];
        $database = Application::getConnection()->queryScalar('SELECT DATABASE()');
        if (!getenv('GIFT_TEST_DOCUMENT_ROOT') && substr($database, -5) !== '_test') {
            self::markTestSkipped('The order test needs an isolated Bitrix database ending in _test.');
        }

        $environment = getenv();
        $environment['GIFT_TEST_DOCUMENT_ROOT'] = $testRoot;
        $process = proc_open([PHP_BINARY, __DIR__ . '/loyalty-gifts.php'], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, null, $environment);
        self::assertIsResource($process, 'Could not start the isolated order test.');
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status === 77) {
            self::markTestSkipped(trim($error));
        }
        self::assertSame(0, $status, trim($error . "\n" . $output));
        self::assertSame(7, substr_count($output, 'PASS '), $output);
    }
}
