<?php

namespace App\Http\Requests\Integration;

use App\Models\ErpItemAttribute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFurlencoSaleOrderRequest extends FormRequest
{

    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Trip-Level Fields
            |--------------------------------------------------------------------------
            */
            'trip_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('erp_trip_plan_headers', 'document_number'),
            ],

            'trip_date' => [
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
            | Orders Array
            |--------------------------------------------------------------------------
            */
            'orders' => [
                'required',
                'array',
                'min:1', // at least 1 order required
            ],

            'orders.*.ref_order_number' => [
                'required',
                'string',
                'max:100',
                'distinct'
            ],

            'orders.*.consignee_id' => [
                'required',
                'integer',
                'exists:erp_consignees,id', // consignee must exist in ERP
            ],

            'orders.*.sale_type' => [
                'required', 
                'string', 
                'in:sale,rental'
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            'orders.*.shipping_address' => [
                'required',
                'array',
            ],

            'orders.*.shipping_address.country_id' => [
                'required',
                'integer',
                'exists:mysql_master.countries,id',
            ],

            'orders.*.shipping_address.state_id' => [
                'required',
                'integer',
                'exists:mysql_master.states,id',
            ],

            'orders.*.shipping_address.city_id' => [
                'required',
                'integer',
                'exists:mysql_master.cities,id',
            ],

            'orders.*.shipping_address.pincode' => [
                'required',
                'string',
                'max:6',
            ],

            'orders.*.shipping_address.address' => [
                'required',
                'string',
                'max:250',
            ],

            /*
            |--------------------------------------------------------------------------
            | Order Items
            |--------------------------------------------------------------------------
            */
            'orders.*.order_items' => [
                'required',
                'array',
                'min:1', // at least 1 SKU required
                function ($attribute, $value, $fail) {
                    $itemIds = [];
                    foreach ($value as $item) {
                        if (!isset($item['item_id'])) continue;
                        if (in_array($item['item_id'], $itemIds)) {
                            $fail("Duplicate item_id '{$item['item_id']}' found in $attribute.");
                            break;
                        }
                        $itemIds[] = $item['item_id'];
                    }
                },
            ],

            'orders.*.order_items.*.item_id' => [
                'required',
                'integer',
                'exists:erp_items,id'
            ],

            'orders.*.order_items.*.item_code' => [
                'required',
                'string',
                'max:100',
                'exists:erp_items,item_code', // must exist in ERP items
            ],

            'orders.*.order_items.*.item_name' => [
                'required',
                'string',
                'max:255',
            ],
            
            'orders.*.order_items.*.item_qty' => [
                'required',
                'numeric',
                'min:0.01', // must be greater than zero
            ],

            'orders.*.order_items.*.item_rate' => [
                'required',
                'numeric',
                'min:0', // free items allowed, no negatives
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Attributes
            |--------------------------------------------------------------------------
            */
            'orders.*.order_items.*.item_attributes' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $ids = [];
                    $attributeNameIds = [];
                    $attributeValueIds = [];
                    $attributeNames = [];
                    $attributeValues = [];
                    foreach ($value as $index => $attr) {
                        // Skip if keys don't exist (if required, you can also fail here)
                        if (!isset($attr['id'], $attr['attribute_name_id'], $attr['attribute_value_id'], $attr['attribute_value'], $attr['attribute_name'])) {
                            continue;
                        }

                        // Check duplicate id
                        if (in_array($attr['id'], $ids)) {
                            $fail("Duplicate id '{$attr['id']}' found in {$attribute}.");
                            break;
                        }
                        $ids[] = $attr['id'];
                        
                        // Check duplicate attribute_name_id
                        if (in_array($attr['attribute_name_id'], $attributeNameIds)) {
                            $fail("Duplicate attribute_name_id '{$attr['attribute_name_id']}' found in {$attribute}.");
                            break;
                        }
                        $attributeNameIds[] = $attr['attribute_name_id'];

                        // Check duplicate attribute_value_id
                        if (in_array($attr['attribute_value_id'], $attributeValueIds)) {
                            $fail("Duplicate attribute_value_id '{$attr['attribute_value_id']}' found in {$attribute}.");
                            break;
                        }
                        $attributeValueIds[] = $attr['attribute_value_id'];

                        // Check duplicate attribute_value (string)
                        if (in_array($attr['attribute_value'], $attributeValues)) {
                            $fail("Duplicate attribute_value '{$attr['attribute_value']}' found in {$attribute}.");
                            break;
                        }
                        $attributeValues[] = $attr['attribute_value'];

                        // Check duplicate attribute_name (string)
                        if (in_array($attr['attribute_name'], $attributeNames)) {
                            $fail("Duplicate attribute_name '{$attr['attribute_name']}' found in {$attribute}.");
                            break;
                        }
                        $attributeNames[] = $attr['attribute_name'];
                    }
                },
            ],

            'orders.*.order_items.*.item_attributes.*.id' => [
                'required_with:orders.*.order_items.*.item_attributes',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    preg_match('/orders\.(\d+)\.order_items\.(\d+)\.item_attributes\.(\d+)\.id/', $attribute, $matches);
                    if (!$matches) {
                        return;
                    }
                    $orderIndex = $matches[1];
                    $itemIndex = $matches[2];

                    // Get the item_id for this item attribute
                    $data = request()->all();
                    $itemId = data_get($data, "orders.$orderIndex.order_items.$itemIndex.item_id");

                    $exists = ErpItemAttribute::where('item_id', $itemId)
                        ->where('id',$value)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $exists) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
            ],

            'orders.*.order_items.*.item_attributes.*.attribute_name_id' => [
                'required_with:orders.*.order_items.*.item_attributes',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    preg_match('/orders\.(\d+)\.order_items\.(\d+)\.item_attributes\.(\d+)\.attribute_name_id/', $attribute, $matches);
                    if (!$matches) {
                        return;
                    }
                    $orderIndex = $matches[1];
                    $itemIndex = $matches[2];

                    // Get the item_id for this item attribute
                    $data = request()->all();
                    $itemId = data_get($data, "orders.$orderIndex.order_items.$itemIndex.item_id");

                    $exists = ErpItemAttribute::where('item_id', $itemId)
                        ->where('attribute_group_id',$value)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $exists) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
            ],

            'orders.*.order_items.*.item_attributes.*.attribute_value_id' => [
                'required_with:orders.*.order_items.*.item_attributes',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    preg_match('/orders\.(\d+)\.order_items\.(\d+)\.item_attributes\.(\d+)\.attribute_value_id/', $attribute, $matches);
                    if (!$matches) {
                        return;
                    }
                    $orderIndex = $matches[1];
                    $itemIndex = $matches[2];

                    // Get the item_id for this item attribute
                    $data = request()->all();
                    $itemId = data_get($data, "orders.$orderIndex.order_items.$itemIndex.item_id");


                    $exists = ErpItemAttribute::where('item_id', $itemId)
                        ->whereRaw(
                            'JSON_CONTAINS(attribute_id, ?)',
                            [json_encode((string) $value)]
                        )
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $exists) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
            ],

            'orders.*.order_items.*.item_attributes.*.attribute_name' => [
                'required_with:orders.*.order_items.*.item_attributes',
                'string',
            ],

            'orders.*.order_items.*.item_attributes.*.attribute_value' => [
                'required_with:orders.*.order_items.*.item_attributes',
                'string',
            ],

        ];
    }

    public function messages(): array
    {
        return [
            'trip_date.after_or_equal' => 'Trip date cannot be in the past.',
            'orders.*.sale_type.in' => 'Sale type must be either sale or rental.',
            'orders.*.order_items.*.item_code.exists' => 'Invalid Item Code provided.',
            'orders.*.consignee_id.exists' => 'Invalid consignee provided.',
            'orders.*.shipping_address.country_id.exists' => 'Invalid Country selected.',
            'orders.*.shipping_address.state_id.exists' => 'Invalid State selected.',
            'orders.*.shipping_address.city_id.exists' => 'Invalid City selected.',
        ];
    }

    public function attributes(): array
    {
        return [
            'trip_number' => 'Trip Number',
            'trip_date' => 'Trip Date',
            'store_id' => 'Store',
            'organization_id' => 'Organization',

            'orders' => 'Orders',
            'orders.*.ref_order_number' => 'Reference Order Number',
            'orders.*.consignee_id' => 'Consignee',
            'orders.*.sale_type' => 'Sale Type',

            'orders.*.shipping_address.country_id' => 'Country',
            'orders.*.shipping_address.state_id' => 'State',
            'orders.*.shipping_address.city_id' => 'City',
            'orders.*.shipping_address.pincode' => 'Pincode',
            'orders.*.shipping_address.address' => 'Shipping Address',

            'orders.*.order_items' => 'Order Items',
            'orders.*.order_items.*.item_id' => 'Item ID',
            'orders.*.order_items.*.item_code' => 'Item Code',
            'orders.*.order_items.*.item_name' => 'Item Name',
            'orders.*.order_items.*.item_qty' => 'Item Quantity',
            'orders.*.order_items.*.item_rate' => 'Item Rate',

            'orders.*.order_items.*.item_attributes.*.id' => 'Attribute ID',
            'orders.*.order_items.*.item_attributes.*.attribute_name_id' => 'Attribute Name ID',
            'orders.*.order_items.*.item_attributes.*.attribute_value_id' => 'Attribute Value ID',
            'orders.*.order_items.*.item_attributes.*.attribute_name' => 'Attribute Name',
            'orders.*.order_items.*.item_attributes.*.attribute_value' => 'Attribute Value',
        ];
    }
}
