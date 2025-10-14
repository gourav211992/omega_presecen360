<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ErpEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        // For amendments, validate the amendment-specific fields
        if ($this->action_type === 'amendment') {
            return [
                'amend_remarks' => 'required|string|min:3',
                'amend_attachment' => 'nullable|file|mimes:png,jpeg,jpg,xls,xlsx,doc,docx,pdf|max:5120', // 5MB max
            ];
        }

        $rules = [
            'organization_id' => 'required|integer',
            'location_id' => 'required|integer',
            'category_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'alias' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'upload_document' => 'nullable|array', // Array of files
            'upload_document.*' => 'nullable|file|mimes:png,jpeg,jpg,xls,xlsx,docx,pdf|max:5120', // Each file validation, 5MB max
            'final_remarks' => 'nullable|string',
            'status' => 'required|in:draft,submitted',
            'doc_number_type' => 'nullable|string',
            'doc_prefix' => 'nullable|string',
            'doc_suffix' => 'nullable|string',
            'book_id' => 'nullable|string',
            'document_number' => 'nullable|string',

            // Maintenance details validation - conditional based on status
            'maintenance' => 'nullable|array',
            'maintenance.*.type' => 'nullable|integer|exists:erp_maintenance_types,id',
            'maintenance.*.frequency' => 'nullable|string',
            'maintenance.*.date' => 'nullable|date',
            'maintenance.*.time' => 'nullable|string',
            'maintenance.*.bom' => 'nullable|integer|exists:erp_plant_maint_bom,id',
            'maintenance.*.checklists' => 'nullable|array',

            // Spare parts validation
            'spareparts' => 'nullable|array',
            // 'spareparts.*.item_code'  => 'required_with:spareparts|string',
            // 'spareparts.*.item_name'  => 'required_with:spareparts|string',
            // 'spareparts.*.attributes' => 'nullable|array',
            // 'spareparts.*.uom'        => 'required_with:spareparts|string',
            // 'spareparts.*.qty'        => 'required_with:spareparts|numeric|min:0',
        ];

        // If status is 'submitted', make maintenance fields required when maintenance array is present
        if ($this->input('status') === 'submitted') {
            $rules['maintenance.*.type'] = 'required_with:maintenance|integer|exists:erp_maintenance_types,id';
            $rules['maintenance.*.frequency'] = 'required_with:maintenance|string';
            $rules['maintenance.*.date'] = 'required_with:maintenance|date';
            $rules['maintenance.*.time'] = 'required_with:maintenance|string';
            $rules['maintenance.*.bom'] = 'required_with:maintenance|integer|exists:erp_plant_maint_bom,id';
            $rules['maintenance.*.checklists'] = 'required_with:maintenance|array';
        }

        return $rules;
    }
}
