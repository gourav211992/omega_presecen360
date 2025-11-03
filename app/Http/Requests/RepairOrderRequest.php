<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\BookHelper;
use App\Helpers\Helper;
use App\Models\NumberPattern;

class RepairOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'book_id'         => 'required|integer|exists:erp_books,id',
            'document_date'   => 'required|date',
            'document_number' => 'required|string|max:100',
            'store_id'        => 'required|integer|exists:erp_stores,id',
            'repair_items'                       => 'required|array|min:1',
            'repair_items.*.rgr_item_segregation_id' => 'nullable|integer|exists:erp_rgr_item_segregations,id',
            'repair_items.*.item_id'             => 'required|integer|exists:erp_items,id',
            'repair_items.*.category_id'         => 'nullable|integer|exists:erp_categories,id',
            'repair_items.*.hsn_id'              => 'nullable|integer|exists:erp_hsns,id',
            'repair_items.*.sub_store_id'        => 'nullable|integer|exists:erp_sub_stores,id',
            'repair_items.*.item_uid'            => 'nullable|string|max:100',
            'repair_items.*.item_code'           => 'nullable|string|max:100',
            'repair_items.*.item_name'           => 'nullable|string|max:255',
            'repair_items.*.uom_id'              => 'required|integer|exists:erp_units,id',
            'repair_items.*.uom_code'            => 'required|string|max:50',
            'repair_items.*.qty'                 => 'required|numeric|min:0.01',
            'repair_items.*.repair_remarks'      => 'nullable|string|max:500',
            'repair_items.*.rgr_sub_store_id'    => 'nullable|integer|exists:erp_sub_stores,id',
            'repair_items.*.qc_sub_store_id'     => 'nullable|integer|exists:erp_sub_stores,id',
            'repair_items.*.rejuvenate_item_id'  => 'nullable|integer|exists:erp_items,id',
            'repair_items.*.rejuvenate_item_code'=> 'nullable|string|max:100',
            'repair_items.*.rejuvenate_item_name'=> 'nullable|string|max:255',

            'repair_items.*.rep_item_attributes'                             => 'nullable|array',
            'repair_items.*.rep_item_attributes.*.item_attribute_id'         => 'nullable|integer|exists:erp_item_attributes,id',
            'repair_items.*.rep_item_attributes.*.attribute_name'            => 'nullable|string|max:100',
            'repair_items.*.rep_item_attributes.*.attr_name'                 => 'nullable|integer|exists:erp_attribute_groups,id',
            'repair_items.*.rep_item_attributes.*.attribute_value'           => 'nullable|string|max:255',
            'repair_items.*.rep_item_attributes.*.attr_value'                => 'nullable|integer|exists:erp_attributes,id',
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
                
            $repairId = $this->route('id'); // For update scenario

            if ($numPattern && $numPattern->series_numbering === 'Manually') {
                $rules['document_number'] = 'required|unique:erp_repair_orders,document_number,' . ($repairId ?? 'NULL') . ',id';
            }
        }

        return $rules;
    }

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

            'repair_items.required' => 'At least one repair item is required.',
            'repair_items.*.item_id.required' => 'Item is required.',
            'repair_items.*.item_id.exists'   => 'Item not found.',
            'repair_items.*.uom_id.required'  => 'UOM is required.',
            'repair_items.*.uom_id.exists'    => 'UOM not found.',
            'repair_items.*.qty.required'     => 'Quantity is required.',
            'repair_items.*.qty.min'          => 'Quantity must be greater than zero.',

            'repair_items.*.rep_item_attributes.*.item_attribute_id.exists' => 'Invalid item attribute.',
            'repair_items.*.rep_item_attributes.*.attr_name.exists'         => 'Invalid attribute group.',
            'repair_items.*.rep_item_attributes.*.attr_value.exists'        => 'Invalid attribute value.',
        ];
    }
}
