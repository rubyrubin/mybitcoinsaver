<?php
namespace Tests\Feature;

use Tests\TestCase;


class MarketPriceTest extends TestCase
{

    public function test0()
    {
        $response = $this->get('/api/market_prices/0');
        $response->assertStatus(422);
    }

    public function testDayBitcoinStarted()
    {
        $response = $this->get('/api/market_prices/1230940800');
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertEquals(0.0, $content);
    }

    public function testDayBeforeBitcoinStarted()
    {
        $response = $this->get('/api/market_prices/1230940799');
        $response->assertStatus(422);
    }

    public function testTwoDaysAgo()
    {
        $response = $this->get('/api/market_prices/' . (time() - (86400 * 2)));
        $response->assertStatus(200);
    }

    public function testYesterday()
    {
        $response = $this->get('/api/market_prices/' . (time() - (86400)));
        $response->assertStatus(422);
    }

    public function testTomorrow()
    {
        $response = $this->get('/api/market_prices/' . (time() + (86400)));
        $response->assertStatus(422);
    }

    public function testMany()
    {
        $response = $this->get('/api/market_prices/from=1230940800&to=' . (time() - (86400 * 2)));
        $response->assertStatus(200);
    }
}