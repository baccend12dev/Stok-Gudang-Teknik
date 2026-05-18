<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    // Nilai scope yang kita pakai
    const SCOPE_ALL     = 'ALL';
    const SCOPE_GENERAL = 'GENERAL'; // Bu Ani (ATK+SBN+AKB+UMM)
    const SCOPE_APPAREL = 'APPAREL'; // Bu Shinta (AK+PK)

    /**
     * Field yang boleh diisi mass-assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'inventory_scope',
    ];

    /**
     * Field yang disembunyikan ketika di-serialize.
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Accessor: kalau inventory_scope null / kosong → dianggap ALL.
     */
    public function getInventoryScopeAttribute($value)
    {
        if ($value === null || $value === '') {
            return self::SCOPE_ALL;
        }

        return $value;
    }

    /**
     * Label manis buat ditampilkan di UI:
     * $user->inventory_scope_label
     */
    public function getInventoryScopeLabelAttribute()
    {
        $scope = $this->inventory_scope;

        if ($scope === self::SCOPE_GENERAL) {
            return 'ATK, Sabun, Kebersihan, Umum';
        } elseif ($scope === self::SCOPE_APPAREL) {
            return 'Alas Kaki & Pakaian';
        }

        return 'Semua Kategori';
    }

    /**
     * Mapping scope user → daftar kode kategori (untuk filter nanti).
     * Contoh:
     *  - GENERAL  → ['ATK', 'SBN', 'AKB', 'UMM']
     *  - APPAREL  → ['AK', 'PK']
     *  - ALL      → null (artinya jangan filter)
     */
    public function categoryCodesForScope()
    {
        switch ($this->inventory_scope) {
            case self::SCOPE_GENERAL:
                return ['ATK', 'SBN', 'AKB', 'UMM'];

            case self::SCOPE_APPAREL:
                return ['AK', 'PK'];

            case self::SCOPE_ALL:
            default:
                return null;
        }
    }

    public function department()
    {
        return $this->belongsTo(\App\Department::class, 'department_id');
    }
}
