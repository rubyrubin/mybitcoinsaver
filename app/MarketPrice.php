<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    protected $fillable = ['epoch', 'value'];
    public $primaryKey = 'epoch';

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'epoch';
    }
}
