<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpEquipMaintenanceChecklistHistory extends Model
{
    use HasFactory;

    protected $table = 'erp_equip_maintenance_checklists_history';
    
    protected $fillable = [
        'erp_equip_maintenance_id',
        'source_id', // Original checklist ID
        'name',
        'description',
        'type',
        'status',
        'created_by',
        'updated_by',
        'deleted_by',
        'equipment_id'
    ];

    public function maintenanceDetail()
    {
        return $this->belongsTo(ErpEquipMaintenanceDetailHistory::class, 'erp_equip_maintenance_id');
    }
    
    public function equipment()
    {
        return $this->belongsTo(ErpEquipmentHistory::class, 'equipment_id');
    }
}
