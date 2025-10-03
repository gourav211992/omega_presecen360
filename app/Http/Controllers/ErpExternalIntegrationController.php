<?php

namespace App\Http\Controllers;

use App\Helpers\Helper;
use Illuminate\Http\Request;
use App\Helpers\ConstantHelper;
use App\Helpers\SaleModuleHelper;
use App\Models\AuthUser;
use App\Models\ErpStore;
use App\Models\Organization;
use App\Models\ErpSubStoreParent;
use App\Models\ERP\ErpStockStoreMapping;
use App\Models\ERP\ErpExternalIntegration;
use App\Http\Requests\ErpExternalIntegrationRequest;
use App\Models\ErpSubStore;
use Exception;
use Illuminate\Support\Facades\DB; 
use Yajra\DataTables\Facades\DataTables;

class ErpExternalIntegrationController extends Controller
{
    
     public function index(Request $request)
    {    
        $user = Helper::getAuthenticatedUser();

        $erpExternalIntegrations = ErpExternalIntegration::with('customer', 'soBook', 'tripBook', 'dnote', 'organization', 'store')
            ->whereCompanyId($user->company_id)
            ->whereGroupId($user->group_id);
       
       if ($request->ajax()) {
        return DataTables::of($erpExternalIntegrations)
            ->addIndexColumn()
            ->addColumn('customer', fn($row) => $row->customer?->company_name ?? 'AS')
            ->editColumn('soBook', fn($row) => $row->soBook?->book_name ?? 'N/A')
            ->editColumn('tripBook', fn($row) => $row->tripBook?->book_name ?? 'N/A')
            ->editColumn('dnote', fn($row) => $row->dnote?->book_name ?? 'N/A')
            ->editColumn('organization', fn($row) => $row->organization?->name ?? 'N/A')
            ->editColumn('store', fn($row) => $row->store?->store_name ?? 'N/A')
            ->addColumn('status', fn($row) => '<span class="badge rounded-pill badge-light-' . ($row->status === 'active' ? 'success' : 'danger') . '">'
                . ucfirst($row->status) . '</span>')
            ->addColumn('action', function ($row) {
                $editUrl = route('external-integration.edit', $row->id);
                $deleteUrl = route('external-integration.destroy', $row->id);
                return '<div class="dropdown">
                            <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                                <i data-feather="more-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item text-primary" href="' . $editUrl . '">
                                    <i data-feather="edit-3" class="me-50"></i>
                                    <span>Edit</span>
                                </a>
                                <a class="dropdown-item text-danger delete-btn"
                                    href="javascript:void(0);"
                                    data-url="'.$deleteUrl.'"
                                    data-message="Are you sure you want to delete this record?">
                                    <i data-feather="trash-2" class="me-50"></i>
                                    <span>Delete</span>
                                </a>
                            </div>
                        </div>';
            })
            // Searching for relationships
            ->filterColumn('customer', fn($query, $keyword) =>
                $query->whereHas('customer', fn($q) => $q->where('company_name', 'like', "%{$keyword}%"))
            )
            ->filterColumn('soBook', fn($query, $keyword) =>
                $query->whereHas('soBook', fn($q) => $q->where('book_name', 'like', "%{$keyword}%"))
            )
            ->filterColumn('tripBook', fn($query, $keyword) =>
                $query->whereHas('tripBook', fn($q) => $q->where('book_name', 'like', "%{$keyword}%"))
            )
            ->filterColumn('dnote', fn($query, $keyword) =>
                $query->whereHas('dnote', fn($q) => $q->where('book_name', 'like', "%{$keyword}%"))
            )
            ->filterColumn('organization', fn($query, $keyword) =>
                $query->whereHas('organization', fn($q) => $q->where('name', 'like', "%{$keyword}%"))
            )
            ->filterColumn('store', fn($query, $keyword) =>
                $query->whereHas('store', fn($q) => $q->where('store_name', 'like', "%{$keyword}%"))
            )
            // Sorting for relationships
            ->orderColumn('customer', fn($query, $order) =>
                $query->join('erp_customers', 'erp_external_integrations.customer_id', '=', 'erp_customers.id')
                    ->orderBy('erp_customers.company_name', $order)
            )
            ->orderColumn('soBook', fn($query, $order) =>
                $query->join('erp_books as soBooks', 'erp_external_integrations.so_book_id', '=', 'soBooks.id')
                    ->orderBy('soBooks.book_name', $order)
            )
            ->orderColumn('tripBook', fn($query, $order) =>
                $query->join('erp_books as tripBooks', 'erp_external_integrations.trip_book_id', '=', 'tripBooks.id')
                    ->orderBy('tripBooks.book_name', $order)
            )
            ->orderColumn('dnote', fn($query, $order) =>
                $query->join('erp_books as dnoteBooks', 'erp_external_integrations.dnote_id', '=', 'dnoteBooks.id')
                    ->orderBy('dnoteBooks.book_name', $order)
            )
            ->orderColumn('organization', fn($query, $order) =>
                $query->join('organizations', 'erp_external_integrations.organization_id', '=', 'organizations.id')
                    ->orderBy('organizations.name', $order)
            )
            ->orderColumn('store', fn($query, $order) =>
                $query->join('erp_stores', 'erp_external_integrations.store_id', '=', 'erp_stores.id')
                    ->orderBy('erp_stores.store_name', $order)
            )
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

        return view('erp-external.index');
    }

    public function create()
    {
        $user = Helper::getAuthenticatedUser();
        $groupId = $user?->group_id;
        $useRole = AuthUser::where('id', $user->auth_user_id)->first();
        $isSuperAdmin = ($useRole && isset($useRole->user_type) && $useRole->user_type === 'IAM-SUPER');

        $allOrganizations = Organization::where('group_id' ,$groupId)
            ->where('company_id' ,$user->company_id)->where('status', 'active')
            ->get();
        

        $dn_parentUrl = 'delivery-note';
        $dn_servicesAliasParam = ConstantHelper::DELIVERY_CHALLAN_SERVICE_ALIAS;
        $dnbook = Helper::getBookSeriesNew($dn_servicesAliasParam, $dn_parentUrl, false,true)->get();

        $Sale_parentUrl = 'sales-order';
        $Sale_servicesAliasParam = ConstantHelper::SO_SERVICE_ALIAS;
        $sobook = Helper::getBookSeriesNew($Sale_servicesAliasParam, $Sale_parentUrl, false,true)->get();
        
        $trip_parentUrl = 'trip-plan';
        $trip_servicesAliasParam = ConstantHelper::TRIP_SERVICE_ALIAS;
        $tripbook = Helper::getBookSeriesNew($trip_servicesAliasParam, $trip_parentUrl, false,true)->get();
        
      

        $status = ConstantHelper::STATUS;
        return view('erp-external.create', compact('status','tripbook','sobook','dnbook','allOrganizations'));
    }

    public function store(ErpExternalIntegrationRequest $request)
    {
        $user = Helper::getAuthenticatedUser();

        DB::beginTransaction();
        try {
            // Validate uniqueness: organization + store
            $exists = ErpExternalIntegration::where('organization_id', $request->organization_id)
                ->where('store_id', $request->store_id)
                ->where('group_id' ,$user->group_id)
                ->where('company_id' ,$user->company_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'This store already exists for the selected organization.',
                ], 422);
            }

            // Save main external integration
           ErpExternalIntegration::create([
                'group_id'        => $user->group_id,
                'company_id'      => $user->company_id,
                'organization_id' => $request->organization_id,
                'trip_book_id'    => $request->trip_book_id,
                'so_book_id'      => $request->so_book_id,
                'dnote_book_id'   => $request->dnote_book_id,
                'store_id'        => $request->store_id,
                'customer_id'     => $request->customer_id,
                'status'          => $request->status,
            ]);
            // Save sub-store mappings
            foreach ($request->data as $data) {
                $subStoreId = $data['subLocation_id'];

                $exists = ErpStockStoreMapping::where('organization_id', $request->organization_id)
                    ->where('store_id', $request->store_id)
                    ->where('sub_store_id', $subStoreId)
                    ->exists();

                if ($exists) {
                    DB::rollBack();
                    $subStore = ErpSubStore::find($subStoreId);

                    return response()->json([
                        'status' => false,
                        'message' => ($subStore?->name ?? 'This') . " sub-store is duplicate for this store.",
                    ], 422);

                }

                ErpStockStoreMapping::create([
                    'stock_type'      => $data['stock_type'],
                    'group_id'        => $user->group_id,
                    'company_id'      => $user->company_id,
                    'organization_id' => $request->organization_id,
                    'store_id'        => $request->store_id,
                    'sub_store_id'    => $subStoreId,
                    'is_primary'      => isset($data['is_primary']) ?$data['is_primary']: 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Record created successfully',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error managing sub store: ' . $e->getMessage(),
            ], 500);
        }
    }



