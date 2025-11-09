<?php

namespace App\Http\Controllers\Sales;

use App\Helpers\Common\OrganizationHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomerLocation;
use Exception;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function getCustomerSubStore(Request $request)
    {
        try {
            $customerId = $request -> customer_id;
            $organizationId = OrganizationHelper::getAuthenticatedOrganization() ?-> id;
            $storeId = $request -> store_id;
            $customerSubStore = CustomerLocation::with('sub_store')->where('organization_id', $organizationId)
                        -> where('location_id', $storeId) -> where('customer_id', $customerId)
                        -> first();
            return response() -> json([
                'status' => 'success',
                'data' => $customerSubStore?->sub_store ?? ''
            ]);
        } catch (Exception $ex) {
            return response()->json(['error' => $ex->getMessage()], 500);
        }
    }
}
