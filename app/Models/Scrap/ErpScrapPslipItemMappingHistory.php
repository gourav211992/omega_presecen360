<?php

namespace App\Models\Scrap;

use App\Traits\UserStampTrait;
use App\Traits\DateFormatTrait;
use App\Traits\FileUploadTrait;
use App\Traits\DefaultGroupCompanyOrg;
use Illuminate\Database\Eloquent\Model;

class ErpScrapPslipItemMappingHistory extends Model
{
    use DefaultGroupCompanyOrg, FileUploadTrait, DateFormatTrait, UserStampTrait;

    protected $table = 'erp_scrap_pslip_item_mappings_history';

    protected $fillable = [
        'source_id',
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

    public function mapping()
    {
        return $this->belongsTo(ErpScrapPslipItemMapping::class, 'source_id');
    }
}
