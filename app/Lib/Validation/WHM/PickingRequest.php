<?php

namespace App\Lib\Validation\WHM;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class PickingRequest
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getItems() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(), [
            'job_id' => ['required'],
            'store_id' => ['required']
        ], [
            'job_id.required' => 'Job id is required',
            'store_id.required' => 'Store id is required',
        ]);

		return $validator;
	}

    public function getItemDetail() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(), [
            'store_id' => ['required'],
            'pl_item_id' => ['required'],
            'job_id' => ['required'],
        ], [
            'store_id.required' => 'Store id is required',
            'pl_item_id.required' => 'Pl item id is required',
            'job_id.required' => 'Job id is required',
        ]);

		return $validator;
	}


    public function scanQr() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'job_id' => ['required'],
            'pl_item_id' => ['required'],
            'packet_ids' => ['required', 'array', 'max:50'],
            'storage_point_id' => ['nullable']
        ],[
            'job_id.required' => 'Job id is required',
            'pl_item_id.required' => 'Picklist item id is required',
            'packet_ids.required' => 'Scan a packet to draft the form',
            'storage_point_id.required' => 'Storage point id is required',
        ]);

		return $validator;
	}

    public function updateStatus() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'packet_id' => ['required'],
            'job_id' => ['required'],
            'storage_point_id' => ['nullable']
        ],[
            'packet_id.required' => 'Packet id is required',
            'job_id.required' => 'Job id is required',
            'storage_point_id.required' => 'Storage point id is required',
        ]);

		return $validator;
	}

    public function closeJob() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'job_id' => ['required'],
            'deviation' => ['required'],
        ],[
            'job_id.required' => 'Job id is required'
        ]);

		return $validator;
	}

    public function pendingJob() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'job_id' => ['required'],
            'pl_item_id' => ['required'],
            'status' => ['nullable'],
        ],[
            'job_id.required' => 'Job id is required',
            'pl_item_id.required' => 'Picklist item id is required',
        ]);

		return $validator;
	}

    public function scannedItemQrs() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'job_id' => ['required'],
            'pl_item_id' => ['required'],
        ],[
            'job_id.required' => 'Job id is required',
            'pl_item_id.required' => 'Pl item id is required',
        ]);

		return $validator;
	}

    public function scanStorage() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'storage_number' => ['required'],
            'job_id' => ['required'],
            'pl_item_id' => ['required'],
        ],[
            'storage_number.required' => 'Storage number is required',
            'job_id.required' => 'Job id is required',
            'pl_item_id.required' => 'Picklist item id is required',
        ]);

		return $validator;
	}

    public function pickedPackets() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'storage_point_id' => ['required'],
            'pl_item_id' => ['required'],
            'job_id' => ['required'],
        ],[
            'job_id.required' => 'Job id is required',
            'putaway_item_id.required' => 'Putaway item id is required',
            'storage_point_id.required' => 'Storage point id is required',
        ]);

		return $validator;
	}

    public function validateQr() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
            'packet_id' => ['required'],
            'job_id' => ['required'],
            'pl_item_id' => ['required'],
            'storage_point_id' => ['nullable'],
        ],[
            'job_id.required' => 'Job id is required',
            'packet_id.required' => 'Packet id is required',
            'pl_item_id.required' => 'Picking item id is required',
        ]);

		return $validator;
	}
}
