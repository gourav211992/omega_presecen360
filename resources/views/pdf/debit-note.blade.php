<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .status {
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
        }

        .qr-container {
            text-align: center;
            padding: 0px;
        }

        .qr-container img {
            width: 250px;
            height: 250px;
        }
    </style>
</head>

<body>
    <div style="width:700px; font-size: 11px; font-family:Arial;">
        <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
            <tr><td colspan="3" style="width:100%; text-align:center; font-size:18px; font-weight: 900;">Debit Note</td></tr>
            <tr>
                <td style="border: 1px solid #000;  border-bottom: none; padding: 3px; width: 30%; vertical-align: top;">
                    @if (isset($orgLogo) && $orgLogo)
                        <img src="{!! $orgLogo !!}" alt="" height="50px" />
                    @else
                        <img src="{{ $imagePath }}" height="50px" alt="">
                    @endif
                </td>
                <td style="border: 1px solid #000;  border-bottom: none; padding: 3px; width: 40%; vertical-align: top; font-size: 10px;">
                    @php
                        $addressParts = array_filter([
                            $organizationAddress->line_1 ?? null,
			    $organizationAddress->line_2 ?? null,
			    $organizationAddress->line_3 ?? null,
                            $organizationAddress?->city?->name ?? null,
                        ]);

                        $countryPincode = array_filter([
                            $organizationAddress?->state?->name ?? null,
                            $organizationAddress?->country?->name ?? null,
                            $organizationAddress->pincode ?? null
                        ]);

                        $gstinPan = [];
                        $email ='';
                        $phone ='';
                        if (!empty($organization->gst_number)) {
                            $gstinPan[] = 'GSTIN: ' . $organization->gst_number;
                        }

                        if (!empty($organization->pan_number)) {
                            $gstinPan[] = 'Pan No: ' . $organization->pan_number;
                        }
                        if (!empty($organization->email)) {
                            $email = 'Email: ' . $organization->email;
                        }
                        if (!empty($organization->phone)) {
                            $phone = 'Phone No: ' . $organization->phone;
                        }
                    @endphp
                    <div style="padding: 5px;">
                        <div style="font-weight: bold; font-size: 18px; text-align: left;">
                            {{ @$organization->name }}
                        </div>
                    </div>

                    {{ implode(', ', $addressParts) }},<br>
                    {{ implode(', ', $countryPincode) }} . <b>STATE CODE: {{ $organizationAddress?->state?->state_code }}</b>
                    @if($email)
                        <br>
                        {{ $email }}
                    @endif
                    @if($phone)
                        {{ $phone }}
                    @endif
                    @if(count($gstinPan))
                    <br>
                        {{ implode(', ', $gstinPan) }}
                    @endif
                </td>
                <td style="border: 1px solid #000;  border-bottom: none; padding: 3px; width: 30%; vertical-align: top;">
                    <b>Return No.:</b>
                    {{ @$pb->book_code . '-' . @$pb->document_number }}

                    @if($pb->document_date)
                    <br>
                    <b>Return Date:</b>
                    {{ date('d-M-y', strtotime($pb->document_date)) }}
                    <br>
                    @endif
                    <b style="font-weight: 900;">Status :</b>
                    @if($pb->document_status == 'submitted')
                        <span class="status" style="color: #17a2b8 ">
                            {{ $pb->display_status }}
                        </span>
                    @elseif($pb->document_status == 'draft')
                        <span style="color: #6c757d">
                            {{ $pb->display_status }}
                        </span>
                    @elseif($pb->document_status == 'approved' || $pb->document_status == "approval_not_required")
                        <span style="color: #28a745">
                            Approved
                        </span>
                    @elseif($pb->document_status == 'rejected')
                        <span style="color: #dc3545">
                            {{ $pb->display_status }}
                        </span>
                    @else
                        <span style="color: #007bff">
                            {{ $pb->display_status }}
                        </span>
                    @endif
                    <br>
                    <b>Reference</b> : {{ Str::upper($pb->reference_type) }}
            </tr>
        </table>
        <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="border: 1px solid #000;  border-bottom: none; padding: 3px; width: 30%; vertical-align: top;">
                    <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
                        <tr>
                            <td colspan="1" style="font-weight: 900; font-size: 13px; padding-bottom: 3px; padding-top: 10px; ">BILL TO:
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px;" colspan="3">
                                <span style="font-weight: 900; vertical-align: top; padding-top:10px">
                                    {{ @$pb?->vendor?->company_name }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 3px;" colspan="2">
                            {{@$billingAddress->address}},
                            {{ @$billingAddress?->city?->name }},<br>
                            {{ @$billingAddress?->state?->name }},
                            {{ @$billingAddress?->country?->name }},
                            PinCode :{{ @$billingAddress->pincode }} <br> <b>STATE CODE: {{ @$billingAddress?->state?->state_code }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                @if (@$billingAddress->phone)
                                Phone: {{ @$billingAddress->phone }} ,
                                @endif
                                @if (@$pb->vendor?->email)
                                Email: {{ @$pb->vendor?->email }}
                                @endif
                            </td>
                        </tr>

                        @if (@$pb->vendor?->compliances?->gstin_no)
                        <tr>
                            <td style="padding-top: 2px; padding-bottom:10px;" colspan="3">
                                GSTIN: {{ @$pb->vendor?->compliances?->gstin_no }}
                            </td>
                        </tr>
                        @endif
                        @if(@$pb->vendor?->compliances?->pan_number)
                        <tr>
                            <td style="padding-top: 2px; padding-bottom:10px;" colspan="3">
                                PAN No: {{ @$pb->vendor?->compliances?->pan_number }}
                            </td>
                        </tr>
                        @endif
                    </table>
                </td>

                <td style="border: 1px solid #000; border-bottom: none; padding: 3px;  vertical-align: top;" >
                    <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">

                    <tr>
                        <td colspan="1" style="font-weight: 900; font-size: 13px; padding-bottom: 3px; padding-top: 10px;">SHIP TO:</td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding-top: 10px;">
                            <span style="font-weight: 900;">
                                {{ Str::ucfirst($pb->store->store_name ?? '') }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding-top: 5px;">
                            {{ @$pb?->location_address_details->address }}
                            @if (@$pb?->location_address_details?->city?->name), {{ @$pb?->location_address_details?->city?->name }}, @endif
                            <br>
                            @if (@$pb?->location_address_details?->state?->name) {{ @$pb?->location_address_details?->state?->name }}, @endif
                            @if (@$pb?->location_address_details?->country?->name) {{ @$pb?->location_address_details?->country?->name }}, @endif
                            @if (@$pb?->location_address_details->pincode) PinCode : {{ @$pb?->location_address_details->pincode }} @endif
                            <br><b>STATE CODE: {{ @$pb?->location_address_details?->state?->state_code }}</b>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3">
                            @if (@$pb->vendor?->phone)
                            Phone: {{ @$pb->vendor?->phone ?? @$pb->vendor?->mobile }} ,
                            @endif
                            @if (@$pb->vendor?->email)
                            Email: {{ @$pb->vendor?->email }}
                            @endif
                        </td>
                    </tr>
                    @if (@$pb->vendor?->compliances?->gstin_no)
                    <tr>
                        <td style="padding-top: 2px; padding-bottom:10px;" colspan="3">
                            GSTIN: {{ @$pb->vendor?->compliances?->gstin_no }}
                        </td>
                    </tr>
                    @endif
                    @if(@$pb->vendor?->compliances?->pan_number)
                    <tr>
                        <td style="padding-top: 2px; padding-bottom:10px;" colspan="3">
                            PAN No: {{ @$pb->vendor?->compliances?->pan_number }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
            <td rowspan="2" style="border: 1px solid #000; border-bottom: none; border-left: none; vertical-align: top; width: 35%; text-align: center;">
                @if(isset($qrCodeBase64))
                    <img src="{{ $qrCodeBase64 }}" style="margin-top:10px; display: inline-block;" width="80%" alt="QR Code">
                @endif
            </td>
            </tr>
            <tr>
                <td colspan="2" style="border: 1px solid #000; padding: 10px 3px; vertical-align: top; border-bottom: none; text-align: left;">
                    @if(isset($eInvoice->irn_number))
                        <b>IRN : </b>{{ $eInvoice->irn_number ?? '' }}<br>
                    @endif
                    @if(isset($eInvoice->ack_no))
                        <b>Acknowledgment No : </b>{{ $eInvoice->ack_no ?? '' }}<br>
                    @endif
                    @if(isset($eInvoice->ewb_no))
                        <b>EWB Number: </b>
                        {{ $eInvoice->ewb_no ?? '' }},

                        <b>EWB Date: </b>
                        {{ Carbon\Carbon::parse($eInvoice->ewb_date) -> format('d-m-Y h:i A') ?? '' }},

                        <b>EWB Validity: </b>
                        {{ Carbon\Carbon::parse($eInvoice->ewb_valid_till) -> format('d-m-Y h:i A') ?? '' }}<br>
                    @endif
                    @if(isset($pb->transportation_mode))
                        <b>Mode of Transport: </b>
                        {{ $pb->transportation_mode ?? '' }},
                    @endif
                    @if(isset($pb->transporter_name))
                        <b>Transporter Name: </b>{{ $pb->transporter_name ?? '' }},
                    @endif
                    @if(isset($pb->transporter_gstin))
                        <b>Transporter GSTIN: </b>{{ $pb->transporter_gstin ?? '' }},
                    @endif
                    @if(isset($pb->vehicle_no))
                        <b>Vehicle No:  </b>
                        {{ $pb->vehicle_no ?? '' }}
                    @endif
                </td>
            </tr>
        </table>
        <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
            <tr>
                <td
                    style="padding: 2px; border: 1px solid #000; border-top: none; background: #80808070; text-align: center; font-weight: bold;">
                    #
                </td>
                <td
                    style="font-weight: bold; width: 31.25%; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    <div style="">Item</div>
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    HSN Code
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    Quantity
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    UOM
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    Rate
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    Value
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    Discount
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; background: #80808070; text-align: center;">
                    Taxable<br> Value
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; text-align: center; background: #80808070;">
                    Tax <br> Amt
                </td>
                <td
                    style="font-weight: bold; padding: 2px; border: 1px solid #000; border-top: none; border-left: none; text-align: center; background: #80808070;">
                    Tax <br>Group
                </td>
            </tr>
            {{-- <tr>
            </tr> --}}
            @php
                $taxBracket = [];
                $totalCGSTValue = 0.0;
                $totalSGSTValue = 0.0;
                $totalIGSTValue = 0.0;
                $totalTaxValue = 0.0;
            @endphp
            @foreach ($pb->items as $key => $val)
                <tr>
                    <td
                        style=" vertical-align: top; padding:10px 3px; border: 1px solid #000; border-top: none;  text-align: center;">
                        {{ $key + 1 }}
                    </td>
                    <td
                        style="vertical-align: top; padding:10px 3px; text-align:left; border: 1px solid #000; border-top: none; border-left: none;">
                        <div style="max-width:180px;word-wrap:break-word;">
                            <b> {{ @$val->item->item_name }}</b>
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
                                                    {{ ',' }}
                                                @endif
                                                {{ $value->attributeGroup->name }}:{{ ucfirst($value->value) }}
                                                @php
                                                    $first = false;
                                                @endphp
                                            @endif
                                        @endforeach
                                    @endif
                                @endforeach
                                <br>
                            @endif
                            @if (isset($val->specifications))
                                @foreach ($val->specifications as $data)
                                    @if (isset($data->value))
                                        {{ $data->specification_name }}:{{ $data->value }}<br>
                                    @endif
                                @endforeach
                            @endif
                            {{ @$val->item_code }}<br />
                            {{ @$val->remark }}
                        </div>
                    </td>
                    <td
                        style=" vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">
                        {{ @$val->hsn_code }}
                    </td>
                    <td
                        style=" vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">
                        {{ number_format(@$val->accepted_qty, 2) }}
                    </td>
                    <td
                        style="vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">
                        {{ @$val->uom->name }}
                    </td>
                    <td
                        style="vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">
                        {{ number_format(@$val->rate, 2) }}
                    </td>
                    @php
                        $total = $val->accepted_qty * $val->rate;
                    @endphp
                    <td
                        style="vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">
                        {{ number_format($total, 2) }}
                    </td>
                    <td
                        style="vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none;  text-align: right;">
                        {{ number_format($val->discount_amount + $val->header_discount_amount, 2) }}
                    </td>
                    @php
                        $total = $val->accepted_qty * $val->rate;
                        $netValue = $total - ($val->discount_amount + $val->header_discount_amount);
                    @endphp
                    <td
                        style="vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none;  text-align: right;">
                        {{ number_format($netValue, 2) }}
                    </td>
                    <td
                        style=" vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none;  text-align: right;">
                        @php
                            if (count($val->taxes)) {
                                foreach ($val->taxes as $taxs) {
                                    $taxName = $taxs->ted_name . ' ' . number_format($taxs->ted_percentage, 2) . ' %';
                                    if (isset($taxBracket[$taxName])) {
                                        $taxBracket[$taxName][0] += $taxs->ted_amount;
                                        $taxBracket[$taxName][1] += $taxs->assesment_amount;
                                    } else {
                                        $taxBracket[$taxName][0] = $taxs->ted_amount;
                                        $taxBracket[$taxName][1] = $taxs->assesment_amount;
                                    }
                                }
                            }
                            $totalCGSTValue += $val->cgst_value['value'];
                            $totalSGSTValue += $val->sgst_value['value'];
                            $totalIGSTValue += $val->igst_value['value'];
                            $totalTaxValue = $totalCGSTValue + $totalIGSTValue + $totalSGSTValue;

                        @endphp
                        {{-- {{isset($val?->taxes?->first()->ted_amount) ? $val->taxes->first()->ted_amount : "NA"}} --}}
                        {{ number_format($val->cgst_value['value'] + $val->sgst_value['value'] + $val->igst_value['value'], 2) }}
                    </td>
                    <td
                        style="vertical-align: middle; padding:10px 3px; border: 1px solid #000; border-top: none; border-left: none;  text-align: center;">
                        {{ $val?->ted_tax?->taxDetail?->erpTax?->tax_group ?? 'NA' }}
                    </td>
                </tr>
            @endforeach
        </table>

        <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
            <tr>
                <td style="padding: 3px; border: 1px solid #000; width: 60%; border-top: none; vertical-align: top;">
                    <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
                        <tr>
                            <td> <b> Amount In Words</b> <br>
                                {{ @$amountInWords }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 15px;"><b>Currency:</b> {{ @$pb->currency->name }} </td>
                        </tr>
                        <tr>
                            <td style="padding-top: 10px;"><b>Payment Terms :</b>
                                {{ @$pb->paymentTerm->name }}
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                    </table>

                </td>
                <td
                    style="padding: 3px; border: 1px solid #000; border-top: none; border-left: none; vertical-align: top;">
                    <table style="width: 100%; margin-bottom: 0px; margin-top: 10px;" cellspacing="0"
                        cellpadding="0">
                        <tr>
                            <td style="text-align: right;">
                                <b>Item Total :</b>
                            </td>
                            <td style="text-align: right;">
                                {{ number_format($totalItemValue, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding-top: 3px;">
                                <b>Discount:</b>
                            </td>
                            <td style="text-align: right; padding-top: 3px;">
                                {{ number_format($totalDiscount, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding-top: 3px;">
                                <b>Taxable Value:</b>
                            </td>
                            <td style="text-align: right; padding-top: 3px;">
                                {{ number_format($totalTaxableValue, 2) }}
                            </td>
                        </tr>
                        @foreach ($taxBracket as $tax => $value)
                            <tr>
                                <td style="text-align: right; padding-top: 3px;">
                                    <b>{{ $tax }} @ {{ number_format($value[1], 2) }}:</b>
                                </td>
                                <td style="text-align: right; padding-top: 3px;">
                                    {{ number_format($value[0], 2) }}
                                </td>
                            </tr>
                        @endforeach
                        @if (isset($pb?->expenses) && count($pb?->expenses))
                            <tr>
                                <td style="text-align: right; padding-top: 3px;">
                                    <b>Total After Tax:</b>
                                </td>
                                <td style="text-align: right; padding-top: 3px;">
                                    {{ number_format($totalAfterTax, 2) }}
                                </td>
                            </tr>
                            @foreach ($pb->expenses as $key => $pbense)
                                <tr>
                                    <td style="text-align: right; padding-top: 3px;">
                                        <b>{{ ucFirst($pbense->ted_name) }} :</b>
                                    </td>
                                    <td style="text-align: right; padding-top: 3px;">
                                        {{ number_format(@$pbense->ted_amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        <tr>
                            <td style="text-align: right; padding-top: 3px;">
                                <b>Total Value:</b>
                            </td>
                            <td style="text-align: right; padding-top: 3px;">
                                {{ number_format($totalAmount, 2) }}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2"
                    style="padding: 3px; border: 1px solid #000; width: 50%; border-top: none; vertical-align: top;">
                    <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="font-weight: bold; font-size: 13px;"> <b>Remark :</b></td>
                        </tr>
                        <tr>
                            <td>
                                <div style="min-height: 80px;">
                                    {{ $pb->remarks }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>


        <!--  -->

        <table style="width: 100%; margin-bottom: 0px" cellspacing="0" cellpadding="0">
            <tr>
                <td
                    style="padding: 3px; border: 1px solid #000; width: 50%; border-top: none; border-right: none; vertical-align: top;">
                    <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">

                        <tr>
                            <td style="padding-top: 5px;">Created By : {{ @$pb->createdBy->name }}</td>
                        </tr>

                        <tr>
                            <td style="padding-top: 5px;">Printed By : {{ @$user->name }}
                            </td>
                        </tr>
                    </table>

                </td>
                <td
                    style="padding: 3px; border: 1px solid #000; border-top: none; border-left: none; vertical-align: bottom;">
                    <table style="width: 100%; margin-bottom: 0px;" cellspacing="0" cellpadding="0">
                        <tr>
                            <td style="text-align: center; padding-bottom: 20px;">FOR
                                <b>{{ Str::ucfirst(@$organization->name) }}</b>
                            </td>
                        </tr>
                        <tr>
                            <td>This is a computer generated document hence not require any signature. </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2"
                    style=" border: 1px solid #000; padding: 5px; text-align: center; font-size: 12px; border-top: none; text-align: center;">
                    Regd. Office: {{ @$organizationAddress->getFullAddressAttribute() }} <br>
                </td>
                <!-- Principal Office to be added later -->
            </tr>
        </table>
</body>

</html>
