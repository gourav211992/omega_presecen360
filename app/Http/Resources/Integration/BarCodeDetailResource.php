<?php

namespace App\Http\Resources\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarCodeDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'barcode'      => $this->item_uid,
            'sub_store_id'  => $this->sub_store_id,
            'sub_store'  => optional($this->subStore)->name,
            'trip_id'       => optional($this->trip)->id,
            'trip_doc_no'       => optional($this->trip)->document_number,
            'item_id'      => $this->item_id,
            'item_name'    => $this->item_name,
            'item_code'    => $this->item_code,
            'hsnCode'      => optional($this->item->hsn)->code,
            'category'     => optional($this->item->subCategory)->name,
            'item_cost'    => $this->pricingDetails['item_cost'] ?? 0,
            'taxRates'     => $this->getTaxRates($this->pricingDetails['taxRates'] ?? []),
        ];
    }

    private function getTaxRates($taxRates): array{
        if (empty($taxRates)) {
            return [
                "type" => null,
                "rate" => 0
            ];
        }

        return collect($taxRates)->map(function ($rate) {
            return [
                'type' => $rate['tax_type'] ?? null,
                'rate' => $rate['tax_percentage'] ?? 0
            ];
        })->values()->all();

    }
}
