<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Exception;
use App\Helpers\Helper;
use App\Helpers\ConstantHelper;
use App\Models\Shift;
use Illuminate\Support\Facades\DB; 
use Yajra\DataTables\Facades\DataTables;

class ShiftController extends Controller
{
    public function index(Request $request)
    {    
        $user = Helper::getAuthenticatedUser();
        $shift = Shift::select('*')
            ->whereOrganizationId($user->organization_id);
        if ($request->ajax()) {
        return DataTables::of($shift)
            ->addIndexColumn()
            ->addColumn('name', fn($row) => $row->name ?? 'AS')
            ->editColumn('start_time', fn($row) => $row->start_time?? 'N/A')
            ->editColumn('end_time', fn($row) => $row->end_time?? 'N/A')
            ->addColumn('status', fn($row) => '<span class="badge rounded-pill badge-light-' . ($row->status === 'active' ? 'success' : 'danger') . '">'
                . ucfirst($row->status) . '</span>')
            ->addColumn('action', function ($row) {
                $editUrl = route('shift.edit', $row->id);
                $deleteUrl = route('shift.destroy', $row->id);
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
            ->rawColumns(['status', 'action'])
            ->make(true);
        };
        return view('shift.index');
    }

    public function create()
    {
        $user = Helper::getAuthenticatedUser();
 
        $status = ConstantHelper::STATUS;
        return view('shift.shift', compact('status'));
    }

      public function store(Request $request)
    {
        $user = Helper::getAuthenticatedUser();

        DB::beginTransaction();
        try {

            // Save main external integration
            $staff = Shift::find($request->staff_id);

            if ($staff) {
                // Update existing
                $staff->update([
                    'organization_id' => $user->organization_id,
                    'name'            => $request->staff_name,
                    'start_time'      => $request->start_time,
                    'end_time'        => $request->end_time,
                    'status'          => $request->status,
                    'created_by'      => $user->auth_user_id,
                ]);
            } else {
                // Create new
                Shift::create([
                    'organization_id' => $user->organization_id,
                    'name'            => $request->staff_name,
                    'start_time'      => $request->start_time,
                    'end_time'        => $request->end_time,
                    'status'          => $request->status,
                    'created_by'      => $user->auth_user_id,
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
        $shift = Shift::select('*')
                ->where('id', $id)
                ->first();
        $status = ConstantHelper::STATUS;
        
        return view('shift.shift', compact('status','shift'));
    }
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $shift=Shift::findOrFail($id);
            $shift->delete();
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Record deleted successfully.',
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while deleting the record: ' . $e->getMessage()
            ], 500);
        }
    }


}
