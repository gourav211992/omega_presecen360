<?php

namespace App\Http\Requests\Integration;

use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class WarehouseRequest
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function stockLedger() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
			'store_id' => [
				'required','integer'
            ],
            'organization_id' => [
				'required','integer'
			],
            'sub_store_ids' => [
				'required', 'array'
			],
			'sub_store_ids.*' => [
				'integer'
			],
            'item_id' => [
				'nullable','integer'
			]

		],[
			'store_id.required' => 'Store id is required!',
			'sub_store_ids.required' => 'Sub store id is required!',
			'organization_id.required' => 'Organization id is required!',
		]);

		return $validator;
	}

    public function barcodeDetail() : ValidationValidator
	{
		$validator = Validator::make($this->request->all(),[
			'barcode' => [
				'required'
            ],
            'trip_id' => [
				'required','integer'
			]

		],[
			'barcode.required' => 'Barcode is required!',
			'trip_id.required' => 'Trip id is required!',
		]);

		return $validator;
	}

}
