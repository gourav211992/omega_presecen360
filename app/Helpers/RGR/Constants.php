<?php
namespace App\Helpers\RGR;
use App\Helpers\ServiceParametersHelper;
use App\Helpers\ConstantHelper;
//Advanced Shipment Notification Service
class Constants
{
    const SERVICE_ALIAS = "rgr";
    const SERVICE_NAME = "Return Goods Receipt";
    const PARAMETERS = [
       [
            "name" => ServiceParametersHelper::REFERENCE_FROM_SERVICE_PARAM, 
            "applicable_values" => [ConstantHelper::PDS_SERVICE_ALIAS], 
            "default_value" => [ConstantHelper::PDS_SERVICE_ALIAS], 
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
        [
            "name" => ServiceParametersHelper::OK_TO_RECIEVE_BOOK_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => false,
            'service_level_visibility' => false
        ],

         // RCA series parameters

        [
            "name" => ServiceParametersHelper::RCA_TRANSIT_DAMAGE_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::RCA_PACKAGE_MISSING_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::RCA_WRONG_PRODUCT_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::RCA_DELIVERY_CANCEL_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::RCA_MISSING_EXTRA_ITEMS_PARAM,
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => true
        ],
        [
            "name" => ServiceParametersHelper::RCA_REPLACEMENT_PARAM, 
            "applicable_values" => [],
            "default_value" => [],
            'is_multiple' => true,
            'service_level_visibility' => true
        ],
    ];

    const RGR_SEGREGATION_WRONG_PRODUCT = "Wrong Product";
    const RGR_SEGREGATION_PACK_MISSING = "Package Missing";
    const RGR_SEGREGATION_DELIVERY_CANCEL = "Delivery Cancel";
    const RGR_SEGREGATION_REPLACEMENT_ITEM = "Replacement Item"; 
    const RGR_SEGREGATION_TRANSIT_DAMAGE  = "Transit Damage";
    const RGR_SEGREGATION_OK_TO_RECIEVE = "Ok to recieve";
    const RGR_SEGREGATION_EXTRA_ITEM  = "Extra Item";
    const RGR_SEGREGATION_MISSING_ITEM = "Missing Item";

    const DEFECT_SEVERITY_MINOR = 'Minor';
    const DEFECT_SEVERITY_MAJOR = 'Major';
    const DEFECT_SEVERITY_SCRAP = 'Scrap';

    const DEFECT_SEVERITY_LEVELS = [
        ['label' => self::DEFECT_SEVERITY_MINOR, 'value' => self::DEFECT_SEVERITY_MINOR],
        ['label' => self::DEFECT_SEVERITY_MAJOR, 'value' => self::DEFECT_SEVERITY_MAJOR],
        ['label' => self::DEFECT_SEVERITY_SCRAP, 'value' => self::DEFECT_SEVERITY_SCRAP],
    ];


     const RGR_STATUSES = [
            self::RGR_SEGREGATION_PACK_MISSING,
             self::RGR_SEGREGATION_WRONG_PRODUCT,
             self::RGR_SEGREGATION_DELIVERY_CANCEL,
             self::RGR_SEGREGATION_TRANSIT_DAMAGE,
             self::RGR_SEGREGATION_EXTRA_ITEM,
             self::RGR_SEGREGATION_REPLACEMENT_ITEM,
             self::RGR_SEGREGATION_MISSING_ITEM,
     ];

    const DAMAGE_NATURE_NO_DAMAGE = 'No Damage';
    const DAMAGE_NATURE_CUSTOMER_DAMAGE = 'Customer Damage';
    const DAMAGE_NATURE_TRANSIT_HANDLE_DAMAGE = 'Transit / Handling Damage';
    const DAMAGE_NATURE_WEAR_AND_TEAR = 'Wear and Tear';

    const DAMAGE_NATURES = [
        ['label' => self::DAMAGE_NATURE_NO_DAMAGE, 'value' => self::DAMAGE_NATURE_NO_DAMAGE],
        ['label' => self::DAMAGE_NATURE_CUSTOMER_DAMAGE, 'value' => self::DAMAGE_NATURE_CUSTOMER_DAMAGE],
        ['label' => self::DAMAGE_NATURE_TRANSIT_HANDLE_DAMAGE, 'value' => self::DAMAGE_NATURE_TRANSIT_HANDLE_DAMAGE],
        ['label' => self::DAMAGE_NATURE_WEAR_AND_TEAR, 'value' => self::DAMAGE_NATURE_WEAR_AND_TEAR],
    ];

    const DAMAGE_TYPE_TRANSIT = 'Transit Damage';
    const DAMAGE_TYPE_WRONG_PRODUCT = 'Wrong Product';
    const DAMAGE_TYPE_MISSING_PRODUCT = 'Missing Product';
    const DAMAGE_TYPE_EXTRA_ASSET = 'Extra Asset';

    const DAMAGE_TYPE = [
        ['label' => self::DAMAGE_TYPE_TRANSIT, 'value' => self::DAMAGE_TYPE_TRANSIT],
        ['label' => self::DAMAGE_TYPE_WRONG_PRODUCT, 'value' => self::DAMAGE_TYPE_WRONG_PRODUCT],
        ['label' => self::DAMAGE_TYPE_MISSING_PRODUCT, 'value' => self::DAMAGE_TYPE_MISSING_PRODUCT],
        ['label' => self::DAMAGE_TYPE_EXTRA_ASSET, 'value' => self::DAMAGE_TYPE_EXTRA_ASSET],
    ];

    // REPAIR ACTION constants
    const REPAIR_ACTION_CHANGE_DEFECT_LABEL = 'Change Defect Severity';
    const REPAIR_ACTION_CHANGE_DEFECT_VALUE = 'change_defect_severity';

    const REPAIR_ACTION_SEND_VENDOR_LABEL = 'Send to Vendor';
    const REPAIR_ACTION_SEND_VENDOR_VALUE = 'send_to_vendor';

    const REPAIR_ACTION_SCRAP_LABEL = 'Scrap';
    const REPAIR_ACTION_SCRAP_VALUE = 'scrap';

    const REPAIR_ACTION_REPAIR_LABEL = 'Repair';
    const REPAIR_ACTION_REPAIR_VALUE = 'repair';

    // QC ACTION constants
    const QC_ACTION_REJECT_LABEL = 'Reject';
    const QC_ACTION_REJECT_VALUE = 'reject';

    const QC_ACTION_APPROVED_WITH_DEVIATION_LABEL = 'Approved with Deviation';
    const QC_ACTION_APPROVED_WITH_DEVIATION_VALUE = 'approved_with_deviation';

    const QC_ACTION_APPROVE_LABEL = 'Approve';
    const QC_ACTION_APPROVE_VALUE = 'approve';

    // Arrays using constants
    const REPAIR_ACTION = [
        ['label' => self::REPAIR_ACTION_CHANGE_DEFECT_LABEL, 'value' => self::REPAIR_ACTION_CHANGE_DEFECT_VALUE],
        ['label' => self::REPAIR_ACTION_SEND_VENDOR_LABEL, 'value' => self::REPAIR_ACTION_SEND_VENDOR_VALUE],
        ['label' => self::REPAIR_ACTION_SCRAP_LABEL, 'value' => self::REPAIR_ACTION_SCRAP_VALUE],
        ['label' => self::REPAIR_ACTION_REPAIR_LABEL, 'value' => self::REPAIR_ACTION_REPAIR_VALUE],
    ];

    const QC_ACTION = [
        ['label' => self::QC_ACTION_REJECT_LABEL, 'value' => self::QC_ACTION_REJECT_VALUE],
        ['label' => self::QC_ACTION_APPROVED_WITH_DEVIATION_LABEL, 'value' => self::QC_ACTION_APPROVED_WITH_DEVIATION_VALUE],
        ['label' => self::QC_ACTION_APPROVE_LABEL, 'value' => self::QC_ACTION_APPROVE_VALUE],
    ];

}
