<?php

namespace App\Services;

use App\Helpers\ConstantHelper;
use App\Models\Finance\GstrCompiledData;
use App\Models\ErpGstInvoiceType;
use App\Models\Organization;
use App\Helpers\Common\OrganizationHelper;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Gstr3bService
{
    public function getSection3_1Data($gstin, $month)
    {
        $baseQuery = GstrCompiledData::query()
            ->where('erp_gstr_compiled_data.supplier_gstin', $gstin)
            ->whereNotNull('invoice_id');

        $b2bData = (clone $baseQuery)
            ->whereIn('doc_type', ['b2b', 'b2ba'])
            ->selectRaw('
                SUM(taxable_amt) as taxable_amt,
                SUM(igst) as igst,
                SUM(cgst) as cgst,
                SUM(sgst) as sgst,
                SUM(cess) as cess
            ')
            ->first();

        $zeroRatedData = (clone $baseQuery)
            ->where('rate', 0)
            ->selectRaw('SUM(taxable_amt) as taxable_amt')
            ->first();

        $nilExemptedData = (clone $baseQuery)
            ->selectRaw('SUM(expt_amt + nil_amt) as taxable_amt')
            ->first();

        $reverseChargeData = (clone $baseQuery)
            ->where('reverse_charge', 'Y')
            ->selectRaw('
                SUM(taxable_amt) as taxable_amt,
                SUM(igst) as igst,
                SUM(cgst) as cgst,
                SUM(sgst) as sgst,
                SUM(cess) as cess
            ')
            ->first();

        $nonGstData = (clone $baseQuery)
            ->where('non_gst_amt', '>', 0)
            ->selectRaw('SUM(non_gst_amt) as non_gst_amt')
            ->first();

        return [
            'b2b' => [
                'taxable_amt' => $b2bData->taxable_amt ?? 0,
                'igst' => $b2bData->igst ?? 0,
                'cgst' => $b2bData->cgst ?? 0,
                'sgst' => $b2bData->sgst ?? 0,
                'cess' => $b2bData->cess ?? 0,
            ],
            'zero_rated' => [
                'taxable_amt' => $zeroRatedData->taxable_amt ?? 0,
                'igst' => 0, 'cgst' => 0, 'sgst' => 0, 'cess' => 0,
            ],
            'nil_exempted' => [
                'taxable_amt' => $nilExemptedData->taxable_amt ?? 0,
                'igst' => 0, 'cgst' => 0, 'sgst' => 0, 'cess' => 0,
            ],
            'reverse_charge' => [
                'taxable_amt' => $reverseChargeData->taxable_amt ?? 0,
                'igst' => $reverseChargeData->igst ?? 0,
                'cgst' => $reverseChargeData->cgst ?? 0,
                'sgst' => $reverseChargeData->sgst ?? 0,
                'cess' => $reverseChargeData->cess ?? 0,
            ],
            'non_gst' => [
                'taxable_amt' => $nonGstData->non_gst_amt ?? 0,
                'igst' => 0, 'cgst' => 0, 'sgst' => 0, 'cess' => 0,
            ],
        ];
    }

    public function getSection3_2Data($gstin, $month)
    {
        $results = [
            'unregistered' => ['taxable_value' => 0, 'igst' => 0, 'details' => []],
            'composition'  => ['taxable_value' => 0, 'igst' => 0, 'details' => []],
            'uin'          => ['taxable_value' => 0, 'igst' => 0, 'details' => []],
        ];

        $unregistered = GstrCompiledData::where('erp_gstr_compiled_data.igst', '>', 0)
            ->where(function ($q) {
                $q->whereNull('erp_gstr_compiled_data.party_gstin')
                  ->orWhere('erp_gstr_compiled_data.party_gstin', '');
            })
            ->whereNotNull('erp_gstr_compiled_data.place_of_supply')
            ->where('doc_type', 'b2b')
            ->select(
                DB::raw("'unregistered' as category"),
                'erp_gstr_compiled_data.place_of_supply',
                DB::raw('SUM(taxable_amt) as taxable_value'),
                DB::raw('SUM(igst) as igst')
            )
            ->groupBy('erp_gstr_compiled_data.place_of_supply')
            ->get();

        $results['unregistered']['taxable_value'] = $unregistered->sum('taxable_value');
        $results['unregistered']['igst'] = $unregistered->sum('igst');
        $results['unregistered']['details'] = $unregistered->toArray();

        // ... same logic for composition and UIN as your current code
        // (trimmed for brevity)
        return $results;
    }

    //Section 4 
    public function getGstr4Section($month, $gstin)
    {
        // Get organization's country ID from addresses for comparison
        $organization = OrganizationHelper::getAuthenticatedOrganization();
        $orgCountryId = $this->getOrganizationCountryId($organization);

        $getTaxDataWithCountryFilter = function ($headerTable, $detailTable, $tedTable, $headerKey, $filters = []) use ($orgCountryId) {
            $query = DB::table("$headerTable as mh")
                ->join("$detailTable as md", "mh.id", '=', "md.$headerKey")
                ->join("$tedTable as mea", "mh.id", '=', "mea.$headerKey")
                ->join('erp_vendors as v', 'mh.vendor_id', '=', 'v.id')
                ->join('erp_addresses as va', function($join) {
                    $join->on('v.id', '=', 'va.addressable_id')
                         ->where('va.addressable_type', '=', 'App\\Models\\Vendor');
                })
                ->where('mh.document_status', '!=', 'draft')
                ->whereNull('mh.deleted_at')
                ->whereNull('md.deleted_at')
                ->whereNull('mea.deleted_at')
                ->whereNull('v.deleted_at');

            // Country validation: Only include if vendor country != organization country
            if ($orgCountryId) {
                $query->where('va.country_id', '!=', $orgCountryId);
            }

            foreach ($filters as $key => $value) {
                $query->where($key, $value);
            }

            // Group and sum by TED code type (IGST, CGST, SGST, CESS)
            $result = $query
                ->selectRaw("
                    SUM(CASE WHEN mea.ted_code LIKE '%IGST%' THEN mea.ted_amount ELSE 0 END) as igst,
                    SUM(CASE WHEN mea.ted_code LIKE '%CGST%' THEN mea.ted_amount ELSE 0 END) as cgst,
                    SUM(CASE WHEN mea.ted_code LIKE '%SGST%' THEN mea.ted_amount ELSE 0 END) as sgst,
                    SUM(CASE WHEN mea.ted_code LIKE '%CESS%' THEN mea.ted_amount ELSE 0 END) as cess
                ")
                ->first();

            return [
                'igst' => $result->igst ?? 0,
                'cgst' => $result->cgst ?? 0,
                'sgst' => $result->sgst ?? 0,
                'cess' => $result->cess ?? 0,
            ];
        };

        // Import of Goods (with country validation)
        $importTotals = $getTaxDataWithCountryFilter(
            'erp_mrn_headers',
            'erp_mrn_details',
            'erp_mrn_extra_amounts',
            'mrn_header_id'
        );

        // Total taxable amount for import goods (with country validation)
        $taxableAmount = DB::table('erp_mrn_headers as mh')
            ->join('erp_mrn_details as md', 'mh.id', '=', 'md.mrn_header_id')
            ->join('erp_vendors as v', 'mh.vendor_id', '=', 'v.id')
            ->join('erp_addresses as va', function($join) {
                $join->on('v.id', '=', 'va.addressable_id')
                     ->where('va.addressable_type', '=', 'App\\Models\\Vendor');
            })
            ->whereMonth('mh.document_date', $month)
            ->whereYear('mh.document_date', date('Y'))
            ->where('mh.document_status', '!=', 'draft')
            ->whereNull('mh.deleted_at')
            ->whereNull('md.deleted_at')
            ->whereNull('v.deleted_at');

        // Country validation for taxable amount
        if ($orgCountryId) {
            $taxableAmount->where('va.country_id', '!=', $orgCountryId);
        }

        $taxableAmount = $taxableAmount->sum('md.taxable_amount');

        // Import of Services (with country validation)
        $importSerTotals = $getTaxDataWithCountryFilter(
            'erp_expense_headers',
            'erp_expense_details',
            'erp_expense_ted',
            'expense_header_id'
        );

        return [
            'import_goods' => $importTotals,
            'import_services' => $importSerTotals,
            'taxable_amount_goods' => $taxableAmount,
        ];
    }

    /**
     * Get organization's country ID from its addresses
     */
    private function getOrganizationCountryId($organization)
    {
        if (!$organization) {
            return null;
        }

        // Get the first address of the organization to determine country
        $address = $organization->addresses()->first();
        
        return $address ? $address->country_id : null;
    }

    //Section 5 - Values of exempt, nil-rated and non-GST inward supplies
    public function getGstr3bSection5Data($gstin, $month)
    {
        // Step 1: Get organization's state_id from address table (based on GSTIN)
        $companyStateId = DB::table('erp_addresses as a')
            ->join('organizations as o', function ($join) {
                $join->on('a.addressable_id', '=', 'o.id')
                    ->where('a.addressable_type', '=', 'App\\Models\\Organization');
            })
            ->where('o.gst_number', $gstin)
            ->value('a.state_id');
    

        if (!$companyStateId) {
            return [
                'error' => 'Organization address or GSTIN not found.'
            ];
        }

        // Step 2: Function to fetch exempt & non-GST data from any header/detail set
        $getTaxExemptData = function ($headerTable, $detailTable, $tedTable, $headerKey, $addressableType) use ($month, $companyStateId) {
            $query = DB::table("$headerTable as h")
                ->join("$detailTable as d", "h.id", '=', "d.$headerKey")
                ->leftJoin("$tedTable as t", "h.id", '=', "t.$headerKey")
                ->leftJoin('erp_addresses as a', function ($join) use ($addressableType) {
                    $join->on('h.vendor_id', '=', 'a.addressable_id')
                        ->where('a.addressable_type', '=', $addressableType);
                })
                ->whereMonth('h.document_date', $month)
                ->whereYear('h.document_date', date('Y'))
                ->where('h.document_status', '!=', 'draft')
                ->whereNull('h.deleted_at')
                ->whereNull('d.deleted_at');

            $result = $query->selectRaw("
                    SUM(CASE 
                        WHEN t.ted_code IS NULL OR t.ted_amount = 0 OR t.ted_type != 'Tax' 
                        THEN d.taxable_amount 
                        ELSE 0 
                    END) as exempt_nil_amount,
                    SUM(CASE 
                        WHEN d.taxable_amount > 0 AND (t.ted_code IS NULL OR t.ted_amount = 0)
                        THEN d.taxable_amount 
                        ELSE 0 
                    END) as non_gst_amount,
                    SUM(
                        CASE 
                            WHEN a.state_id = $companyStateId THEN d.taxable_amount 
                            ELSE 0 
                        END
                    ) as intrastate_amount,
                    SUM(
                        CASE 
                            WHEN a.state_id != $companyStateId THEN d.taxable_amount 
                            ELSE 0 
                        END
                    ) as interstate_amount
                ")->first();

            return [
                'exempt_nil_amount' => $result->exempt_nil_amount ?? 0,
                'non_gst_amount' => $result->non_gst_amount ?? 0,
                'intrastate_amount' => $result->intrastate_amount ?? 0,
                'interstate_amount' => $result->interstate_amount ?? 0,
            ];
        };

        // Step 3: MRN data (Material Receipt Note)
        $mrnData = $getTaxExemptData(
            'erp_mrn_headers',
            'erp_mrn_details',
            'erp_mrn_extra_amounts',
            'mrn_header_id',
            'App\\Models\\Vendor'
        );

        // Step 4: PB data (Purchase Bill)
        $pbData = $getTaxExemptData(
            'erp_pb_headers',
            'erp_pb_details',
            'erp_pb_ted',
            'header_id',
            'App\\Models\\Vendor'
        );

        // Step 5: Combine results
        $combinedData = [
            'composition_exempt_nil' => [
                'total_amount' => ($mrnData['exempt_nil_amount'] ?? 0) + ($pbData['exempt_nil_amount'] ?? 0),
                'intrastate'   => ($mrnData['intrastate_amount'] ?? 0) + ($pbData['intrastate_amount'] ?? 0),
                'interstate'   => ($mrnData['interstate_amount'] ?? 0) + ($pbData['interstate_amount'] ?? 0),
            ],
            'non_gst' => [
                'total_amount' => ($mrnData['non_gst_amount'] ?? 0) + ($pbData['non_gst_amount'] ?? 0),
                'intrastate'   => ($mrnData['intrastate_amount'] ?? 0) + ($pbData['intrastate_amount'] ?? 0),
                'interstate'   => ($mrnData['interstate_amount'] ?? 0) + ($pbData['interstate_amount'] ?? 0),
            ],
        ];

        // Step 6: Return final formatted array for GSTR-3B Section 5 table
        return [
            [
                'nature_of_supplies' => 'From a supplier under composition scheme, Exempt and Nil rated supply',
                'inter_state' => number_format($combinedData['composition_exempt_nil']['interstate'] ?? 0, 2, '.', ''),
                'intra_state' => number_format($combinedData['composition_exempt_nil']['intrastate'] ?? 0, 2, '.', ''),
            ],
            [
                'nature_of_supplies' => 'Non GST supply',
                'inter_state' => number_format($combinedData['non_gst']['interstate'] ?? 0, 2, '.', ''),
                'intra_state' => number_format($combinedData['non_gst']['intrastate'] ?? 0, 2, '.', ''),
            ],
        ];
    }

    public function getGstr3bSection4PartC($gstin, $month)
    {
        // Get Section 3.1 data (nil exempted supplies)
        $section3_1Data = $this->getSection3_1Data($gstin, $month);
        
        // Get Section 4 data (imports)
        $section4Data = $this->getGstr4Section($month, $gstin);
        
        // Extract nil exempted data from Section 3.1
        $nilExemptedData = $section3_1Data['reverse_charge'] ?? [
            'taxable_amt' => 0,
            'igst' => 0, 'cgst' => 0, 'sgst' => 0, 'cess' => 0,
        ];

        // Get import totals from Section 4
        $importTotals = [
            'igst' => ($section4Data['import_goods']['igst'] ?? 0) + ($section4Data['import_services']['igst'] ?? 0),
            'cgst' => ($section4Data['import_goods']['cgst'] ?? 0) + ($section4Data['import_services']['cgst'] ?? 0),
            'sgst' => ($section4Data['import_goods']['sgst'] ?? 0) + ($section4Data['import_services']['sgst'] ?? 0),
            'cess' => ($section4Data['import_goods']['cess'] ?? 0) + ($section4Data['import_services']['cess'] ?? 0),
        ];

        // Calculate final totals (Section 3.1 nil exempted + imports - 0)
        $finalTotals = [
            'igst' => $nilExemptedData['igst'] + $importTotals['igst'],
            'cgst' => $nilExemptedData['cgst'] + $importTotals['cgst'],
            'sgst' => $nilExemptedData['sgst'] + $importTotals['sgst'],
            'cess' => $nilExemptedData['cess'] + $importTotals['cess'],
        ];

        // dd($finalTotals);

        return [
            'nil_exempted_data' => $nilExemptedData,
            'import_totals' => $importTotals,
            'final_totals' => $finalTotals,
            'net_itc_available' => $finalTotals,
        ];
    }

}
