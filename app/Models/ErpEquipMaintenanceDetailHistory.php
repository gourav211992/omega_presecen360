<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpEquipMaintenanceDetailHistory extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    protected $fillable = [
        'erp_equipment_id',
        'source_id', // Original maintenance detail ID
        'maintenance_type_id',
        'frequency',
        'start_date',
        'time',
        'maintenance_bom_id',
        'latest_work_order_id',
        'checklist_data',
        'created_by',
        'updated_by'
    ];
    
    protected $casts = [
        'checklist_data' => 'array',
    ];
    
    public function equipment()
    {
        return $this->belongsTo(ErpEquipmentHistory::class, 'erp_equipment_id','id');
    }
    
    public function checklists()
    {
        return $this->hasMany(ErpEquipMaintenanceChecklistHistory::class, 'erp_equip_maintenance_id');
    }
    
    public function maintenanceType()
    {
        return $this->belongsTo(ErpMaintenanceType::class, 'maintenance_type_id');
    }
    
    public function bom()
    {
        return $this->belongsTo(PlantMaintBom::class, 'maintenance_bom_id');
    }
}
