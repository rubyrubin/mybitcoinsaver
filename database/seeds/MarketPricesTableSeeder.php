<?php

use Illuminate\Database\Seeder;
use App\MarketPrice;

class MarketPricesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Let's truncate our existing records to start from scratch.
        MarketPrice::truncate();

        $faker = \Faker\Factory::create();

        // And now, let's create data starting now going back to Jan 3 2009:
        for ($i = 1230940800; $i < time(); $i=$i+172800) {
            MarketPrice::create([
                'epoch' => $i,
                'value' => $faker->randomFloat(2,0,100000),
            ]);
        }
    }
}
