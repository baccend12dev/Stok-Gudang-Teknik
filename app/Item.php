<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = array(
        'code','name','unit','location','buffer_min','current_status',
        'notes','current_stock','note','category_id'
    );

    public function itemDepartmentBuffers()
    {
        return $this->hasMany('App\ItemDepartmentBuffer', 'item_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo('App\Category', 'category_id');
    }

    /**
     * Accessor untuk label dropdown/select.
     * Dipakai di Request module: $item->formatted_label
     *
     * Output contoh:
     *   ATK001 - Kertas A4 (RIM) [ATK]
     */
    public function getFormattedLabelAttribute()
    {
        $code = $this->code ? trim($this->code) : '-';
        $name = $this->name ? trim($this->name) : '-';
        $unit = $this->unit ? trim($this->unit) : '';
        $cat  = ($this->category && $this->category->code) ? trim($this->category->code) : '';

        $label = $code . ' - ' . $name;

        if ($unit !== '') {
            $label .= ' (' . $unit . ')';
        }

        if ($cat !== '') {
            $label .= ' [' . $cat . ']';
        }

        return $label;
    }
}
