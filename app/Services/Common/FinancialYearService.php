<?php

namespace App\Services\Common;

use App\Models\ErpFinancialYear;
use P360\Core\Interfaces\TagCacheInterface;

class FinancialYearService
{
    /**
     * Constructor to inject cache dependency
     */
    public function __construct(
        protected TagCacheInterface $cache
    )
    {}

    /**
     * Get list of financial years for an authenticated user.
     * Uses caching to avoid repeated DB queries.
     *
     * @param mixed $authUser The authenticated user object
     * @return array|null
     */
    public function getFinancialYears($authUser)
    {
        // Unique cache key prefix for financial years
        $cacheKey = "fys:accesss:";

        return $this->cache->remember(
            key: $this->cache->key($authUser, $cacheKey),    // Cache key includes user
            ttl: $this->cache->ttl(),                        // Cache TTL
            callback: fn() => $this->getFYsData($authUser),  // Fetch if not cached
            storeName: 'redis_p360'                          // Store in Redis
        );
    }

    /**
     * Fetch and filter financial years from DB based on user authorization.
     *
     * @param mixed $authUser The authenticated user
     * @return array|null
     */
    private function getFYsData($authUser) {
        $currentUserId   = $authUser->auth_user_id;
        $currentUserType = $authUser->authenticable_type;
        $organizationId  = $authUser->organization_id;

        // Query financial years by organization if provided
        if ($organizationId) {
            $financialYears = ErpFinancialYear::where('organization_id', $organizationId)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $financialYears = ErpFinancialYear::orderBy('id', 'desc')->get();
        }

        // Process if we have financial years
        if ($financialYears->isNotEmpty()) {
            return $financialYears
                ->filter(function ($financialYear) use ($currentUserId, $currentUserType) {
                    // If FY is closed, check access restrictions
                    if ($financialYear->fy_close === true && is_array($financialYear->access_by)) {
                        return !collect($financialYear->access_by)->contains(function ($entry) use ($currentUserId, $currentUserType) {
                            return isset($entry['user_id'], $entry['authorized'], $entry['authenticable_type'], $entry['locked']) &&
                                $entry['user_id'] == $currentUserId &&
                                $entry['authenticable_type'] == $currentUserType &&
                                $entry['authorized'] === false && // denied
                                $entry['locked'] !== true;        // but not locked
                        });
                    }
                    return true; // Allow if not closed
                })
                ->map(function ($financialYear) {
                    // Format year range as e.g. "2022-23"
                    $startYear    = \Carbon\Carbon::parse($financialYear->start_date)->format('Y');
                    $endYearShort = \Carbon\Carbon::parse($financialYear->end_date)->format('y');

                    return [
                        'id'              => $financialYear->id,
                        'alias'           => $financialYear->alias,
                        'start_date'      => $financialYear->start_date,
                        'end_date'        => $financialYear->end_date,
                        'range'           => $startYear . '-' . $endYearShort,
                        'authorized_users'=> $financialYear->authorizedUsers() // related users
                    ];
                })
                ->values();
        }

        return null;
    }

    /**
     * Get currently active financial year for an authenticated user.
     * Uses caching to improve performance.
     *
     * @param mixed $authUser The authenticated user object
     * @return array|null
     */
    public function getFinancialYear($date, $authUser): mixed
    {
        $cacheKey = "fy:accesss:";

        return $this->cache->remember(
            key: $this->cache->key($authUser, $cacheKey),
            ttl: $this->cache->ttl(),
            callback: fn() => $this->getFYData($date, $authUser),
            storeName: 'redis_p360'
        );
    }

    /**
     * Fetch the currently active financial year from DB.
     * Determines if the user has access based on authorization rules.
     *
     * @param mixed $authUser The authenticated user
     * @return array Financial year data with access flag
     */
    public function getFYData($date, $authUser): array
    {
        $startDate = request()->cookie('fyear_start_date', $date);
        $endDate   = request()->cookie('fyear_end_date', $date);

        // Get financial year covering current range
        $financialYear = ErpFinancialYear::where('start_date', '<=', $startDate)
            ->where('end_date', '>=', $endDate)
            ->first();

        // If no FY found, return default empty structure
        if (!$financialYear) {
            return array_fill_keys([
                'alias', 'id', 'start_date', 'end_date', 'lock_fy',
                'fy_close', 'range', 'authorized'
            ], '');
        }

        $startYear    = \Carbon\Carbon::parse($financialYear->start_date)->year;
        $endYearShort = \Carbon\Carbon::parse($financialYear->end_date)->format('y');

        // Default assumption: authorized
        $authorized = true;

        // If FY is closed, check if current user is restricted
        if ($financialYear->fy_close && is_array($financialYear->access_by)) {
            $authorized = !collect($financialYear->access_by)->contains(fn ($entry) =>
                ($entry['user_id'] ?? null) === $authUser->auth_user_id &&
                ($entry['authenticable_type'] ?? null) === $authUser->authenticable_type &&
                (
                    ($entry['authorized'] ?? true) === false || // explicitly denied
                    ($entry['locked'] ?? false) === true        // or locked
                )
            );
        }

        // Return structured FY data
        return [
            'alias'       => $financialYear->alias,
            'id'          => $financialYear->id,
            'start_date'  => $financialYear->start_date,
            'end_date'    => $financialYear->end_date,
            'lock_fy'     => $financialYear->lock_fy,
            'fy_close'    => $financialYear->fy_close,
            'range'       => "{$startYear}-{$endYearShort}",
            'authorized'  => $authorized,
        ];
    }
}
