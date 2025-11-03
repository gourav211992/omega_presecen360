<?php

namespace App\Http\Requests\Integration;

use App\Models\ErpItemAttribute;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFurlencoSaleOrderRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'trip_number' => [
                'required',
                'string',
                'max:50',
                'exists:erp_trip_plan_headers,document_number',
            ],
            'ref_order_number' => [
                'required',
                'string',
                'max:50',
                Rule::exists('erp_sale_orders', 'ref_order_number')
                    ->where(function ($query) {
                        $query->where('trip_number', $this->trip_number);
                    })
            ],
            'trip_date' => [
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

            'sale_type' => [
                'required_if:delivery_skus.*.action_type,add',
                'nullable',
                'string',
                'max:50',
                'in:sale,rental'
            ],

            'consignee_id' => ['nullable', 'integer', 'exists:erp_consignees,id'],

            'delivery_skus' => ['required', 'array', 'min:1'],

            'delivery_skus.*.action_type' => ['required', Rule::in(['add', 'update', 'delete'])],

            'delivery_skus.*.item_id' => [
                'required',
                'integer',
                'distinct',
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
                'integer',
                'min:1',
            ],

            'delivery_skus.*.item_rate' => [
                'required_if:delivery_skus.*.action_type,add,update',
                'numeric',
                'min:0',
            ],

            /*
            |--------------------------------------------------------------------------
            | Item Attributes
            |--------------------------------------------------------------------------
            */
            'delivery_skus.*.item_attributes' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    $uniqueChecks = [
                        'id' => [],
                        'attribute_name_id' => [],
                        'attribute_value_id' => [],
                        'attribute_name' => [],
                        'attribute_value' => [],
                    ];

                    foreach ($value as $index => $attr) {
                        foreach ($uniqueChecks as $key => &$seen) {
                            if (!isset($attr[$key])) continue;

                            if (in_array($attr[$key], $seen, true)) {
                                $fail("Duplicate {$key} '{$attr[$key]}' found in {$attribute}.");
                                break 2;
                            }

                            $seen[] = $attr[$key];
                        }
                    }
                }
            ],

            'delivery_skus.*.item_attributes.*.id' => [
                'required_with:delivery_skus.*.item_attributes',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    preg_match('/delivery_skus\.(\d+)\.item_attributes\.(\d+)\.id/', $attribute, $matches);
                    if (!$matches) {
                        return;
                    }
                    $index = $matches[1];

                    // Get the item_id for this item attribute
                    $data = request()->all();
                    $itemId = data_get($data, "delivery_skus.$index.item_id");
                    
                    $exists = ErpItemAttribute::where('item_id', $itemId)
                        ->where('id',$value)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $exists) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
            ],

            'delivery_skus.*.item_attributes.*.attribute_name_id' => [
                'required_with:delivery_skus.*.item_attributes',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    preg_match('/delivery_skus\.(\d+)\.item_attributes.(\d+)\.attribute_name_id/', $attribute, $matches);
                    if (!$matches) {
                        return;
                    }
                    $index = $matches[1];

                    // Get the item_id for this item attribute
                    $data = request()->all();
                    $itemId = data_get($data, "delivery_skus.$index.item_id");

                    $exists = ErpItemAttribute::where('item_id', $itemId)
                        ->where('attribute_group_id',$value)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (! $exists) {
                        $fail("The selected {$attribute} is invalid.");
                    }
                },
            ],

            'delivery_skus.*.item_attributes.*.attribute_value_id' => [
                'required_with:delivery_skus.*.item_attributes',
                'integer',
                // custom validation: ensure value exists in JSON column
                function ($attribute, $value, $fail) {
                    preg_match('/delivery_skus\.(\d+)\.item_attributes.(\d+)\.attribute_value_id/', $attribute, $matches);
                    if (!$matches) {
                        return;
                    }
                    $index = $matches[1];

                    // Get the item_id for this item attribute
                    $data = request()->all();
                    $itemId = data_get($data, "delivery_skus.$index.item_id");


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

            'delivery_skus.*.item_attributes.*.attribute_name' => [
                'required_with:delivery_skus.*.item_attributes',
                'string',
            ],

            'delivery_skus.*.item_attributes.*.attribute_value' => [
                'required_with:delivery_skus.*.item_attributes',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping Address
            |--------------------------------------------------------------------------
            */

            'shipping_address' => [
                'nullable',
                'array',
            ],

            'shipping_address.country_id' => [
                'required_with:shipping_address',
                'integer',
                'exists:mysql_master.countries,id',
            ],

            'shipping_address.state_id' => [
                'required_with:shipping_address',
                'integer',
                'exists:mysql_master.states,id',
            ],

            'shipping_address.city_id' => [
                'required_with:shipping_address',
                'integer',
                'exists:mysql_master.cities,id',
            ],

            'shipping_address.pincode' => [
                'required_with:shipping_address',
                'string',
                'max:6',
            ],

            'shipping_address.address' => [
                'required_with:shipping_address',
                'string',
                'max:250',
            ],
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
