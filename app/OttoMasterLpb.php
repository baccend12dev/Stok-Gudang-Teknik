<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OttoMasterLpb extends Model
{
    /**
     * Nama foreign table PostgreSQL
     *
     * @var string
     */
    protected $table = 'otto_master_lpbtk_v';

    /**
     * Foreign table / view tidak menggunakan timestamps bawaan Laravel
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Foreign table tidak memiliki primary key auto-increment standar
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Guarded attributes
     *
     * @var array
     */
    protected $guarded = [];
}