    public function edit($id)
    {
        $user = Helper::getAuthenticatedUser();
      
        $allOrganizations = Organization::where('group_id',$user->group_id)->where('company_id',$user->company_id)
                ->where('status', 'active')
                ->get();
        $external = ErpExternalIntegration::with(['customer','stockStoreMapping'])
                ->where('id', $id)
                ->first();

        if (!$external) {
            return redirect()->back()->with('error', 'External Integration not found.');
        }

        $dnParentUrl = 'delivery-note';
        $dn_servicesAliasParam = SaleModuleHelper::SALES_INVOICE_DN_TYPE;
        $dnbook = Helper::getBookSeriesNew($dn_servicesAliasParam, $dnParentUrl, true,true)->get();
        
        $saleParentUrl = 'sales-order';
        $Sale_servicesAliasParam = ConstantHelper::SO_SERVICE_ALIAS;
        $sobook = Helper::getBookSeriesNew($Sale_servicesAliasParam, $saleParentUrl, true,true)->get();
        
        $tripParentUrl = 'trip-plan';
        $trip_servicesAliasParam = ConstantHelper::TRIP_SERVICE_ALIAS;
        $tripbook = Helper::getBookSeriesNew($trip_servicesAliasParam, $tripParentUrl, true,true)->get();
        

        $status = ConstantHelper::STATUS;
        
        return view('erp-external.edit', compact('status','external','sobook','tripbook','dnbook','allOrganizations'));
    }

