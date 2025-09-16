<?php

namespace App\Http\Requests\Integration;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFurlencoSaleOrderRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'document_number' => ['required', 'string', 'max:50'],
            'document_date' => [
                'required',
                'date',
                'after_or_equal:today', // must not be in the past
            ],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
            'store_id' => [
                'required',
                'integer',
                'exists:erp_stores,id', // store must exist
            ],

            'delivery_skus' => ['required', 'array', 'min:1'],
            'delivery_skus.*.action_type' => ['required', Rule::in(['add', 'update', 'delete'])],

           'delivery_skus.*.sale_type' => [
                'required_if:delivery_skus.*.action_type,add',
                'nullable',
                'string',
                'max:50',
                'in:sale,rental'
            ],
            'delivery_skus.*.item_id' => [
                'required',
                'integer',
                'exists:erp_items,id'
            ],
            'delivery_skus.*.item_code' => [
                'required',
                'string',
                'max:100',
                'exists:erp_items,item_code', // must exist in ERP items
            ],

            'delivery_skus.*.item_qty' => [
                'required_if:delivery_skus.*.action_type,add,update',
                'nullable',
                'integer',
                'min:1',
            ],

            'delivery_skus.*.item_rate' => [
                'required_if:delivery_skus.*.action_type,add,update',
                'nullable',
                'numeric',
                'min:0',
            ],

            'delivery_skus.*.consignee_id' => ['required', 'integer', 'exists:erp_consignees,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal'   => 'Order date cannot be in the past.',
            'delivery_skus.*.sale_type.in' => 'Sale type must be either sale or rental.',
            'delivery_skus.*.item_code.exists' => 'Invalid Item Code provided.',
            'delivery_skus.*.consignee_id.exists' => 'Invalid consignee provided.'
        ];
    }

    public function attributes(): array
    {
        return [
            'document_number'       => 'Trip/Document Number',
            'document_date'         => 'Trip/Order Date',
            'store_id'              => 'Location/Store',
            'organization_id'       => 'Warehouse/Organization',
            'delivery_skus' => 'delivery items',
            'delivery_skus.*.action_type' => 'action type',
            'delivery_skus.*.sale_type'   => 'sale type',

            'delivery_skus.*.item_code' => 'item code',
            'delivery_skus.*.item_qty' => 'item quantity',
            'delivery_skus.*.item_rate' => 'item rate',
            'delivery_skus.*.consignee_id' => 'consignee',
        ];
    }
}
