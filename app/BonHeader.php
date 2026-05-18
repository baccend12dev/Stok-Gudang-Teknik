<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BonHeader extends Model
{
    protected $table = 'bon_headers';

    protected $fillable = [
        'bon_number',
        'date',
        'department_id',
        'division_name', // <-- divisi disimpan di sini
        'notes',
        'status',
    ];

    // Biar $bon->date->format() jalan
    protected $dates = ['date', 'created_at', 'updated_at'];

    /**
     * Satu header punya banyak detail
     */
    public function details()
    {
        return $this->hasMany('App\BonDetail', 'bon_header_id');
    }

    /**
     * Relasi ke departemen
     */
    public function department()
    {
        return $this->belongsTo('App\Department', 'department_id');
    }

    /**
     * Relasi balik ke Request Header.
     * Karena di database: request_headers.bon_header_id = bon_headers.id
     * Maka relasinya adalah hasOne.
     */
    public function requestReference()
    {
        return $this->hasOne('App\RequestHeader', 'bon_header_id');
    }

    public function user()
    {
        // Asumsi foreign key di tabel bon_headers adalah 'user_id'
        // Jika di database namanya 'created_by', ganti 'user_id' jadi 'created_by'
        return $this->belongsTo('App\User', 'user_id');
    }
}
