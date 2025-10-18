<?php

namespace App\Helpers\Configuration;

use App\Models\Configuration;
use App\Helpers\Helper as OldHelper;
use App\Helpers\ConstantHelper;
use App\Models\Book;
use App\Helpers\Configuration\Constants as ConfigConstant;

class Helper
{
    public static function getConfigurationValueOfOrg(string $key, int $orgId) : string
    {
        $config = Configuration::select('id', 'config_value') -> where('type', ConfigConstant::ORG_MORPH_TYPE) 
        -> where('type_id', $orgId) -> where('config_key', $key) -> first();
        $value = isset($config -> config_value) ? $config -> config_value : "";
        return $value;
    }

    public static function getConfigurationValueOfCompany(string $key, int $companyId) : string
    {
        $config = Configuration::select('id', 'config_value') -> where('type', ConfigConstant::COMPANY_MORPH_TYPE) 
        -> where('type_id', $companyId) -> where('config_key', $key) -> first();
        $value = isset($config -> config_value) ? $config -> config_value : "";
        return $value;
    }

    public static function getBookSeriesByType($serviceAlias, $menuServiceAlias = '', $isEdit = false,$series_type = null)
    {
        $servicesBooks = OldHelper::getAccessibleServicesFromMenuAlias($menuServiceAlias, $isEdit ? $serviceAlias : '');
        $bookIds = $servicesBooks['books'];
        $allBookAccess = $servicesBooks['all_book_access'];
        $series = Book::withDefaultGroupCompanyOrg()
            ->whereHas('org_service', function ($orgService) use ($serviceAlias) {
                $orgService->where('alias', $serviceAlias);
            })->when($series_type, function ($query) use ($series_type) {
                $query->whereHas('patterns', function ($patterns) use ($series_type) {
                    $patterns->where('series_numbering', $series_type);
                });
            })->when($allBookAccess === false, function ($bookQuery) use ($bookIds) {
                $bookQuery->whereIn('id', $bookIds);
            })->where('status', ConstantHelper::ACTIVE)->where('manual_entry', 1);
        //Code modified due to change in requirement -> Jagdeep
        return $series;
    }
}
