<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetails extends Model
{
    protected $primaryKey="invoice_detail_id";
    protected $fillable=[
        'invoice_detail_id',
        'invoice_id',
        'car_id',
        'quantity',
        'unit_price',
        'total',
    ];
}
