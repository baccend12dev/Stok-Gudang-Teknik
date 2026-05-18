<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BonDetail extends Model
{
    protected $table = 'bon_details';

    // Sesuaikan kolom yang boleh diisi (opsional)
    protected $fillable = [
        'bon_header_id',
        'item_id',
        'quantity', 
        'requested_quantity',
        'approved_quantity',
        'issued_quantity',
        'unit_price',
        'total_price',
        'notes',
    ];

    /**
     * Header BON (many details belong to one header)
     */
    public function bonHeader()
    {
        return $this->belongsTo('App\BonHeader', 'bon_header_id');
    }

    /**
     * Relasi ke item
     */
    public function item()
    {
        return $this->belongsTo('App\Item', 'item_id');
    }
}
