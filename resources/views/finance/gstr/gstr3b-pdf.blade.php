<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>GSTR-3B Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 15px;
            line-height: 1.3;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border: 2px solid #000;
            padding: 15px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .header-info {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            text-align: left;
        }
        
        .header-info div {
            flex: 1;
        }
        
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        
        .info-table td {
            padding: 8px;
            border: 1px solid #000;
            font-weight: bold;
        }
        
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        .main-table th,
        .main-table td {
            padding: 6px 4px;
            border: 1px solid #000;
            text-align: center;
            font-size: 10px;
        }
        
        .main-table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-left {
            text-align: left !important;
        }
        
        .text-right {
            text-align: right !important;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 12px;
            margin: 15px 0 8px 0;
            background-color: #f5f5f5;
            padding: 5px;
            border: 1px solid #000;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .verification-section {
            margin-top: 30px;
            border: 1px solid #000;
            padding: 15px;
        }
        
        .instructions {
            margin-top: 20px;
            font-size: 10px;
            border: 1px solid #000;
            padding: 10px;
        }
        
        .row-number {
            font-weight: bold;
            width: 30px;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Form GSTR-3B</h1>
        <h2>[ See Rule 61(5)]</h2>
    </div>

    <table class="main-table">
    <tr>
        <td class="text-left">GSTIN of the supplier</td>
        <td class="text-right">{{$gstin}}</td>
    </tr>
    <tr>
        <td class="text-left">2(a). Legal name of the registered person</td>
        <td class="text-right">{{$organizationName}}</td>
    </tr>
    <tr>
        <td class="text-left">2(b). Trade name, if any</td>
        <td class="text-right">{{$organizationName}}</td>
    </tr>
    <tr>
        <td class="text-left">2(c). ARN</td>
        <td class="text-right"></td>
    </tr>
    <tr>
        <td class="text-left">2(d). Date of ARN</td>
        <td class="text-right"></td>
    </tr>
    </table>


    <!-- New Section 3.1 -->
    <div class="section-title">3.1 Details of Outward supplies and inward supplies liable to reverse charge (other than those covered by Table 3.1.1)</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 30%;">Nature of Supplies</th>
                <th style="width: 15%;">Total taxable value</th>
                <th style="width: 12%;">Integrated tax</th>
                <th style="width: 12%;">Central tax</th>
                <th style="width: 12%;">State/UT tax</th>
                <th style="width: 12%;">Cess</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">(a) Outward taxable supplies (other than zero rated, nil rated and exempted)</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['b2b']['taxable_amt'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['b2b']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['b2b']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['b2b']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['b2b']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(b) Outward taxable supplies (zero rated)</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['zero_rated']['taxable_amt'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['zero_rated']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['zero_rated']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(c) Other outward supplies (nil rated, exempted)</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['nil_exempted']['taxable_amt'] ?? 0, 2) }}</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
            </tr>
            <tr>
                <td class="text-left">(d) Inward supplies (liable to reverse charge)</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['taxable_amt'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(e) Non-GST outward supplies</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['non_gst']['taxable_amt'] ?? 0, 2) }}</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
            </tr>
        </tbody>
    </table>

    <!-- New Section 3.1.1 -->
    <div class="section-title">3.1.1 Details of Supplies notified under section 9(5) of the CGST Act, 2017 and corresponding provisions in IGST/UTGST/SGST Acts</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 30%;">Nature of Supplies</th>
                <th style="width: 15%;">Total taxable value</th>
                <th style="width: 12%;">Integrated tax</th>
                <th style="width: 12%;">Central tax</th>
                <th style="width: 12%;">State/UT tax</th>
                <th style="width: 12%;">Cess</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">(i) Taxable supplies on which electronic commerce operator pays tax u/s 9(5) [to be furnished by electronic commerce operator]</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr>
                <td class="text-left">(ii) Taxable supplies made by registered person through electronic commerce operator, on which electronic commerce operator is required to pay tax u/s 9(5) [to be furnished by registered person making supplies through electronic commerce operator]</td>
                <td class="text-right">0.00</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
            </tr>
        </tbody>
    </table>

    <!-- New Section 3.2 -->
    <div class="section-title">3.2 Out of supplies made in 3.1 (a) and 3.1.1 (i), details of inter-state supplies made</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 40%;">Nature of Supplies</th>
                <th style="width: 30%;">Total taxable value</th>
                <th style="width: 30%;">Integrated tax</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">Supplies made to Unregistered Persons</td>
                <td class="text-right">{{ number_format($gstr3bSection3_2Data['unregistered']['taxable_value'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_2Data['unregistered']['igst'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">Supplies made to Composition Taxable Persons</td>
                <td class="text-right">{{ number_format($gstr3bSection3_2Data['composition']['taxable_value'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_2Data['composition']['igst'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">Supplies made to UIN holders</td>
                <td class="text-right">{{ number_format($gstr3bSection3_2Data['uin']['taxable_value'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_2Data['uin']['igst'] ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table><br><br><br> <br><br><br><br><br><br>


    <div class="section-title">4 Eligible ITC</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 40%;">Details</th>
                <th style="width: 15%;">Integrated Tax</th>
                <th style="width: 15%;">Central Tax</th>
                <th style="width: 15%;">State/UT Tax</th>
                <th style="width: 15%;">Cess</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">(A) ITC Available (whether in full or part)</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">(1) Import of goods</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_goods']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_goods']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_goods']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_goods']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(2) Import of services</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_services']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_services']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_services']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['import_services']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(3) Inward supplies liable to reverse charge (other than 1 & 2 above)</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(4) Inward supplies from ISD</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">(5) All other ITC</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['all_other_itc']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['all_other_itc']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['all_other_itc']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($gstr3bSection4Data['all_other_itc']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(B) ITC Reversed</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr>
                <td class="text-left">(1) As per rules 42 & 43 of CGST Rules</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr>
                <td class="text-left">(2) Others</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr>
                <td class="text-left">(C) Net ITC Available (A)-(B)</td>
                <td class="text-right">{{ number_format($getGstr3bSection4PartC['final_totals']['igst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($getGstr3bSection4PartC['final_totals']['cgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($getGstr3bSection4PartC['final_totals']['sgst'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($getGstr3bSection4PartC['final_totals']['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="text-left">(D) Ineligible ITC</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr>
                <td class="text-left">(1) As per section 17(5)</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">5 Values of exempt, nil-rated and non-GST inward supplies</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th class="row-number"></th>
                <th style="width: 45%;">Nature of Supplies</th>
                <th style="width: 25%;">Inter-State Supplies</th>
                <th style="width: 25%;">Intra-State Supplies</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="row-number">1</td>
                <td class="text-left">From a supplier under composition scheme, Exempt and Nil rated supply</td>
                <td class="text-right">{{ $gstr3bSection5Data['composition_exempt_nil']['inter_state'] ?? '0.00' }}</td>
                <td class="text-right">{{ $gstr3bSection5Data['composition_exempt_nil']['intra_state'] ?? '0.00' }}</td>
            </tr>
            <tr>
                <td class="row-number">2</td>
                <td class="text-left">Non GST supply</td>
                <td class="text-right">{{ $gstr3bSection5Data['non_gst']['inter_state'] ?? '0.00' }}</td>
                <td class="text-right">{{ $gstr3bSection5Data['non_gst']['intra_state'] ?? '0.00' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">6.1 Payment of Tax</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 15%;">Description</th>
                <th rowspan="2" style="width: 10%;">Tax Payable</th>
                <th colspan="4" style="width: 40%;">Paid through ITC</th>
                <th rowspan="2" style="width: 8%;">Tax Paid TDS/TCS</th>
                <th rowspan="2" style="width: 8%;">Tax/Cess Paid in</th>
                <th rowspan="2" style="width: 8%;">Interest</th>
                <th rowspan="2" style="width: 8%;">Late Fee</th>
            </tr>
            <tr>
                <th style="width: 10%;">Integrated Tax</th>
                <th style="width: 10%;">Central Tax</th>
                <th style="width: 10%;">State/UT Tax</th>
                <th style="width: 10%;">Cess</th>
            </tr>
            <tr>
                <th>1</th>
                <th>2</th>
                <th>3</th>
                <th>4</th>
                <th>5</th>
                <th>6</th>
                <th>7</th>
                <th>8</th>
                <th>9</th>
                <th>10</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-left">Integrated Tax</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Central Tax</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">State/UT Tax</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Cess</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Reverse Charge</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Integrated Tax</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Reverse Charge</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Central Tax</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Reverse Charge</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">State/UT Tax</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
            <tr>
                <td class="text-left">Reverse Charge Cess</td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
                <td class="text-right"></td>
            </tr>
        </tbody>
    </table>

    <div class="section-title">6.2 TDS/TCS Credit</div>
    
    <table class="main-table">
        <thead>
            <tr>
                <th class="row-number"></th>
                <th style="width: 40%;">Details</th>
                <th style="width: 18%;">Integrated Tax</th>
                <th style="width: 18%;">Central Tax</th>
                <th style="width: 18%;">State/UT Tax</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="row-number">1</td>
                <td class="text-left">TDS</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
            <tr>
                <td class="row-number">2</td>
                <td class="text-left">TCS</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
                <td class="text-right">0.00</td>
            </tr>
        </tbody>
    </table>

    <div class="verification-section">
        <div class="section-title" style="margin: 0 0 15px 0; background: none; border: none; padding: 0;">Verification (By Authorised Signatory)</div>
        <p style="text-align: justify; line-height: 1.5;">
            I hereby solemnly affirm and declare that the information given herein above is true and correct to the best of my knowledge and belief and nothing has been concealed there from.
        </p>
        <div style="margin-top: 30px;">
            <div style="float: left; width: 50%;">
                <p><strong>Place:</strong> _______________</p>
            </div>
            <div style="float: right; width: 50%; text-align: right;">
                <p><strong>Date:</strong> {{ date('d-m-Y') }}</p>
            </div>
            <div style="clear: both;"></div>
            <div style="margin-top: 40px; text-align: right;">
                <p>_________________________</p>
                <p><strong>Signature of Authorised Signatory</strong></p>
            </div>
        </div>
    </div>

    <div class="instructions">
        <div class="section-title" style="margin: 0 0 10px 0; background: none; border: none; padding: 0;">INSTRUCTIONS:</div>
        <p><strong>1)</strong> Value of Taxable Supplies = Value of invoices + Value of Debit Notes - Value of Credit Notes + Value of advances received for which invoices have not been issued in the same month - Value of advances adjusted against invoices.</p>
        <p><strong>2)</strong> Details of advances as well as adjustment of same against invoices to be adjusted and not shown separately.</p>
        <p><strong>3)</strong> Amendment in any details to be adjusted and not shown separately.</p>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 10px; border-top: 1px solid #000; padding-top: 10px;">
        <p><strong>Generated on:</strong> {{ date('d-m-Y H:i:s') }}</p>
        <p>This is a system generated report</p>
    </div>
</body>
</html>
