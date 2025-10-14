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
            'trip_id'       => $this->trip_id,
            'trip_doc_no'       => $this->trip_no,
            'item_id'      => $this->item_id,
            'item_name'    => $this->item_name,
            'item_code'    => $this->item_code,
            'packet_no'    => $this->packet_no,
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
