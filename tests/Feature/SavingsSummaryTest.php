<?php
namespace Tests\Feature;

use Tests\TestCase;


class SavingsSummaryTest extends TestCase
{
    public function testNonNumericAmount()
    {
        $response = $this->get('/api/savings_summary/amount=abc&frequency=monthly&months=36&currency=NZD');
        $response->assertStatus(422);
    }

    public function testNegativeAmount()
    {
        $response = $this->get('/api/savings_summary/amount=-1&frequency=monthly&months=36&currency=NZD');
        $response->assertStatus(422);
    }

    public function test0Amount()
    {
        $response = $this->get('/api/savings_summary/amount=0&frequency=monthly&months=36&currency=NZD');
        $response->assertStatus(422);
    }

    public function testHugeAmount()
    {
        $response = $this->get('/api/savings_summary/amount=1000000&frequency=monthly&months=36&currency=NZD');
        $response->assertStatus(422);
    }

    public function testBadFrequency()
    {
        $response = $this->get('/api/savings_summary/amount=20&frequency=qqq&months=36&currency=NZD');
        $response->assertStatus(422);
    }

    public function test0Months()
    {
        $response = $this->get('/api/savings_summary/amount=20&frequency=monthly&months=0&currency=NZD');
        $response->assertStatus(422);
    }

    public function testHugeMonths()
    {
        $response = $this->get('/api/savings_summary/amount=20&frequency=monthly&months=8888&currency=NZD');
        $response->assertStatus(422);
    }

    public function testBadMonths()
    {
        $response = $this->get('/api/savings_summary/amount=20&frequency=monthly&months=abc&currency=NZD');
        $response->assertStatus(422);
    }

    public function testBadCurrency()
    {
        $response = $this->get('/api/savings_summary/amount=20&frequency=monthly&months=12&currency=qqq');
        $response->assertStatus(422);
    }

    public function testLowercaseCurrency()
    {
        $response = $this->get('/api/savings_summary/amount=20&frequency=monthly&months=12&currency=nzd');
        $response->assertStatus(200);
    }

    // TODO Make some mock tests to confirm the actual numbers are accurate (i.e. the value increase, percent increase, etc)

}