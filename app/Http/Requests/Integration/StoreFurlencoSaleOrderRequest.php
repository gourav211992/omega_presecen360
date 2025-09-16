<?php

namespace App\Http\Requests\Integration;

use App\Models\ErpItemAttribute;
use Illuminate\Foundation\Http\FormRequest;

class StoreFurlencoSaleOrderRequest extends FormRequest
{

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | General Sale Order
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
            'organization_id' => [
                'required',
                'integer',
                'exists:organizations,id', // organization must exist in organizations table
            ],

            /*
            |--------------------------------------------------------------------------
            | Delivery SKUs
            |--------------------------------------------------------------------------
            */
            'delivery_skus' => [
                'required',
                'array',
                'min:1', // at least 1 SKU required
            ],

            'delivery_skus.*.sale_type' => [
                'required',
                'string',
                'in:sale,rental', // only "sale" or "rental" allowed
            ],

            'delivery_skus.*.item_code' => [
                'required',
                'string',
                'max:100',
                'exists:erp_items,item_code', // must exist in ERP items
            ],

            'delivery_skus.*.item_name' => [
                'required',
                'string',
                'max:255',
            ],

            'delivery_skus.*.item_id' => [
                'required',
                'integer',
                'exists:erp_items,id'
            ],
            'delivery_skus.*.item_qty' => [
                'required',
                'numeric',
                'min:0.01', // must be greater than zero
            ],

            'delivery_skus.*.item_rate' => [
                'required',
                'numeric',
                'min:0', // free items allowed, no negatives
            ],

            'delivery_skus.*.consignee_id' => [
                'required',
                'integer',
                'exists:erp_consignees,id', // consignee must exist in ERP
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Attributes
            |--------------------------------------------------------------------------
            */
            'delivery_skus.*.item_attributes' => [
                'nullable',
                'array',
            ],

            'delivery_skus.*.item_attributes.*.id' => [
                'required_with:delivery_skus.*.item_attributes',
                'distinct',
                'integer',
                'exists:erp_item_attributes,id',
            ],

            'delivery_skus.*.item_attributes.*.attribute_name_id' => [
                'required_with:delivery_skus.*.item_attributes',
                'distinct',
                'integer',
                'exists:erp_item_attributes,attribute_group_id',
            ],

            'delivery_skus.*.item_attributes.*.attribute_value_id' => [
                'required_with:delivery_skus.*.item_attributes',
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

            'delivery_skus.*.item_attributes.*.attribute_name' => [
                'required_with:delivery_skus.*.item_attributes',
                'distinct',
                'string',
            ],

            'delivery_skus.*.item_attributes.*.attribute_value' => [
                'required_with:delivery_skus.*.item_attributes',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Consignee Address
            |--------------------------------------------------------------------------
            */
            'delivery_skus.*.consignee_address' => [
                'required',
                'array',
            ],

            'delivery_skus.*.consignee_address.country_id' => [
                'required',
                'integer',
                'exists:mysql_master.countries,id',
            ],

            'delivery_skus.*.consignee_address.state_id' => [
                'required',
                'integer',
                'exists:mysql_master.states,id',
            ],

            'delivery_skus.*.consignee_address.city_id' => [
                'required',
                'integer',
                'exists:mysql_master.cities,id',
            ],

            'delivery_skus.*.consignee_address.pincode' => [
                'required',
                'string',
                'max:6',
            ],

            'delivery_skus.*.consignee_address.address' => [
                'required',
                'string',
                'max:250',
            ],

            /*
            |--------------------------------------------------------------------------
            | Transport Details
            |--------------------------------------------------------------------------
            */
            'transport_detail' => ['sometimes', 'array'],
            'transport_detail.transport_mode' => [
                'required_with:transport_detail', 'in:Road,Rail,Air,Ship'
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
            'delivery_skus.*.sale_type.in' => 'Sale type must be either sale or rental.',
            'delivery_skus.*.item_code.exists' => 'Invalid Item Code provided.',
            'delivery_skus.*.consignee_id.exists' => 'Invalid consignee provided.',
            'delivery_skus.*.consignee_address.country_id.exists' => 'Invalid Country selected.',
            'delivery_skus.*.consignee_address.state_id.exists'   => 'Invalid State selected.',
            'delivery_skus.*.consignee_address.city_id.exists'    => 'Invalid City selected.',
        ];
    }

    public function attributes(): array
    {
        return [
            'document_number'       => 'Trip/Document Number',
            'document_date'         => 'Trip/Order Date',
            'store_id'              => 'Location/Store',
            'organization_id'       => 'Warehouse/Organization',

            'delivery_skus'  => 'Delivery SKUs',
            'delivery_skus.*.sale_type'     => 'Sale Type',
            'delivery_skus.*.item_code'     => 'Item Code',
            'delivery_skus.*.item_name'     => 'Item Name',
            'delivery_skus.*.item_qty'      => 'Item Quantity',
            'delivery_skus.*.item_rate'     => 'Item Rate',
            'delivery_skus.*.consignee_id'  => 'Consignee',

            'delivery_skus.*.item_attributes.*.id'  => 'Attribute ID',
            'delivery_skus.*.item_attributes.*.attribute_name_id'  => 'Attribute Name ID',
            'delivery_skus.*.item_attributes.*.attribute_value_id' => 'Attribute Value ID',
            'delivery_skus.*.item_attributes.*.attribute_name'     => 'Attribute Name',
            'delivery_skus.*.item_attributes.*.attribute_value'    => 'Attribute Value',

            'delivery_skus.*.consignee_address.country_id' => 'Country',
            'delivery_skus.*.consignee_address.state_id'   => 'State',
            'delivery_skus.*.consignee_address.city_id'    => 'City',
            'delivery_skus.*.consignee_address.pincode'    => 'Pincode',
            'delivery_skus.*.consignee_address.address'    => 'Consignee Address',

            'transport_detail'                   => 'transport detail',
            'transport_detail.transport_mode'    => 'transport mode',
            'transport_detail.vehicle_number'    => 'vehicle number',
            'transport_detail.transporter_name'  => 'transporter name',
        ];
    }
}
