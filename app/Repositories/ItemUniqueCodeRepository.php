<?php

namespace App\Repositories;

use App\Helpers\CommonHelper;
use App\Helpers\TaxHelper;
use App\Models\StockLedger;
use App\Models\WHM\ErpItemUniqueCode;
use App\Models\WHM\ErpWhmJob;

class ItemUniqueCodeRepository
{
    public function getDetail($barcode, $tripNo)
    {
       $data = ErpItemUniqueCode::with([
            'subStore' => function($q){
                $q->select('id','name');
            },
            'store' => function($q){
                $q->select('id','store_name')
                ->with([
                    'address' => function($q) {
                        $q->select('id', 'addressable_id', 'addressable_type', 'state_id', 'country_id', 'address');
                    }
                ]);
            },
            'item' => function($q){
                $q->select('id', 'hsn_id', 'subcategory_id')
                ->with([
                    'hsn' => function($query){
                        $query->select('id','code');
                    },
                    'subCategory' => function($query){
                        $query->select('id','name');
                    }
                ]);
            }
        ])
        ->where('item_uid',$barcode)
        ->where('trip_no',$tripNo)
        ->where('job_type',CommonHelper::PICKING)
        ->whereNull('utilized_id')
        ->select('id','job_id','item_uid','store_id','sub_store_id','trip_id','trip_no','packet_no','item_id','item_name','item_code','morphable_id')
        ->first();

        if (!$data) {
            return [
                'message' => 'No matching item found.',
                'status' => false
            ];
        }

        $job = ErpWhmJob::find($data->job_id);
        $pricingRes = $this->getItemPricingDetail($job,$data);
        $data->pricingDetails = $pricingRes;
        
        return [
            'message' => 'Data found.',
            'status' => true,
            'data' => $data
        ];

    }

    public function getItemPricingDetail($job, $data)
    {
        // Get item cost
        $stockLedger = StockLedger::whereHas('reservations',function($q) use($data, $job){
                $q->where('issue_book_type', $job->trns_type)
                ->where('issue_header_id', $job->morphable_id)
                ->where('issue_detail_id', $data->morphable_id);
            })->first();


        // calculate price
        $price = 0; 
        if($stockLedger){
            $price = $stockLedger->cost_per_unit;
        }

        // calculate tax
        $hsnId = optional($data->item)->hsn_id;
        $countryId = optional($data->store->address)->country_id;
        $stateId = optional($data->store->address)->state_id;
        $taxRates = [];
        if($hsnId && $countryId && $stateId){
            $taxRates = TaxHelper::calculateTax($hsnId, $price, $countryId, $stateId, $countryId, $stateId, 'sale');
        }

        return [
            "item_cost" => $price, 
            "taxRates" => $taxRates
        ];
    }
}
