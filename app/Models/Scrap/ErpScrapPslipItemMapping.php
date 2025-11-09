<?php

namespace App\Models\Scrap;

use App\Models\ErpPslipItem;
use App\Traits\UserStampTrait;
use App\Traits\DateFormatTrait;
use App\Traits\FileUploadTrait;
use App\Models\ErpProductionSlip;
use App\Traits\DefaultGroupCompanyOrg;
use Illuminate\Database\Eloquent\Model;
use App\Models\Scrap\ErpScrapPslipItemMappingHistory;

class ErpScrapPslipItemMapping extends Model
{
    use DefaultGroupCompanyOrg, FileUploadTrait, DateFormatTrait, UserStampTrait;
    protected $table = 'erp_scrap_pslip_item_mappings';

    protected $fillable = [
        'group_id',
        'company_id',
        'organization_id',

        'erp_scrap_id',
        'erp_pslip_id',
        'erp_scrap_item_id',
        'erp_pslip_item_id',

        'rejected_qty',

        'remark',

        'created_by',
        'updated_by',
    ];

    public function source()
    {
        return $this->hasMany(ErpScrapPslipItemMappingHistory::class, 'source_id');
    }

    public function scrap()
    {
        return $this->belongsTo(ErpScrap::class, 'erp_scrap_id');
    }

    public function scrapItem()
    {
        return $this->belongsTo(ErpScrapItem::class, 'erp_scrap_item_id');
    }

    public function pslip()
    {
        return $this->belongsTo(ErpProductionSlip::class, 'erp_pslip_id');
    }

    public function pslipItem()
    {
        return $this->belongsTo(ErpPslipItem::class, 'erp_pslip_item_id');
    }
}