    public function update(ErpExternalIntegrationRequest $request, $id)
    {
        $user = Helper::getAuthenticatedUser();

        DB::beginTransaction();
        try {
            $external = ErpExternalIntegration::findOrFail($id);

            $exists = ErpExternalIntegration::where('organization_id', $request->organization_id)
                ->where('store_id', $request->store_id)
                ->where('id', '!=', $external->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'This store already exists for the selected organization.',
                ], 422);
            }

            $external->update([
                'trip_book_id'  => $request->trip_book_id,
                'so_book_id'    => $request->so_book_id,
                'dnote_book_id' => $request->dnote_book_id,
                'customer_id'   => $request->customer_id,
                'status'        => $request->status,
            ]);
            $keys = ['deletedItemIds'];
            $deletedData = collect($keys)->mapWithKeys(function ($key) use ($request) {
                return [$key => json_decode($request->input($key, '[]'), true)];
            })->toArray();

            if(isset($deletedData['deletedItemIds'])&&!empty($deletedData['deletedItemIds'])){
                ErpStockStoreMapping::whereIn('id',$deletedData['deletedItemIds'])->delete();

            }
            $existingSubStores = ErpStockStoreMapping::where('organization_id', $request->organization_id)
                ->where('store_id', $request->store_id)
                ->pluck('sub_store_id')
                ->toArray();
            if(isset($request->data)&&$request->data!=null){

                foreach ($request->data as $data) {
                    if(isset($data['stock_id'])&&$data['stock_id']){
                        ErpStockStoreMapping::where('id',$data['stock_id'])->update(['is_primary'=>isset($data['is_primary']) ?$data['is_primary']: 0]);
                        continue;
                    }
                    $subStoreId = $data['subLocation_id'];
                    if (in_array($subStoreId, $existingSubStores)) {
                        DB::rollBack();
                        $subStore = ErpSubStore::find($subStoreId);

                        return response()->json([
                            'status' => false,
                            'message' => ($subStore?->name ?? 'This') . " sub-store is duplicate for this store.",
                        ], 422);
                    }
                
                    ErpStockStoreMapping::create([
                        'stock_type'      => $data['stock_type'],
                        'group_id'        => $user->group_id,
                        'company_id'      => $user->company_id,
                        'organization_id' => $external->organization_id,
                        'store_id'        => $external->store_id,
                        'sub_store_id'    => $subStoreId,
                        'is_primary'      => isset($data['is_primary']) ?1: 0,
                    ]);

                    $existingSubStores[] = $subStoreId;
                    
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Record updated successfully',
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Error updating sub store: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $external = ErpExternalIntegration::findOrFail($id);

            ErpStockStoreMapping::where('organization_id', $external->organization_id)
                ->where('store_id', $external->store_id)
                ->delete();

            $external->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'External Integration data deleted successfully.',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the record: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getStore(Request $request){
        $get =ErpStore::where('organization_id',$request->org_id)->get();
        return $get;
    }  
    
    public function getSubStore(Request $request){
        $type = $request->type;
        $storeId = $request->store_id;

        $subStoreIds = ErpSubStoreParent::where('store_id', $storeId)
                ->get()->pluck('sub_store_id')
                ->toArray(); 

        $subStores = ErpSubStore::select('id', 'name', 'code', 'station_wise_consumption', 'is_warehouse_required')
                    ->whereIn('id', $subStoreIds)->where('type', 'like', "%{$type}%")
                    ->where('status', ConstantHelper::ACTIVE)
                    ->get();
                        
        return $subStores;
    }
}
