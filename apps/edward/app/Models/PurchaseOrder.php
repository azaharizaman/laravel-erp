<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Purchase Order Model (Stub for Testing)
 * 
 * This is a minimal stub model for testing workflow polymorphic relationships.
 * In a real application, this would be part of the nexus-accounting or 
 * nexus-procurement package.
 */
class PurchaseOrder extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'po_number',
        'vendor_id',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];
}
