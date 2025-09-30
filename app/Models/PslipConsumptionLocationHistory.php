<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PslipConsumptionLocationHistory extends Model
{
    use HasFactory;

    protected $table = 'erp_pslip_consumption_locations_history';
    
    protected $fillable = [
        'pslip_id',
        'pslip_consumption_id',
        'item_id',
        'store_id',
        'sub_store_id',
        'station_id',
        'rack_id',
        'shelf_id',
        'bin_id',
        'quantity',
        'inventory_uom_qty'
    ];

    protected $hidden = ['deleted_at'];

    public function station()
    {
        return $this -> belongsTo(Station::class, 'station_id');
    }

    public function header()
    {
        return $this -> belongsTo(ErpProductionSlipHistory::class, 'pslip_id');
    }

    public function consumption()
    {
        return $this -> belongsTo(PslipBomConsumptionHistory::class, 'pslip_consumption_id');
    }

    public function erpStore()
    {
        return $this->belongsTo(ErpStore::class, 'store_id');
    }

    public function erpSubStore()
    {
        return $this->belongsTo(ErpSubStore::class, 'sub_store_id');
    }

    public function erpRack()
    {
        return $this->belongsTo(ErpRack::class, 'rack_id');
    }

    public function erpShelf()
    {
        return $this->belongsTo(ErpShelf::class, 'shelf_id');
    }

    public function erpBin()
    {
        return $this->belongsTo(ErpBin::class, 'bin_id');
    }
}
