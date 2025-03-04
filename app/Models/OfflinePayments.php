<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePayments extends Model
{
    use HasFactory;
    protected $casts = [
        'order_id'=>'integer',
        'amount'=>'float',  // v2.8.1
    ];
    protected $guarded = ['id'];
    // v2.8.1 code starts
    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id');
    }
    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
    // v2.8.1 code ends
}
