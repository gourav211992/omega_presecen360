<?php

namespace App\Services\Integration;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Helpers\ConstantHelper;
use App\Models\ErpPickupSchedule;
use App\Models\ErpTripPlanHeader;
use App\Models\EwayBillMaster;

class TransportService {

    /**
     * Main method to update transport detail
     */
    public function update($request, $user)
    {
        try {
            $erpTripPlan = ErpTripPlanHeader::where('organization_id', $request->organization_id)
                ->where('document_number', $request->trip_number)
                ->first();
            
            if (!$erpTripPlan) throw new Exception(ConstantHelper::TRIP_NUMBER_NOT_FOUND);

            $transportMode = EwayBillMaster::where('description',  $request->transport_mode)
                ->where('status', 'active')->first();
            if(!$transportMode){
                throw new Exception("invalid transport_mode.");
            }

            DB::beginTransaction();

            $erpTripPlan->update([
                'transporter_name'  => $request->transporter_name,
                'vehicle_number' => $request->vehicle_number,
                'transport_mode' => $request->transport_mode,
                'transport_mode_id' =>  $transportMode->id,
                'champ_name' =>  $request->champ_name,
                'driver_name' =>  $request->driver_name,
            ]);

            $erpPickupSchedule = ErpPickupSchedule::where('trip_id',$erpTripPlan->id)->first();
            if($erpPickupSchedule){
                $erpPickupSchedule->update([
                    'transporter_name'  => $request->transporter_name,
                    'vehicle_no' => $request->vehicle_number,
                    'transport_mode' => $request->transport_mode,
                    'transport_mode_id' =>  $transportMode->id,
                    'champ' =>  $request->champ_name,
                    'driver_name' =>  $request->driver_name,
                ]);
            }

            DB::commit();

            return ['status' => true, 'message' => 'Transport detail updated successfully', 'data' => $erpTripPlan];

        } catch (Exception $ex) {
            DB::rollBack();
            return ['status' => false, 'message' => $ex->getMessage()];
        }
    }
}