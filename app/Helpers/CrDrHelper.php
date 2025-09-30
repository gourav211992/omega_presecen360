<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Models\ItemDetail;
use App\Models\Voucher;
use App\Helpers\ConstantHelper;
use App\Helpers\Helper;

class CrDrHelper
{
    /**
     * Get all overdue values for a specific ledger in one optimized call
     *
     * @param string $type
     * @param array $ages_all
     * @param array $doc_types
     * @param string $cus_type
     * @param array $vouchers
     * @param int $credit_days
     * @param int $group
     * @param int $ledger
     * @param string $start
     * @param string $end
     * @param string $due_date
     * @return array
     */
    public static function get_overdue_all_values(
        $type,
        $ages_all,
        $doc_types,
        $cus_type,
        $vouchers,
        $credit_days,
        $group,
        $ledger,
        $start,
        $end,
        $due_date = "invoice"
    ) {
        $amount = $type . '_amt_org';
        [$ages0, $ages1, $ages2, $ages3, $ages4] = $ages_all;

        // Fetch items once and process all aggregations
        $vendors = ItemDetail::whereIn('voucher_id', $vouchers)
            ->where('ledger_id', $ledger)
            ->where($amount, '>', 0)
            ->withWhereHas('voucher', function ($query) {
                $query->whereIn('document_status', ConstantHelper::DOCUMENT_STATUS_APPROVED);
                $query->orderBy('document_date', 'asc');
                $query->orderBy('created_at', 'asc');
            })->get()
            ->groupBy('voucher_id')
            ->flatMap(function ($items) use ($ages0, $ages1, $ages2, $ages3, $ages4, $amount, $due_date) {
                $records = [];
                foreach ($items as $item) {
                    $totals = (object)[
                        'id' => $item->voucher_id,
                        'ledger_parent_id' => $item->ledger_parent_id,
                        'ledger_id' => $item->ledger_id,
                        'days_0_30' => 0,
                        'days_30_60' => 0,
                        'days_60_90' => 0,
                        'days_90_120' => 0,
                        'days_120_180' => 0,
                        'days_above_180' => 0,
                        'total_outstanding' => 0,
                        'invoice_amount' => 0,
                        'document_date' => "",
                        'days_diff' => 0,
                        'due_date' => $item->due_date
                    ];

                    $d_date = ($due_date == "invoice" || empty($item->due_date))
                        ? $item->voucher->document_date
                        : $item->due_date;

                    $totals->document_date = $d_date;

                    $documentDate = \Carbon\Carbon::parse($d_date)->format('Y-m-d');
                    $days_diff = $documentDate
                        ? now()->diffInDays(\Carbon\Carbon::createFromFormat('Y-m-d', $documentDate))
                        : 0;

                    if ($days_diff <= $ages0) {
                        $totals->days_0_30 = $item->$amount;
                    } elseif ($days_diff <= $ages1) {
                        $totals->days_30_60 = $item->$amount;
                    } elseif ($days_diff <= $ages2) {
                        $totals->days_60_90 = $item->$amount;
                    } elseif ($days_diff <= $ages3) {
                        $totals->days_90_120 = $item->$amount;
                    } elseif ($days_diff <= $ages4) {
                        $totals->days_120_180 = $item->$amount;
                    } else {
                        $totals->days_above_180 = $item->$amount;
                    }

                    $totals->invoice_amount = $item->$amount;
                    $totals->total_outstanding = $item->$amount;
                    $totals->days_diff = $days_diff;

                    $records[] = $totals;
                }
                return $records;
            })->values();

        $result = [];

        foreach ($vendors as $vendor) {
            $ages = CrDrReportController::getAgedReceipts([$vendor->id], $ages_all, $doc_types, $start, $end);
            $voucher = Voucher::find($vendor->id);

            $bill_no = "";
            $invoice_amount = "";
            $view_route = "";

            if ($voucher->reference_service != null) {
                $model = Helper::getModelFromServiceAlias($voucher->reference_service);
                if ($model != null) {
                    $referenceDoc = $model::find($voucher->reference_doc_id);
                    if ($referenceDoc) {
                        $bill_no = trim(
                            ($referenceDoc->doc_prefix ? $referenceDoc->doc_prefix . '-' : '') .
                            $referenceDoc->doc_no .
                            ($referenceDoc->doc_suffix ? '-' . $referenceDoc->doc_suffix : ''),
                            '-'
                        );
                    }
                    $invoice_amount = $vendor->invoice_amount;
                    $view_route = Helper::getRouteNameFromServiceAlias(
                        $voucher->reference_service,
                        $voucher->reference_doc_id
                    );
                }
            }

            $vs = $voucher->reference_service ? strtoupper($voucher->reference_service) . "-" : "";

            $result[] = [
                'id' => $voucher->id,
                'ledger_parent_id' => $vendor->ledger_parent_id,
                'ledger_id' => $vendor->ledger_id,
                'due_date' => $vendor->due_date,
                'bill_no' => $vs . $bill_no,
                'view_route' => $view_route,
                'created_at' => $voucher?->created_at,
                'voucher_no' => $voucher?->series?->book_code . "-" . $voucher->voucher_no,
                'document_date' => date('d-m-Y', strtotime($vendor->document_date)),
                'total_outstanding' => $vendor->total_outstanding - $ages[6],
                'days_0_30' => $vendor->days_0_30 - $ages[0],
                'days_30_60' => $vendor->days_30_60 - $ages[1],
                'days_60_90' => $vendor->days_60_90 - $ages[2],
                'days_90_120' => $vendor->days_90_120 - $ages[3],
                'days_120_180' => $vendor->days_120_180 - $ages[4],
                'days_above_180' => $vendor->days_above_180 - $ages[5],
                'overdue' => 0,
                'overdue_days' => 0,
                'diff_days' => $vendor->days_diff,
                'invoice_amount' => $invoice_amount,
            ];
        }

        $lastIndex = count($result) - 1;
        usort($result, fn($a, $b) => strtotime($a['document_date']) <=> strtotime($b['document_date']));

        // Apply advance adjustments (same logic as original get_overdue method)
        if ($ledger == null) {
            // On Account advance adjust
            $advanceData = [];
            $uniqueLedgerPairs = collect($result)->map(fn($res) => [
                'ledger_id' => $res['ledger_id'],
                'ledger_parent_id' => $res['ledger_parent_id']
            ])->unique()->values();

            foreach ($uniqueLedgerPairs as $pair) {
                $key = 'ledger' . $pair['ledger_id'] . '_parent' . $pair['ledger_parent_id'];
                $advance = CrDrReportController::getAdvanceOnAccountType(
                    $cus_type,
                    $pair['ledger_parent_id'],
                    $pair['ledger_id'],
                    $start,
                    $end,
                    'On Account'
                );
                $sum = (clone $advance)->sum('orgAmount');
                $latest = (clone $advance)->sortByDesc('document_date')->first();
                $advanceData[$key] = [
                    'remaining' => $sum,
                    'ageBucket' => $latest
                        ? CrDrReportController::get_bucket_ages(now()->diffInDays($latest->document_date), $ages_all)
                        : null,
                    'lastIndex' => null,
                ];
            }

            foreach ($result as $index => &$res) {
                $key = 'ledger' . $res['ledger_id'] . '_parent' . $res['ledger_parent_id'];
                if (!isset($advanceData[$key]) || $advanceData[$key]['remaining'] <= 0) {
                    continue;
                }
                $bucket = CrDrReportController::get_bucket_ages($res['diff_days'], $ages_all);
                $deduct = min($advanceData[$key]['remaining'], $res[$bucket]);
                $res[$bucket] -= $deduct;
                $res['total_outstanding'] -= $deduct;
                $advanceData[$key]['remaining'] -= $deduct;
                $advanceData[$key]['lastIndex'] = $index;
            }

            foreach ($advanceData as $key => $groupl) {
                if ($groupl['remaining'] > 0 && $groupl['ageBucket'] && $groupl['lastIndex'] !== null) {
                    $idx = $groupl['lastIndex'];
                    $bucket = $groupl['ageBucket'];
                    if (isset($result[$idx][$bucket])) {
                        $result[$idx][$bucket] -= $groupl['remaining'];
                        $result[$idx]['total_outstanding'] -= $groupl['remaining'];
                    }
                }
            }

            // Advance adjust (only for older than res date)
            $uniquePairs = collect($result)->map(fn($res) => [
                'ledger_id' => $res['ledger_id'],
                'ledger_parent_id' => $res['ledger_parent_id']
            ])->unique();

            foreach ($uniquePairs as $pair) {
                $advanceItems = CrDrReportController::getAdvanceOnAccountType(
                    $cus_type,
                    $pair['ledger_parent_id'],
                    $pair['ledger_id'],
                    $start,
                    $end,
                    'Advance'
                );
                $remainingAdvanceAmount = $advanceItems->sum('orgAmount');
                if ($remainingAdvanceAmount > 0) {
                    foreach ($result as &$res) {
                        if ($res['ledger_id'] == $pair['ledger_id'] && $res['ledger_parent_id'] == $pair['ledger_parent_id']) {
                            foreach ($advanceItems as $advanceItem) {
                                $voucherDate = $advanceItem->voucher->document_date;
                                $voucherCreatedAt = $advanceItem->voucher->created_at;
                                $voucherTime = date('H:i:s', strtotime($voucherCreatedAt));
                                $voucherDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $voucherDate . ' ' . $voucherTime);
                                $advanceVoucherDate = $voucherDateTime ? $voucherDateTime->getTimestamp() : null;

                                $resDocDate = $res['document_date'];
                                $resCreatedAt = $res['created_at'];
                                $resTime = date('H:i:s', strtotime($resCreatedAt));
                                $resDateTime = \DateTime::createFromFormat('d-m-Y H:i:s', $resDocDate . ' ' . $resTime);
                                $resDate = $resDateTime ? $resDateTime->getTimestamp() : null;

                                if ($advanceVoucherDate < $resDate) {
                                    $buckets = ['days_0_30', 'days_30_60', 'days_60_90', 'days_90_120', 'days_120_180', 'days_above_180'];
                                    foreach ($buckets as $bucket) {
                                        if ($remainingAdvanceAmount <= 0) break;
                                        $deductAmount = min($remainingAdvanceAmount, $res[$bucket]);
                                        $res[$bucket] -= $deductAmount;
                                        $remainingAdvanceAmount -= $deductAmount;
                                        $res['total_outstanding'] -= $deductAmount;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            // Ledger specific advance adjust
            $advance = CrDrReportController::getAdvanceOnAccountType($cus_type, $group, $ledger, $start, $end, 'On Account');
            $advanceSum = (clone $advance)->sum('orgAmount');
            $advanceAges = (clone $advance)->sortByDesc('document_date')->first();
            if ($advanceAges) {
                $difDays = now()->diffInDays($advanceAges->document_date);
                $avanceAgesbucket = CrDrReportController::get_bucket_ages($difDays, $ages_all);
            }
            foreach ($result as &$res) {
                $bucket = CrDrReportController::get_bucket_ages($res['diff_days'], $ages_all);
                if ($advanceSum > 0) {
                    $deductAmount = min($advanceSum, $res[$bucket]);
                    $res[$bucket] -= $deductAmount;
                    $advanceSum -= $deductAmount;
                    $res['total_outstanding'] -= $deductAmount;
                }
            }
            if (isset($avanceAgesbucket) && $advanceSum > 0) {
                $result[$lastIndex][$avanceAgesbucket] -= $advanceSum;
                $result[$lastIndex]['total_outstanding'] -= $advanceSum;
            }

            $advanceItems = CrDrReportController::getAdvanceOnAccountType($cus_type, $group, $ledger, $start, $end, 'Advance');
            $remainingAdvancesByDate = [];
            foreach ($advanceItems as $advanceItem) {
                $documentDate = $advanceItem->voucher->document_date;
                $createdAt = $advanceItem->voucher->created_at;
                $advanceDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $documentDate . ' ' . date('H:i:s', strtotime($createdAt)));
                $vendorDateTimestamp = $advanceDateTime ? $advanceDateTime->getTimestamp() : null;
                if ($vendorDateTimestamp) {
                    $remainingAdvancesByDate[$vendorDateTimestamp] = (int)$advanceItem->orgAmount;
                }
            }

            foreach ($result as $index => &$res) {
                $bucket = CrDrReportController::get_bucket_ages($res['diff_days'], $ages_all);
                $docDateInput = Carbon::createFromFormat('d-m-Y', $res['document_date'])->format('Y-m-d');
                $createdTimeInput = $res['created_at'];
                $timeFromCreated = date('H:i:s', strtotime($createdTimeInput));
                $resDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $docDateInput . ' ' . $timeFromCreated);
                $resDateTimestamp = $resDateTime ? $resDateTime->getTimestamp() : null;

                $filtered = array_filter(
                    $remainingAdvancesByDate,
                    fn($v, $k) => $k < $resDateTimestamp && $v > 0,
                    ARRAY_FILTER_USE_BOTH
                );

                if (!empty($filtered) && $res[$bucket] > 0) {
                    foreach ($filtered as $advanceDate => $advanceAmount) {
                        if ($res[$bucket] <= 0) break;
                        $deductAmount = min($advanceAmount, $res[$bucket]);
                        $res[$bucket] -= $deductAmount;
                        $remainingAdvancesByDate[$advanceDate] -= $deductAmount;
                        $res['total_outstanding'] -= $deductAmount;
                    }
                }
            }
        }

        // Final overdue calculation
        foreach ($result as &$res) {
            $creditDays = $credit_days ?? 0;
            $dueDate = date('d-m-Y', strtotime("+$creditDays days", strtotime($res['document_date'])));
            $today = date('d-m-Y');
            $overdue = (strtotime($today) > strtotime($dueDate)) ? $res['total_outstanding'] : 0;
            $overdueDays = (strtotime($today) > strtotime($res['document_date']))
                ? floor((strtotime($today) - strtotime($res['document_date'])) / (60 * 60 * 24))
                : 0;
            $res['overdue'] = $overdue;
            $res['overdue_days'] = ($res['total_outstanding'] > 0) ? (int)$overdueDays : "-";
        }

        // Return all aggregated values instead of just one
        return [
            'overdue' => array_sum(array_column($result, 'overdue')),
            'days_0_30' => array_sum(array_column($result, 'days_0_30')),
            'days_30_60' => array_sum(array_column($result, 'days_30_60')),
            'days_60_90' => array_sum(array_column($result, 'days_60_90')),
            'days_90_120' => array_sum(array_column($result, 'days_90_120')),
            'days_120_180' => array_sum(array_column($result, 'days_120_180')),
            'days_above_180' => array_sum(array_column($result, 'days_above_180')),
            'total_outstanding' => array_sum(array_column($result, 'total_outstanding')),
        ];
    }
}
