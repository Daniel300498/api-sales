<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'total',
        'total_discount',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'total_discount' => 'decimal:2',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function saleDetails()
    {
        return $this->hasMany(SaleDetail::class);
    }

}
