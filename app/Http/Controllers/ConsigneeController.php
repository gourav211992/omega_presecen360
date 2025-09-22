<?php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\DataTables;
use App\Models\ERP\ErpConsignee;
use App\Models\ErpAddress;
use Illuminate\Http\Request;
use App\Http\Requests\ConsigneeRequest;
use App\Helpers\ConstantHelper;
use App\Helpers\Helper;
use Illuminate\Support\Facades\DB;

class ConsigneeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = ErpConsignee::query()->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('consignee_type', function ($row) {
                    $types = [];
                    if ($row->is_vendor) {
                        $types[] = 'Vendor';
                    }
                    if ($row->is_customer) {
                        $types[] = 'Customer';
                    }
                    if (!empty($types)) {
                        $badgeClasses = [
                            'Vendor' => 'badge-light-primary',
                            'Customer' => 'badge-light-info'
                        ];
                        $badges = array_map(function($type) use ($badgeClasses) {
                            return '<span class="badge rounded-pill ' . $badgeClasses[$type] . ' badgeborder-radius">' . $type . '</span>';
                        }, $types);
                        return implode(' ', $badges);
                    }

                    return '<span class="badge rounded-pill badge-light-secondary badgeborder-radius">N/A</span>';
                })
                ->addColumn('status', function ($row) {
                    return '<span class="badge rounded-pill '
                        . ($row->status == 'active' ? 'badge-light-success' : 'badge-light-danger')
                        . ' badgeborder-radius">'
                        . ucfirst($row->status) . '</span>';
                })
                ->addColumn('action', function ($row) {
                    $editUrl = route('consignees.edit', $row->id);
                    return '<div class="dropdown">
                                <button type="button" class="btn btn-sm dropdown-toggle hide-arrow py-0" data-bs-toggle="dropdown">
                                    <i data-feather="more-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="' . $editUrl . '">
                                        <i data-feather="edit-3" class="me-50"></i>
                                        <span>Edit</span>
                                    </a>
                                </div>
                            </div>';
                })
                ->rawColumns(['consignee_type', 'status', 'action'])
                ->make(true);
        }

        return view('consignees.index');
    }

    public function create()
    {
        $status = ConstantHelper::STATUS;
        return view('consignees.create', compact('status'));
    }

    public function store(ConsigneeRequest $request)
    {
        $validated = $request->validated();
        $user = Helper::getAuthenticatedUser();

        try {
            DB::beginTransaction();

            $parentUrl = ConstantHelper::CONSIGNEE_SERVICE_ALIAS;
            $services = Helper::getAccessibleServicesFromMenuAlias($parentUrl, '', $user);
            if ($services && $services['services'] && $services['services']->isNotEmpty()) {
                $firstService = $services['services']->first();
                $serviceId = $firstService->service_id;
                $policyData = Helper::getPolicyByServiceId($serviceId);
                if ($policyData && isset($policyData['policyLevelData'])) {
                    $policyLevelData = $policyData['policyLevelData'];
                    $validated['group_id'] = $policyLevelData['group_id'];
                    $validated['company_id'] = $policyLevelData['company_id'];
                    $validated['organization_id'] = $policyLevelData['organization_id'];
                }
            } else {
                $validated['group_id'] = $user->organization->group_id ?? null;
                $validated['company_id'] = $user->organization->company_id ?? null;
                $validated['organization_id'] = $user->organization_id ?? null;
            }

            $validated['created_by'] = $user->auth_user_id;

            $consignee = ErpConsignee::create($validated);

            // --- Save addresses ---
            if ($request->has('addresses')) {
                foreach ($request->input('addresses') as $addr) {
                    if (!empty($addr['country_id']) && !empty($addr['state_id']) && !empty($addr['city_id'])) {
                        $consignee->addresses()->create([
                            'country_id' => $addr['country_id'] ?? null,
                            'state_id' => $addr['state_id'] ?? null,
                            'city_id' => $addr['city_id'] ?? null,
                            'pincode' => $addr['pincode'] ?? null,
                            'pincode_master_id' => $addr['pincode_master_id'] ?? null,
                            'address' => $addr['address'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Record created successfully',
                'data' => $consignee,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while creating the consignee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $consignee = ErpConsignee::findOrFail($id);
        $status = ConstantHelper::STATUS;
        return view('consignees.edit', compact('consignee', 'status'));
    }

    public function update(ConsigneeRequest $request, $id)
    {
        $validated = $request->validated();
        $user = Helper::getAuthenticatedUser();

        try {
            DB::beginTransaction();

            $parentUrl = ConstantHelper::CONSIGNEE_SERVICE_ALIAS;
            $services = Helper::getAccessibleServicesFromMenuAlias($parentUrl, '', $user);
            if ($services && $services['services'] && $services['services']->isNotEmpty()) {
                $firstService = $services['services']->first();
                $serviceId = $firstService->service_id;
                $policyData = Helper::getPolicyByServiceId($serviceId);
                if ($policyData && isset($policyData['policyLevelData'])) {
                    $policyLevelData = $policyData['policyLevelData'];
                    $validated['group_id'] = $policyLevelData['group_id'];
                    $validated['company_id'] = $policyLevelData['company_id'];
                    $validated['organization_id'] = $policyLevelData['organization_id'];
                }
            } else {
                $validated['group_id'] = $user->organization->group_id ?? null;
                $validated['company_id'] = $user->organization->company_id ?? null;
                $validated['organization_id'] = $user->organization_id ?? null;
            }

            $consignee = ErpConsignee::findOrFail($id);
            $validated['updated_by'] = $user->auth_user_id;
            $consignee->update($validated);

            // Handle addresses
            $addresses = $request->input('addresses', []);
            $existingAddressIds = $consignee->addresses()->pluck('id')->toArray();
            $submittedAddressIds = [];

            foreach ($addresses as $addressData) {
                if (isset($addressData['id']) && in_array($addressData['id'], $existingAddressIds)) {
                    $address = $consignee->addresses()->find($addressData['id']);
                    $address->update([
                        'country_id' => $addressData['country_id'] ?? null,
                        'state_id' => $addressData['state_id'] ?? null,
                        'city_id' => $addressData['city_id'] ?? null,
                        'pincode' => $addressData['pincode'] ?? null,
                        'pincode_master_id' => $addressData['pincode_master_id'] ?? null,
                        'address' => $addressData['address'] ?? null,
                        'type' => $addressData['type'] ?? null,
                    ]);
                    $submittedAddressIds[] = $address->id;
                } else {
                    $newAddress = $consignee->addresses()->create([
                        'country_id' => $addressData['country_id'] ?? null,
                        'state_id' => $addressData['state_id'] ?? null,
                        'city_id' => $addressData['city_id'] ?? null,
                        'pincode' => $addressData['pincode'] ?? null,
                        'pincode_master_id' => $addressData['pincode_master_id'] ?? null,
                        'address' => $addressData['address'] ?? null,
                        'type' => $addressData['type'] ?? null,
                    ]);
                    $submittedAddressIds[] = $newAddress->id;
                }
            }

            $addressesToDelete = array_diff($existingAddressIds, $submittedAddressIds);
            if (!empty($addressesToDelete)) {
                $consignee->addresses()->whereIn('id', $addressesToDelete)->delete();
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Record updated successfully',
                'data' => $consignee,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating the consignee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

      public function deleteAddress($id)
        {
            DB::beginTransaction();
            try {
                $address = ErpAddress::find($id);

                if ($address) {
                    $result = $address->deleteWithReferences();
                    if (!$result['status']) {
                        DB::rollBack();
                        return response()->json(['status' => false, 'message' => $result['message']], 400);
                    }
                    DB::commit();
                    return response()->json(['status' => true, 'message' => 'Record deleted successfully']);
                }
                return response()->json(['status' => false, 'message' => 'Record not found'], 404);
            } catch (Exception $e) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
            }
        }

    public function destroy($id)
    {
        $user = Helper::getAuthenticatedUser();

        try {
            $consignee = ErpConsignee::findOrFail($id);
            $consignee->deleted_by = $user->auth_user_id;
            $consignee->save();

            $result = $consignee->deleteWithReferences();

            if (!$result['status']) {
                return response()->json([
                    'status' => false,
                    'message' => $result['message'],
                    'referenced_tables' => $result['referenced_tables'] ?? []
                ], 400);
            }

            return response()->json([
                'status' => true,
                'message' => 'Record deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the consignee: ' . $e->getMessage()
            ], 500);
        }
    }
}
