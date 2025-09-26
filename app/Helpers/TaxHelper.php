<?php

namespace App\Helpers;

use App\Models\Customer;
use App\Models\Hsn;
use App\Models\Tax;
use App\Helpers\Configuration\Helper as ConfigHelper;
use App\Helpers\Configuration\Constants as ConfigConstant;
use App\Models\Country;
use App\Models\ErpTaxThresholdUtilization;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Model;

class TaxHelper
{
    const ADDRESS_TYPES = [self::ADDRESS_TYPE_STORE, self::ADDRESS_TYPE_ORGANIZATION];
    const ADDRESS_TYPE_STORE = "Store";
    const ADDRESS_TYPE_ORGANIZATION = "Organization";
    const ADDRESS_TYPE_DOCUMENT = "Document";

    public static function calculateTax(int $hsnId, float $price, int $fromCountry, int $fromState, int $upToCountry, int $upToState, string $transactionType, string $date = null): array
    {
        $taxDetails = [];
        if (empty($date)) {
            $date = now()->toDateString();
        }
        $hsn = Hsn::withDefaultGroupCompanyOrg()
            ->where('id', $hsnId)
            ->where('status', 'active')
            ->first();
        if (!$hsn) {
            throw new \Exception('Active HSN code not found.');
        }

        $placeOfSupply = self::determinePlaceOfSupply($fromCountry, $fromState, $upToCountry, $upToState);
        $taxPatterns = $hsn->taxPatterns()
            ->where('from_price', '<=', $price)
            ->where('upto_price', '>=', $price)
            ->where('from_date', '<=', $date)
            ->orderBy('from_date', 'desc')
            ->get();

        if ($taxPatterns->isEmpty()) {
            return $taxDetails;
        }

        foreach ($taxPatterns as $taxPattern) {
            $taxGroup = $taxPattern->taxGroup;

            if (!$taxGroup) {
               continue;
            }

            $taxCategory = $taxGroup->tax_category;
            $taxes = $taxGroup->taxDetails()
                ->where('status', 'active')
                ->get();

            foreach ($taxes as $taxDetail) {
                if ($taxCategory === 'GST') {
                    if ($taxDetail->place_of_supply && $taxDetail->place_of_supply === $placeOfSupply) {
                        $matches = ($transactionType === 'purchase')
                            ? $taxDetail->is_purchase
                            : $taxDetail->is_sale;

                        if ($matches) {
                            $taxDetails[] = [
                                'id' => $taxDetail->id,
                                'applicability_type' => $taxDetail->applicability_type,
                                'tax_group' => $taxGroup->tax_group,
                                'tax_percentage' => $taxDetail->tax_percentage,
                                'tax_type' => $taxDetail->tax_type,
                                'tax_id' => $taxDetail->tax_id,
                                'tax_code' => $taxDetail->tax_type,
                            ];
                        }
                    }
                } else {
                    $matches = ($transactionType === 'purchase')
                        ? $taxDetail->is_purchase
                        : $taxDetail->is_sale;

                    if ($matches) {
                        $taxDetails[] = [
                            'id' => $taxDetail->id,
                            'applicability_type' => $taxDetail->applicability_type,
                            'tax_group' => $taxGroup->tax_group,
                            'tax_percentage' => $taxDetail->tax_percentage,
                            'tax_type' => $taxDetail->tax_type,
                            'tax_id' => $taxDetail->tax_id,
                            'tax_code' => $taxDetail->tax_type,
                        ];
                    }
                }
            }
        }

        return $taxDetails;
    }

    /**
     * Calculate tax groups based on HSN, price, and location details.
     *
     * @param int $hsnId
     * @param float $price
     * @param int $fromCountry
     * @param int $fromState
     * @param int $upToCountry
     * @param int $upToState
     * @param string $transactionType
     * @param string $date
     * @return array
     */
    public static function calculateTaxGroups(int $hsnId, float $price, int $fromCountry, int $fromState, int $upToCountry, int $upToState, string $transactionType, string $date = ''): array
    {
        $groupedTaxes    = [];
        $totalTaxAmount  = 0.0;

        $taxDetails = self::calculateTax($hsnId, $price, $fromCountry, $fromState, $upToCountry, $upToState, $transactionType, $date);

        if (empty($taxDetails)) {
            return [
                'price'                  => $price,
                'total_tax'              => 0.0,
                'total_amount_after_tax' => $price,
                'group_taxes'            => [],
            ];
        }

        foreach ($taxDetails as $taxDetail) {
            $taxAmount       = round(($price * $taxDetail['tax_percentage']) / 100, 2);
            $totalTaxAmount += $taxAmount;

            $groupName  = $taxDetail['tax_group'];
            $groupId    = $taxDetail['id'];

            if (!isset($groupedTaxes[$groupName])) {
                $groupedTaxes[$groupName] = [
                    'tax_group_id' => $groupId,
                    'group_name'   => $groupName,
                    'taxes'        => [],
                ];
            }

            $groupedTaxes[$groupName]['taxes'][] = [
                'tax_id'            => $taxDetail['tax_id'],
                'tax_percent'       => $taxDetail['tax_percentage'],
                'tax_amount'        => $taxAmount,
                'tax_type'          => $taxDetail['tax_type'],
                'tax_code'          => $taxDetail['tax_code'],
                'applicability_type' => $taxDetail['applicability_type'],
            ];
        }

        return [
            'price'                  => $price,
            'total_tax'              => $totalTaxAmount,
            'total_amount_after_tax' => round($price + $totalTaxAmount, 2),
            'group_taxes'            => array_values($groupedTaxes),
        ];
    }

