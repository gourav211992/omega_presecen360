<?php

namespace App\Helpers\AuthUser;

use App\Models\Country;
use App\Models\LoginActivity;
use App\Models\Organization;

class UtlilityHelper
{
    const DEFAULT_FALLBACK_TIMEZONE = "Asia/Kolkata";
    //Function to get the current timezone for the logged in user
    public static function getAuthUserTimezone($authUser) : string
    {
        $timeZone = self::DEFAULT_FALLBACK_TIMEZONE;
        //Retrieve the last data from login activity
        $loginActivity = LoginActivity::select('timezone') -> where('user_id', $authUser -> auth_user_id) -> latest() -> first();
        if ($loginActivity && isset($loginActivity -> timezone)) {
            $timeZone = $loginActivity -> timezone;
        } else {
            //Retrieve the timezone from organization
            $organization = Organization::select('id', 'country_id') -> where('id', $authUser -> organization_id) -> first();
            if (!$organization) {
                return $timeZone;
            }
            //Return according to country
            $countryCode = Country::select('id', 'code') -> where('id', $organization -> id) -> first() ?-> code;
            if ($countryCode) {
                //Return the first timezone if found
                $timeZones = \DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $countryCode);
                if (count($timeZones) > 0) {
                    $timeZone = $timeZones[0];
                }
            }
        }
        return $timeZone;
    }
}
