<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Helper;
use App\Helpers\BookHelper;
use App\Models\NumberPattern;

class RcaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Change as per your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            // ===================== HEADER FIELDS =====================
            'book_id'       => 'required|integer|exists:erp_books,id',
            'document_date' => 'required|date',
            'document_number' => 'required|string|max:100',
            'store_id'      => 'required|integer|exists:erp_stores,id',
            'discrepancy_type' => 'nullable|string|max:100',
            'customer_id'   => 'nullable|integer|exists:erp_customers,id',
            'fur_id'            => 'nullable',
            'customer_phone_no' => 'nullable|string|max:20',
            'unloading_date' => 'nullable|date',
            'trip_no'       => 'nullable|string|max:50',
            'vehicle_no'    => 'nullable|string|max:15',
            'champ_name'    => 'nullable|string|max:100',
            'remarks'       => 'nullable|string|max:500',

            // ===================== ITEMS ARRAY =====================
            'rca_items' => 'required|array|min:1',
            'rca_items.*.rgr_job_detail_id' => 'nullable|integer|exists:erp_item_unique_codes,id',
            'rca_items.*.rgr_item_segregation_id' => 'nullable|integer|exists:erp_rgr_item_segregations,id',
            'rca_items.*.item_id' => 'required|integer|exists:erp_items,id',
            'rca_items.*.item_code' => 'required|string|max:100',
            'rca_items.*.item_name' => 'required|string|max:255',
            'rca_items.*.item_uid' => 'nullable|string|max:100',
            'rca_items.*.uom_id' => 'required|integer|exists:erp_units,id',
            'rca_items.*.uom_code' => 'required|string|max:50',
            'rca_items.*.inventory_uom_id' => 'nullable|integer|exists:erp_units,id',
            'rca_items.*.inventory_uom_code' => 'nullable|string|max:50',
            'rca_items.*.inventory_uom_qty' => 'nullable|numeric|min:0',
            'rca_items.*.scheduled_qty' => 'nullable|numeric|min:0',
            'rca_items.*.missing_qty' => 'nullable|numeric|min:0',
            'rca_items.*.deletedMediaIds' => 'nullable|array',
            'rca_items.*.deletedMediaIds.*' => 'nullable|integer|exists:erp_rca_media,id',

            // ===================== ITEM ATTRIBUTES =====================
            'rca_items.*.rca_item_attributes' => 'nullable|array',
            'rca_items.*.rca_item_attributes.*.item_attribute_id' => 'nullable|integer|exists:erp_item_attributes,id',
            'rca_items.*.rca_item_attributes.*.item_code' => 'nullable|string|max:100',
            'rca_items.*.rca_item_attributes.*.attribute_name' => 'nullable|string|max:100',
            'rca_items.*.rca_item_attributes.*.attr_name' => 'nullable|integer|exists:erp_attribute_groups,id',
            'rca_items.*.rca_item_attributes.*.attribute_value' => 'nullable|string|max:255',
            'rca_items.*.rca_item_attributes.*.attr_value' => 'nullable|integer|exists:erp_attributes,id',
        ];

        // ===================== DOCUMENT DATE RULES =====================
        $parameters = [];
        $response = BookHelper::fetchBookDocNoAndParameters($this->input('book_id'), $this->input('document_date'));
        if ($response['status'] === 200) {
            $parameters = json_decode(json_encode($response['data']['parameters']), true);
        }

        $today = now()->toDateString();
        $futureAllowed = isset($parameters['future_date_allowed']) && in_array('yes', array_map('strtolower', (array)$parameters['future_date_allowed']));
        $backAllowed   = isset($parameters['back_date_allowed']) && in_array('yes', array_map('strtolower', (array)$parameters['back_date_allowed']));

        if (!$futureAllowed && !$backAllowed) {
            $rules['document_date'] = "required|date|in:$today";
        } elseif ($futureAllowed && $backAllowed) {
            $rules['document_date'] = "required|date";
        } elseif ($futureAllowed) {
            $rules['document_date'] = "required|date|after_or_equal:$today";
        } elseif ($backAllowed) {
            $rules['document_date'] = "required|date|before_or_equal:$today";
        }

        // ===================== DOCUMENT NUMBER UNIQUE RULE =====================
        if ($this->filled('book_id')) {
            $user = Helper::getAuthenticatedUser();
            $numPattern = NumberPattern::where('organization_id', $user->organization_id)
                ->where('book_id', $this->book_id)
                ->latest()
                ->first();
                
            $rcaId = $this->route('id'); 

            if ($numPattern && $numPattern->series_numbering === 'Manually') {
                $rules['document_number'] = 'required|unique:erp_rca_headers,document_number,' . ($rcaId ?? 'NULL') . ',id';
            }
        }

        return $rules;
    }

    /**
     * Custom error messages for validation
     */
    public function messages(): array
    {
        return [
            'book_id.required' => 'Book is required.',
            'book_id.integer'  => 'Book must be valid.',
            'book_id.exists'   => 'Book does not exist.',

            'document_date.required' => 'Document date is required.',
            'document_date.date'     => 'Document date must be valid.',
            'document_date.in'       => 'Document date must be today.',
            'document_date.after_or_equal' => 'Future dates not allowed.',
            'document_date.before_or_equal'=> 'Back dates not allowed.',

            'document_number.required' => 'Document number is required.',
            'document_number.unique'   => 'Document number already exists.',

            'store_id.required' => 'Store is required.',
            'store_id.exists'   => 'Store not found.',

            'rca_items.required' => 'At least one RCA item is required.',
            'rca_items.*.item_id.required' => 'Item is required.',
            'rca_items.*.item_id.exists'   => 'Item not found.',
            'rca_items.*.uom_id.required'  => 'UOM is required.',
            'rca_items.*.uom_id.exists'    => 'UOM not found.',
            'rca_items.*.rca_item_attributes.*.item_attribute_id.exists' => 'Invalid item attribute.',
            'rca_items.*.rca_item_attributes.*.attr_name.exists'         => 'Invalid attribute group.',
            'rca_items.*.rca_item_attributes.*.attr_value.exists'        => 'Invalid attribute value.',
        ];
    }
}