    private static function determinePlaceOfSupply(int $fromCountry, int $fromState, int $upToCountry, int $upToState): string
    {
        if ($fromCountry === $upToCountry) {
            return ($fromState === $upToState) ? 'Intrastate' : 'Interstate';
        }

        return 'Overseas';
    }
    /* For TDS and TCS taxes at header level only */
    public static function calculateHeaderTax(Model $party, int $fromCountryId, int $upToCountryId, $authUser, string $transactionType, string $taxCategory, string $taxType, string $documentDate, $oldNonTaxAssessableAmt = 0, $newTaxableAmount = 0)
    {
        $taxLabel = $taxCategory . ' - ' . $taxType;
        $country = Country::find($fromCountryId);
        //Country Not found
        if (!$country) {
            return [
                'status' => 'error',
                'message' => 'Country not found',
                'data' => null
            ];
        }
        //If country is not India - then skip
        if ($country -> code !== "IN") {
            return [
                'status' => 'success',
                'message' => 'Not Required',
                'data' => null
            ];
        }
        //Only applicable for India
        if ($fromCountryId !== $upToCountryId) {
            return [
                'status' => 'success',
                'message' => 'Not Required',
                'data' => null
            ];
        }
        //Check Customer TCS and Company TDS
        $customerTcs = false;
        if ($transactionType === 'sale' && $taxCategory === ConstantHelper::TCS && $taxType === ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) {
            $customerTcs = $party -> compliances -> tds_applicable;
        }
        //Retrieve party GST and party PAN
        $partyPan = $party -> pan_number;
        $partyGst = $party -> compliances -> gstin_no;

        //Get the company parameter for TDS
        $groupId = $authUser -> group_id;
        $companyId = $authUser -> company_id;
        $companyTds = ConfigHelper::getConfigurationValueOfCompany(ConfigConstant::COMP_TDS_APPLICABLE, $companyId);
        if ($transactionType === 'sale' && $taxCategory === ConstantHelper::TCS && $taxType === ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) {
            if (!($customerTcs && $companyTds == 'yes')) {
                return [
                    'status' => 'success',
                    'message' => 'Not Required',
                    'data' => null
                ];
            }
        } 
        if ($transactionType === 'purchase' && $taxCategory === ConstantHelper::TDS && $taxType === ConstantHelper::TDS_SECTION_194Q) {
            if ($companyTds != 'yes') {
                return [
                    'status' => 'success',
                    'message' => 'Not Required',
                    'data' => null
                ];
            }
        }
        //If GST or PAN is not specified 
        if ($partyGst && !$partyPan) {
            return [
                'status' => 'error',
                'message' => 'Party Pan not updated',
                'data' => null
            ];
        }
        //SALE
        if ($transactionType === 'sale' && $taxCategory === ConstantHelper::TCS && $taxType === ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS) {
            //Retrieve the TDS Tax
            $tax = Tax::with(['taxDetails' => function ($subQuery) {
                    $subQuery -> where('is_sale', 1)
                        ->where('status', ConstantHelper::ACTIVE);
                }])->where('tax_type', ConstantHelper::TCS_SECTION_SALE_OF_OTHER_GOODS)
                ->where('tax_category', ConstantHelper::TCS_CATEGORY)->first();
        } 
        if ($transactionType === 'purchase' && $taxCategory === ConstantHelper::TDS && $taxType === ConstantHelper::TDS_SECTION_194Q) { 
            //PURCHASE
            $tax = Tax::with(['taxDetails' => function ($subQuery) {
                    $subQuery -> where('is_purchase', 1)
                        ->where('status', ConstantHelper::ACTIVE);
                }])->where('tax_type', ConstantHelper::TDS_SECTION_194Q)
                ->where('tax_category', ConstantHelper::TDS_CATEGORY)->first();
        }
        //Tax not setup
        if (!$tax) {
            return [
                'status' => 'error',
                'message' => $taxLabel .' setup not found',
                'data' => null
            ];
        }
        //Detail setup not found
        $taxDetail = $tax -> taxDetails -> first();
        if (!$taxDetail) {
            return [
                'status' => 'error',
                'message' => $taxLabel .' detail setup not found',
                'data' => null
            ];
        }
        //Threshold check
        $taxthreshold = $taxDetail -> tax_threshold;
        if (!$taxthreshold || $taxthreshold <= 0) {
            return [
                'status' => 'error',
                'message' => $taxLabel .' threshold not defined',
                'data' => null
            ];
        }
        //PAN or W/O PAN Threshold
        if ($partyPan) {
            $taxPercentage = $taxDetail -> tax_percentage;
            if (!$taxPercentage || $taxPercentage <= 0) {
                return [
                    'status' => 'error',
                    'message' => $taxLabel .' percentage not defined',
                    'data' => null
                ];
            }
        } else {
            $taxPercentage = $taxDetail -> tax_percent_wo_pan;
            if (!$taxPercentage || $taxPercentage <= 0) {
                return [
                    'status' => 'error',
                    'message' => $taxLabel .' percentage without PAN not defined',
                    'data' => null
                ];
            }
        }
        //Get party current threshold limit
        $fyId = Helper::getFinancialYear($documentDate)['id'];
        $partyIds = [];
        if ($transactionType == 'sale') {
            //Retrieve customer IDS with same PAN number
            $partyIds = $partyPan
                ? Customer::where('pan_number', $partyPan)->pluck('id')->toArray()
                : [$party->id];
        } else {
            //Retrieve customer IDS with same PAN number
            $partyIds = $partyPan
                ? Vendor::where('pan_number', $partyPan)->pluck('id')->toArray()
                : [$party->id];
        }
        //Retrieve the current threshold utilized
        $partyTransactionAmt = ErpTaxThresholdUtilization::where('group_id', $groupId)
            ->where('company_id', $companyId)
            ->where('tax_category', $taxCategory)
            ->where('tax_type', $taxType)
            ->where('transaction_type', $transactionType)
            ->where('fy_id', $fyId)
            ->whereIn('party_id', $partyIds)
            ->selectRaw('SUM(COALESCE(opening_threshold, 0) + COALESCE(used_threshold, 0)) as total')
            ->value('total') ?? 0;
        
        $excessThreshold = $partyTransactionAmt - $oldNonTaxAssessableAmt - $taxthreshold;
        // dd($partyTransactionAmt, $oldNonTaxAssessableAmt, $taxthreshold);
        if ($excessThreshold < 0) {
            $assesableValue = max(($excessThreshold + $newTaxableAmount), 0);
        } else {
            $assesableValue = $newTaxableAmount;
        }
        if ($assesableValue <= 0) {
            return [
                'status' => 'success',
                'message' => $taxLabel .' not required',
                'data' => null
            ];
        }
        $taxAmount = round(($assesableValue * $taxPercentage / 100), 2);
        return [
            'status' => 'success',
            'message' => $taxLabel .' found',
            'data' => [
                'id' => $taxDetail->id,
                'applicability_type' => $taxDetail->applicability_type,
                'tax_group' => $tax->tax_group,
                'tax_percentage' => $taxPercentage,
                'tax_type' => $taxType,
                'tax_id' => $taxDetail->tax_id,
                'tax_code' => $taxType,
                'assesable_value' => $assesableValue,
                'tax_amount' => $taxAmount
            ]
        ];
    }

