<?php

namespace App\Http\Requests\Integration;

use App\Models\ErpItemAttribute;
use Illuminate\Foundation\Http\FormRequest;

class StoreFurlencoDeliveryNoteRequest extends FormRequest
{
   public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | General Sale Invoice
            |--------------------------------------------------------------------------
            */
            'document_number' => [
                'required',
                'string',
                'max:100'
            ],

            'document_date' => [
                'required',
                'date',
                'after_or_equal:today', // must not be in the past
            ],

            'store_id' => [
                'required',
                'integer',
                'exists:erp_stores,id', // store must exist
            ],

            'stock_type' => [
                'required',
                'exists:erp_stock_store_mappings,stock_type', // stock type must exist
            ], 

            'organization_id' => [
                'required',
                'integer',
                'exists:organizations,id', // organization must exist in organizations table
            ],

            /*
            |--------------------------------------------------------------------------
            | Invoice Items
            |--------------------------------------------------------------------------
            */
            'items' => [
                'required',
                'array',
                'min:1', // at least 1 SKU required
            ],

            'items.*.item_code' => [
                'required',
                'string',
                'max:100',
                'exists:erp_items,item_code', // must exist in ERP items
            ],

            'items.*.item_name' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.item_id' => [
                'required',
                'integer',
                'exists:erp_items,id'
            ],
            'items.*.item_qty' => [
                'required',
                'numeric',
                'min:0.01', // must be greater than zero
            ],

            'items.*.item_rate' => [
                'required',
                'numeric',
                'min:0', // free items allowed, no negatives
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Attributes
            |--------------------------------------------------------------------------
            */
            'items.*.item_attributes' => [
                'nullable',
                'array',
            ],

            'items.*.item_attributes.*.id' => [
                'required_with:items.*.item_attributes',
                'distinct',
                'integer',
                'exists:erp_item_attributes,id',
            ],

            'items.*.item_attributes.*.attribute_name_id' => [
                'required_with:items.*.item_attributes',
                'distinct',
                'integer',
                'exists:erp_item_attributes,attribute_group_id',
            ],

            'items.*.item_attributes.*.attribute_value_id' => [
                'required_with:items.*.item_attributes',
                'distinct',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    $exists = ErpItemAttribute::whereRaw(
                        'JSON_CONTAINS(attribute_id, ?)',
                        [json_encode((string) $value)]
                    )->exists();

                    if (! $exists) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
            ],

            'items.*.item_attributes.*.attribute_name' => [
                'required_with:items.*.item_attributes',
                'distinct',
                'string',
            ],

            'items.*.item_attributes.*.attribute_value' => [
                'required_with:items.*.item_attributes',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Transport Details
            |--------------------------------------------------------------------------
            */
            'transport_detail' => ['required', 'array'],
            'transport_detail.transport_mode' => [
                'required_with:transport_detail', 'in:Road,Rail,Air,Ship,InTransit'
            ],
            'transport_detail.vehicle_number' => [
                'required_with:transport_detail', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z]{1,2}[0-9]{4}$/'
            ],
            'transport_detail.transporter_name' => [
                'required_with:transport_detail', 'string', 'max:199'
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal'   => 'Order date cannot be in the past.',
            'items.*.item_code.exists' => 'Invalid Item Code provided.',
        ];
    }

    public function attributes(): array
    {
        return [
            'document_number'       => 'Document Number',
            'document_date'         => 'Document Date',
            'store_id'              => 'Location/Store',
            'stock_type'              => 'Stock Type',
            'organization_id'       => 'Warehouse/Organization',

            'items'  => 'Items',
            'items.*.item_code'     => 'Item Code',
            'items.*.item_name'     => 'Item Name',
            'items.*.item_qty'      => 'Item Quantity',
            'items.*.item_rate'     => 'Item Rate',

            'items.*.item_attributes.*.id'  => 'Attribute ID',
            'items.*.item_attributes.*.attribute_name_id'  => 'Attribute Name ID',
            'items.*.item_attributes.*.attribute_value_id' => 'Attribute Value ID',
            'items.*.item_attributes.*.attribute_name'     => 'Attribute Name',
            'items.*.item_attributes.*.attribute_value'    => 'Attribute Value',

            'transport_detail'                   => 'transport detail',
            'transport_detail.transport_mode'    => 'transport mode',
            'transport_detail.vehicle_number'    => 'vehicle number',
            'transport_detail.transporter_name'  => 'transporter name',
        ];
    }
}
