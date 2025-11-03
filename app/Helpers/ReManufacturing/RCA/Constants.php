<?php
namespace App\Helpers\ReManufacturing\RCA;
use App\Helpers\ReManufacturing\RepairOrder\Constants as RepConstants;
use App\Helpers\ServiceParametersHelper;
//Repair Order Service
class Constants
{
    const SERVICE_ALIAS = "rca";
    const SERVICE_NAME = "Rca";
    const PARAMETERS = [
       [
            "name" => ServiceParametersHelper::REFERENCE_FROM_SERVICE_PARAM, 
            "applicable_values" => [RepConstants::SERVICE_ALIAS], 
            "default_value" => [RepConstants::SERVICE_ALIAS], 
            'is_multiple' => true,
            'service_level_visibility' => true
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
            "applicable_values" =>ServiceParametersHelper::BACK_DATE_ALLOW_PARAM_VALUES,
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
    ];
}
