<?php

/**
 * @file Contains \BartFeenstra\Tests\CurrencyExchange\AbstractStackedExchangeRateProviderTest.
 */

namespace BartFeenstra\Tests\CurrencyExchange;

use BartFeenstra\CurrencyExchange\ExchangeRate;

/**
 * @coversDefaultClass \BartFeenstra\CurrencyExchange\AbstractStackedExchangeRateProvider
 */
class AbstractStackedExchangeRateProviderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The class under test.
     *
     * @var \BartFeenstra\CurrencyExchange\AbstractStackedExchangeRateProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sut;

    public function setUp()
    {
        $this->sut = $this->getMockForAbstractClass('\BartFeenstra\CurrencyExchange\AbstractStackedExchangeRateProvider');
    }

    /**
     * @covers ::load
     */
    public function testLoad()
    {
        $sourceCurrencyCode = 'EUR';
        $destinationCurrencyCode = 'NLG';
        $rate = ExchangeRate::create($sourceCurrencyCode,
          $destinationCurrencyCode, '2.20371');

        $exchangeRateProviderIdA = 'fooBar' . mt_rand();
        $exchangeRateProviderA = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
        $exchangeRateProviderA->expects($this->once())
          ->method('load')
          ->with($sourceCurrencyCode, $destinationCurrencyCode)
          ->willReturn(null);

        $exchangeRateProviderIdB = 'fooBar' . mt_rand();
        $exchangeRateProviderB = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
        $exchangeRateProviderB->expects($this->once())
          ->method('load')
          ->with($sourceCurrencyCode, $destinationCurrencyCode)
          ->willReturn($rate);

        $exchangeRateProviderIdC = 'fooBar' . mt_rand();
        $exchangeRateProviderC = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
        $exchangeRateProviderC->expects($this->never())
          ->method('load');

        $this->sut->expects($this->atLeastOnce())
          ->method('getExchangeRateProviders')
          ->willReturn([
            $exchangeRateProviderIdA => $exchangeRateProviderA,
            $exchangeRateProviderIdB => $exchangeRateProviderB,
            $exchangeRateProviderIdC => $exchangeRateProviderC,
          ]);

        $this->assertSame($rate,
          $this->sut->load($sourceCurrencyCode, $destinationCurrencyCode));
    }

    /**
     * @covers ::load
     */
    public function testLoadWithIdenticalCurrencies()
    {
        $sourceCurrencyCode = 'EUR';
        $destinationCurrencyCode = 'EUR';

        $rate = $this->sut->load($sourceCurrencyCode, $destinationCurrencyCode);
        $this->assertInstanceOf('\BartFeenstra\CurrencyExchange\ExchangeRateInterface',
          $rate);
        $this->assertSame('1', $rate->getRate());
    }

    /**
     * @covers ::load
     */
    public function testLoadWithoutExchangeRateProviders()
    {
        $sourceCurrencyCode = 'foo';
        $destinationCurrencyCode = 'bar';

        $this->sut->expects($this->atLeastOnce())
          ->method('getExchangeRateProviders')
          ->willReturn([]);

        $this->assertnull($this->sut->load($sourceCurrencyCode,
          $destinationCurrencyCode));
    }

    /**
     * @covers ::loadMultiple
     */
    public function testLoadMultiple()
    {
        $sourceCurrencyCodeA = 'EUR';
        $destinationCurrencyCodeA = 'NLG';
        $rateA = '2.20371';
        $sourceCurrencyCodeB = 'NLG';
        $destinationCurrencyCodeB = 'EUR';
        $rateB = '0.453780216';

        // Convert both currencies to each other and themselves.
        $requested_rates_provider = [
          $sourceCurrencyCodeA => [
            $destinationCurrencyCodeA,
            $sourceCurrencyCodeA
          ],
          $sourceCurrencyCodeB => [
            $destinationCurrencyCodeB,
            $sourceCurrencyCodeB
          ],
        ];
        // By the time plugin A will be called, the identical source and destination
        // currencies will have been processed.
        $requested_rates_plugin_a = [
          $sourceCurrencyCodeA => [$destinationCurrencyCodeA],
          $sourceCurrencyCodeB => [$destinationCurrencyCodeB],
        ];
        // By the time plugin B will be called, the 'A' source and destination
        // currencies will have been processed.
        $requested_rates_plugin_b = [
          $sourceCurrencyCodeB => [$destinationCurrencyCodeB],
        ];

        $exchangeRateProviderIdA = 'fooBar' . mt_rand();
        $exchangeRateProviderA = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
        $returnedRatesA = [
          $sourceCurrencyCodeA => [
            $destinationCurrencyCodeA => ExchangeRate::create($sourceCurrencyCodeA,
              $destinationCurrencyCodeA, $rateA),
          ],
          $sourceCurrencyCodeB => [
            $destinationCurrencyCodeB => null,
          ],
        ];
        $exchangeRateProviderA->expects($this->once())
          ->method('loadMultiple')
          ->with($requested_rates_plugin_a)
          ->willReturn($returnedRatesA);

        $exchangeRateProviderIdB = 'fooBar' . mt_rand();
        $exchangeRateProviderB = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
        $returnedRatesB = [
          $sourceCurrencyCodeA => [
            $destinationCurrencyCodeA => null,
          ],
          $sourceCurrencyCodeB => [
            $destinationCurrencyCodeB => ExchangeRate::create($sourceCurrencyCodeA,
              $destinationCurrencyCodeA, $rateB),
          ],
        ];
        $exchangeRateProviderB->expects($this->once())
          ->method('loadMultiple')
          ->with($requested_rates_plugin_b)
          ->willReturn($returnedRatesB);

        $exchangeRateProviderIdC = 'fooBar' . mt_rand();
        $exchangeRateProviderC = $this->getMock('\BartFeenstra\CurrencyExchange\ExchangeRateProviderInterface');
        $exchangeRateProviderC->expects($this->never())
          ->method('loadMultiple');

        $this->sut->expects($this->atLeastOnce())
          ->method('getExchangeRateProviders')
          ->willReturn([
            $exchangeRateProviderIdA => $exchangeRateProviderA,
            $exchangeRateProviderIdB => $exchangeRateProviderB,
            $exchangeRateProviderIdC => $exchangeRateProviderC,
          ]);

        $exchangeRates = $this->sut->loadMultiple($requested_rates_provider);
        $this->assertSame($returnedRatesA[$sourceCurrencyCodeA][$destinationCurrencyCodeA],
          $exchangeRates[$sourceCurrencyCodeA][$destinationCurrencyCodeA]);
        $this->assertSame($exchangeRateProviderIdA,
          $exchangeRates[$sourceCurrencyCodeA][$destinationCurrencyCodeA]->getExchangeRateProviderId());
        $this->assertSame('1',
          $exchangeRates[$sourceCurrencyCodeA][$sourceCurrencyCodeA]->getRate());
        $this->assertSame($returnedRatesB[$sourceCurrencyCodeB][$destinationCurrencyCodeB],
          $exchangeRates[$sourceCurrencyCodeB][$destinationCurrencyCodeB]);
        $this->assertSame('1',
          $exchangeRates[$sourceCurrencyCodeB][$sourceCurrencyCodeB]->getRate());
        $this->assertSame($exchangeRateProviderIdB,
          $exchangeRates[$sourceCurrencyCodeB][$destinationCurrencyCodeB]->getExchangeRateProviderId());
    }

}
