<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expense Allocation</title>
    <style>
        /* ---------- Page & Base ---------- */
        @page {
            margin: 20mm 12mm 18mm 12mm;
        }

        html,
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .title {
            font-size: 24px;
            font-weight: 100;
            text-align: center;
            margin: 0 0 6px 0;
        }

        /* ---------- Helpers ---------- */
        .fw-900 {
            font-weight: 900
        }

        .fw-700 {
            font-weight: 700
        }

        .fs-13 {
            font-size: 13px
        }

        .text-center {
            text-align: center
        }

        .text-right {
            text-align: right
        }

        .wrap {
            word-wrap: break-word;
            overflow-wrap: anywhere
        }

        .mb-4 {
            margin-bottom: 4px
        }

        .mb-6 {
            margin-bottom: 6px
        }

        /* ---------- Boxes / tables ---------- */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px 4px
        }

        th {
            font-weight: bold;
            text-align: center
        }

        .box {
            border: 1px solid #000;
            padding: 6px
        }

        .no-border {
            border: none !important
        }

        .thead-gray {
            background: #cccccc;
            background: rgba(128, 128, 128, .44)
        }

        thead {
            display: table-header-group
        }

        tfoot {
            display: table-row-group
        }

        tr {
            page-break-inside: avoid
        }

        /* Column widths */
        .col-idx {
            width: 22px
        }

        .col-item {
            width: 31%
        }

        .col-sm {
            width: 70px
        }

        .col-md {
            width: 90px
        }

        .col-lg {
            width: 110px
        }

        .col-date {
            width: 120px
        }

        /* Section bar that “attaches” to table */
        .section-title {
            font-weight: 900;
            font-size: 13px;
            padding: 6px 8px;
            border: 1px solid #000;
            border-bottom: none;
            background: #cccccc;
            background: rgba(128, 128, 128, .44);
            text-transform: uppercase;
            margin: 10px 0 0 0;
            page-break-after: avoid;
        }

        .table-attached {
            margin-top: 0
        }

        .tight-top thead th {
            border-top: none !important
        }

        /* Zebra rows to improve readability */
        tbody tr:nth-child(odd) td {
            background: #fafafa
        }

        /* Status colors */
        .status {
            font-weight: 900;
            white-space: nowrap
        }

        .submitted {
            color: #17a2b8
        }

        .draft {
            color: #6c757d
        }

        .approved {
            color: #28a745
        }

        .rejected {
            color: #dc3545
        }

        .other {
            color: #007bff
        }

        /* Footer */
        .footer {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            font-size: 12px
        }
    </style>
</head>

