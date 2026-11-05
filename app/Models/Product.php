<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'price',
        'stock',
        'image'
    ];
    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }
}
