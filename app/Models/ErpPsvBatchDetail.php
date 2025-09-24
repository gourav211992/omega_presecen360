<?php   
namespace App\Models;

use App\Models\WHM\ErpItemUniqueCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ErpPsvBatchDetail extends Model
{

    use HasFactory;

    protected $table = "erp_psv_batch_details";
    protected $fillable = [
        'header_id', 
        'detail_id', 
        'item_id', 
        'batch_number', 
        'manufacturing_year', 
        'expiry_date', 
        'quantity', 
        'inventory_uom_qty',
    ];

    protected $hidden = ['deleted_at'];

    // In App\Models\PSVBatchDetail (or whatever model)
    protected $casts = [
        'expiry_date' => 'date', // returns a Carbon instance
    ];

    public function header()
    {
        return $this->belongsTo(ErpPsvHeader::class, 'header_id');
    }

    // public function source()
    // {
    //     return $this->hasOne(PSVBatchDetailHistory::class, 'source_id');
    // }

    public function psvItem()
    {
        return $this->belongsTo(ErpPsvItem::class, 'detail_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function uniqueCodes()
    {
        return $this->hasMany(ErpItemUniqueCode::class, 'batch_id');
    }

}