<body>

    <!-- Header -->
    <table class="no-border" style="border-collapse:separate; border-spacing:0">
        <tr>
            <td class="no-border" style="vertical-align:top; width:25%">
                @if (isset($orgLogo) && $orgLogo)
                    <img src="{!! $orgLogo !!}" alt="" height="50px" />
                @else
                    <img src="{{ $imagePath }}" height="50px" alt="">
                @endif
            </td>
            <td class="no-border" style="width:50%">
                <div class="title">Expense Allocation</div>
            </td>
            <td class="no-border" style="width:25%"></td>
        </tr>
    </table>

    <!-- Top boxes -->
    <table class="mb-6" style="border-collapse:separate; border-spacing:0 6px">
        <tr>
            <td class="box" style="width:60%; vertical-align:top;">
                <div class="fw-900 fs-13" style="margin-bottom:4px">Buyer Name & Address:</div>
                <div class="fw-700 fs-13"><b>{{ Str::ucfirst(@$organization->name) }}</b></div>
                <div style="margin-top:4px">
                    {{ @$organizationAddress->line_1 }}, {{ @$organizationAddress->line_2 }},
                    {{ @$organizationAddress->line_3 }}
                </div>
                <div style="margin-top:3px">
                    {{ @$organizationAddress?->city?->name }}, {{ @$organizationAddress?->state?->name }},
                    {{ @$organizationAddress?->country?->name }}, Pin Code: {{ @$organizationAddress->pincode }}
                </div>
                <div style="margin-top:3px">
                    @if (@$organizationAddress->phone)
                        Phone: {{ @$organizationAddress->phone }},
                    @endif
                    @if (@$organization?->email)
                        Email: {{ @$organization?->email }}
                    @endif
                </div>
            </td>

            <td class="box" style="width:40%; vertical-align:top;">
                <table class="no-border">
                    <tr class="no-border">
                        <td class="no-border"><b>Expense Allocation No:</b></td>
                        <td class="no-border fw-900">{{ @$exp->book_code }} - {{ @$exp->document_number }}</td>
                    </tr>
                    @if ($exp->document_date)
                        <tr class="no-border">
                            <td class="no-border"><b>Expense Allocation Date:</b></td>
                            <td class="no-border fw-900">{{ date('d-M-y', strtotime($exp->document_date)) }}</td>
                        </tr>
                    @endif
                    @if ($exp->reference_number)
                        <tr class="no-border">
                            <td class="no-border"><b>Reference :</b></td>
                            <td class="no-border fw-900">{{ $exp->reference_number }}</td>
                        </tr>
                    @endif
                    <tr class="no-border">
                        <td class="no-border"><b>Status :-</b></td>
                        <td class="no-border">
                            @php
                                $status = $exp->document_status;
                                $display = $exp->display_status;
                                $cls = match ($status) {
                                    'submitted' => 'submitted',
                                    'draft' => 'draft',
                                    'approved', 'approval_not_required' => 'approved',
                                    'rejected' => 'rejected',
                                    default => 'other',
                                };
                                if (in_array($status, ['approved', 'approval_not_required'])) {
                                    $display = 'Approved';
                                }
                            @endphp
                            <span class="status {{ $cls }}">{{ $display }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td class="box" colspan="2">
                <div class="fw-900 fs-13" style="margin-bottom:4px">Delivery Address:</div>
                <div class="fw-700 fs-13"><b>{{ Str::ucfirst(@$mrn?->erpStore?->store_name ?? '') }}</b></div>
                <div style="margin-top:3px">{{ @$buyerAddress->address }}</div>
                <div style="margin-top:3px">
                    {{ @$buyerAddress?->city?->name }}, {{ @$buyerAddress?->state?->name }},
                    {{ @$buyerAddress?->country?->name }}, Pin Code: {{ @$buyerAddress->pincode }}
                </div>
                <div style="margin-top:3px">
                    @if (@$buyerAddress->phone)
                        Phone: {{ @$buyerAddress->phone }},
                    @endif
                    @if (@$organization?->email)
                        Email: {{ @$organization?->email }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- PO Details -->
    <div class="section-title">PO Details</div>
    <table class="table-attached tight-top mb-6">
        <thead>
            <tr class="thead-gray">
                <th class="col-idx">#</th>
                <th class="col-item">Item</th>
                <th class="col-sm">UOM</th>
                <th class="col-sm">Quantity</th>
                <th class="col-sm">Rate</th>
                <th class="col-sm">Value ({{ $exp->currency?->short_name }})</th>
                <th class="col-md">Allocation Type</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($exp->poDetails as $key => $val)
                <tr>
                    <td class="text-center">{{ $key + 1 }}</td>
                    <td class="wrap">
                        @php $poNumber = ($val->poHeader->book_code ?? '').' '.($val->poHeader->document_number ?? ''); @endphp
                        <b>{{ @$val->item_name }}</b> {{ @$val->item_code }}<br>
                        <b>HSN Code : </b>{{ $val?->hsn_code }}<br>
                        @if ($val?->poHeader)
                            <b>Vendor : </b>{{ $val?->vendor_name }}<br>
                            <b>PO No. : </b>{{ $poNumber }}<br>
                            <b>PO Date : </b>{{ $val?->poHeader?->getFormattedDate('document_date') }}<br>
                        @endif
                        {{ @$val->remark }}
                    </td>
                    <td class="text-center">{{ @$val->uom->name }}</td>
                    <td class="text-right">{{ number_format(@$val->receipt_qty, 2) }}</td>
                    <td class="text-right">{{ number_format(@$val->rate, 2) }}</td>
                    <td class="text-right">{{ number_format($val->value, 2) }}</td>
                    <td class="text-center">{{ ucfirst(@$val->allocation_type) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- GRN Details -->
    <div class="section-title">GRN Details</div>
    <table class="table-attached tight-top mb-6">
        <thead>
            <tr class="thead-gray">
                <th class="col-idx">#</th>
                <th class="col-item">Item</th>
                <th class="col-sm">UOM</th>
                <th class="col-sm">Quantity</th>
                <th class="col-sm">Value ({{ $exp->currency?->short_name }})</th>
                <th class="col-sm">Weight</th>
                <th class="col-sm">Volume</th>
                <th class="col-md">Allocated Cost</th>
                <th class="col-md">Landed Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($exp->grnDetails as $key => $val)
                <tr>
                    <td class="text-center">{{ $key + 1 }}</td>
                    <td class="wrap">
                        @php $grnNumber = ($val?->mrnHeader?->book_code ?? '').' '.($val?->mrnHeader?->document_number ?? ''); @endphp
                        <b>{{ @$val->item_name }}</b>
                        @if (isset($val->attributes))
                            <br>
                            @php
                                $arrr = $val->attributes
                                    ? $val->attributes()->whereNotNull('attr_value')->pluck('attr_value')->all()
                                    : [];
                                $first = true;
                            @endphp
                            @foreach ($val->item->itemAttributes as $itemAttribute)
                                @if (count($arrr))
                                    @foreach ($itemAttribute->attributes() as $value)
                                        @if (in_array($value->id, $arrr))
                                            @if (!$first)
                                                ,
                                            @endif
                                            {{ $value->attributeGroup->name }}:{{ ucfirst($value->value) }}
                                            @php $first = false; @endphp
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach
                            <br>
                        @endif
                        {{ @$val->item_code }}<br>
                        <b>HSN Code : </b>{{ $val?->hsn_code }}<br>
                        <b>Vendor : </b>{{ $val?->vendor_name }}<br>
                        <b>GRN No. : </b>{{ $grnNumber }}<br>
                        <b>GRN Date : </b>{{ $val?->mrnHeader?->getFormattedDate('document_date') }}<br>
                        {{ @$val->remark }}
                    </td>
                    <td class="text-center">
                        {{ $val?->uom?->name }}
                    </td>
                    <td class="text-right">
                        {{ number_format(@$val->receipt_qty, 2) }}
                    </td>
                    <td class="text-right">
                        {{ number_format(@$val->value, 2) }}
                    </td>
                    <td class="text-right">
                        {{ number_format(@$val->weight, 2) }}
                    </td>
                    <td class="text-right">
                        {{ number_format(@$val->volume, 2) }}
                    </td>
                    <td class="text-right">
                        {{ number_format(@$val->allocated_cost, 2) }}
                    </td>
                    <td class="text-right">
                        {{ number_format(@$val->landed_cost, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Amounts & remark -->
    <table class="mb-6" style="border-collapse:separate; border-spacing:0 6px">
        <tr>
            <td class="box" style="width:40%">
                <b>Allocated Cost In Words</b><br>
                {{ @$allocatedCostInWords }}<br><br>
                <b>Landed Cost In Words</b><br>
                {{ @$landedCostInWords }}<br><br>
                <b>Currency:</b> {{ @$exp->currency->name }}
            </td>
            <td class="box" style="width:60%">
                <table style="width: 100%; margin-bottom: 0px; margin-top: 10px;" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="text-align: right;">
                            <b>Total Expenses :</b>
                        </td>
                        <td style="text-align: right;">
                            {{ number_format($totalPoValue, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-top: 3px;">
                            <b>Total GRN Value : </b>
                        </td>
                        <td style="text-align: right; padding-top: 3px;">
                            {{ number_format($totalGrnValue, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-top: 3px;">
                            <b>Total Allocated Cost : </b>
                        </td>
                        <td style="text-align: right; padding-top: 3px;">
                            {{ number_format($totalAllocatedCost, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: right; padding-top: 3px;">
                            <b>Total Landed Cost : </b>
                        </td>
                        <td style="text-align: right; padding-top: 3px;">
                            {{ number_format($totalLandedCost, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <table class="mb-6" style="border-collapse:separate; border-spacing:0 6px">
        <tr>
            <td class="box" style="width:60%">
                Created By : {{ @$exp->createdBy->name }}<br>
                Printed By : {{ @$user->name }}<br>
                Remark : {{ $exp->remark }}
            </td>
            <td class="box" style="width:40%; vertical-align:bottom;">
                <div class="text-center" style="margin-bottom:12px">FOR
                    <b>{{ Str::ucfirst(@$organization->name) }}</b>
                </div>
                <div>This is a computer generated document hence not require any signature.</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Regd. Office: {{ @$organizationAddress->getFullAddressAttribute() }}
    </div>
</body>

</html>
