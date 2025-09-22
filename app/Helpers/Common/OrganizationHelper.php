<?php

namespace App\Helpers\Common;

use App\Models\Organization;
use P360\Core\Interfaces\TagCacheInterface;

class OrganizationHelper
{
    /**
     * Get the authenticated user's organization (cached).
     */
    public static function getAuthenticatedOrganization(): ?Organization
    {
        $authUser = request()->user();
        $ck = "iam:organization";

        $key = app(TagCacheInterface::class)->key($authUser, $ck);
        $ttl = app(TagCacheInterface::class)->ttl();

        return app(TagCacheInterface::class)->remember(
            key: $key,    // Cache key includes user
            ttl: $ttl,                        // Cache TTL
            callback: fn() => Organization::find($authUser->organization_id),  // Fetch if not cached
            storeName: 'redis_p360'                          // Store in Redis
        );
    }

    /**
     * Convenience accessors for company, group, and org IDs.
     */
    public static function getCompanyId(): ?int
    {
        return self::getAuthenticatedOrganization()?->company_id;
    }

    public static function getGroupId(): ?int
    {
        return self::getAuthenticatedOrganization()?->group_id;
    }

    public static function getOrganizationId(): ?int
    {
        return self::getAuthenticatedOrganization()?->id;
    }
}
