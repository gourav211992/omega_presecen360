<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PiPwoMappingItem extends Model
{
    use HasFactory;

    protected $table = 'erp_pi_pwo_mapping_items';
    public $timestamps = false;

    protected $fillable = [
        'pi_id',
        'pwo_id',
        'so_id',
        'bom_id',
        'bom_detail_id',
        'pwo_bom_mapping_id',
        'pi_item_id',
        'qty',
        'uom_id',
    ];

    public function pi_item()
    {
        return $this->belongsTo(PiItem::class, 'pi_item_id');
    }

    public function pi_pwo_mapping()
    {
        return $this->belongsTo(PwoBomMapping::class, 'pwo_bom_mapping_id', 'id');
    }
}