    public static function buildTaxThresholdUtilization(Model $document, string $transactionType, string $taxCategory, string $taxType, $nonTaxAssessableAmt = 0)
    {
        $fyYear = Helper::getFinancialYear($document -> document_date);
        $partyId = null;
        $partyCode = null;
        $partyName = null;
        if ($transactionType === 'sale') {
            $partyId = $document ?-> customer_id;
            $party = Customer::find($partyId);
            if (!$party) {
                return [
                    'status' => 'error',
                    'message' => 'Customer Not found'
                ];
            }
            $partyCode = $party -> customer_code;
            $partyName = $party -> company_name;
        } else {
            $partyId = $document ?-> vendor_id;
            $party = Vendor::find($partyId);
            if (!$party) {
                return [
                    'status' => 'error',
                    'message' => 'Vendor Not found'
                ];
            }
            $partyCode = $party -> vendor_code;
            $partyName = $party -> company_name;
        }
        //Create or update the summary
        $taxThresholdSummary = ErpTaxThresholdUtilization::firstOrCreate([
            'group_id' => $document -> group_id,
            'company_id' => $document -> company_id,
            'tax_category' => $taxCategory,
            'tax_type' => $taxType,
            'fy_id' => $fyYear['id'],
            'transaction_type' => $transactionType,
            'party_id' => $party -> id,
        ]);
        if ($taxThresholdSummary->wasRecentlyCreated) {
            $taxThresholdSummary -> fy_alias = $fyYear['alias'];
            $taxThresholdSummary -> party_name = $partyName;
            $taxThresholdSummary -> party_code = $partyCode; 
            $taxThresholdSummary -> currency_id = $document -> comp_currency_id;
            $taxThresholdSummary -> save();
        }
        //Increment the value or difference
        $taxThresholdSummary -> increment('used_threshold', $nonTaxAssessableAmt);
    }
}
