<?php
namespace App\Helpers\Sales;

use App\Helpers\ConstantHelper;
use App\Helpers\ServiceParametersHelper;

class SaleOrderConstants
{
    //SERVICE ALIAS
    public const SERVICE_ALIAS = "so";
    public const SERVICE_NAME = "Sales Order";
    //SERVICE PARAMETERS
    const SERVICE_PARAMETERS = [
        [
            "name" => ServiceParametersHelper::REFERENCE_FROM_SERVICE_PARAM, //Name of the parameter
            "applicable_values" => ["0", ConstantHelper::SQ_SERVICE_ALIAS, ConstantHelper::PO_SERVICE_ALIAS, ConstantHelper::JO_SERVICE_ALIAS], //All possible values
            "default_value" => ["0", ConstantHelper::SQ_SERVICE_ALIAS, ConstantHelper::PO_SERVICE_ALIAS, ConstantHelper::JO_SERVICE_ALIAS], //Default selected value(s)
            'is_multiple' => true, // Whether or not to allow multiple selection
            'service_level_visibility' => true, // Whether or not to show this parameter in UI
        ],
        [
            "name" => ServiceParametersHelper::REFERENCE_FROM_SERIES_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => false
        ],
        [
            "name" => ServiceParametersHelper::BACK_DATE_ALLOW_PARAM,
            "applicable_values" => ServiceParametersHelper::BACK_DATE_ALLOW_PARAM_VALUES,
            "default_value" => ['yes'],
            'is_multiple' => false,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::FUTURE_DATE_ALLOW_PARAM,
            "applicable_values" => ServiceParametersHelper::FUTURE_DATE_ALLOW_PARAM_VALUES,
            "default_value" => ['yes'],
            'is_multiple' => false,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::GOODS_SERVICES_PARAM,
            "applicable_values" => ServiceParametersHelper::GOODS_SERVICES_PARAM_VALUES,
            "default_value" => ['Goods'],
            'is_multiple' => false,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::SO_GATE_ENTRY_REQUIRED_PARAM,
            "applicable_values" => ServiceParametersHelper::SO_GATE_ENTRY_REQUIRED_PARAM_VALUES,
            "default_value" => ['Yes'],
            'is_multiple' => false,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::SO_CUSTOMER_PO_REQUIRED_PARAM,
            "applicable_values" => ServiceParametersHelper::SO_CUSTOMER_PO_REQUIRED_PARAM_VALUES,
            "default_value" => ['No'],
            'is_multiple' => false,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::SO_CUSTOMER_DISPLAY_BOM_PARAM,
            "applicable_values" => ServiceParametersHelper::SO_CUSTOMER_DISPLAY_BOM_PARAM_VALUES,
            "default_value" => ['Yes'],
            'is_multiple' => false,
            'service_level_visibility' => true
        ],
    ];
}
