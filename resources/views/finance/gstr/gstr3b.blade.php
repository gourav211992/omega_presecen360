<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSTR-3B Report</title>
    <style>
        .pdf-button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .pdf-button:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }
        @media print {
            .pdf-button {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div style="width:700px; font-family:Arial; font-size: 13px;">
        
       

         <table style="width: 100%; margin-top: 20px;"  cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 75%; text-align: center; font-weight: bold; font-size: 18px;">Form GSTR-3B</td>
                <td>Year :</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $financialYear }}</td>
            </tr>
          
            <tr>
                <td style="width: 75%; text-align: center; font-weight: bold; font-size: 18px;">[ See Rule 61(5)]</td>
                <td>Month :</td>
                <td style="border: 1px solid #000; border-top: none; text-align: center;">{{ $previousMonth }}</td>
            </tr>
         </table>

    

        <table style="width: 100%; margin-top: 14px;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000;">1 GSTIN</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none;">{{ $gstin }}</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">2 Legal name of the registered person</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ $organizationName }}</td>
            </tr>
        </table>
        <!-- New Section 3.1 -->
        <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">3.1 Details of Outward supplies and inward supplies liable to reverse charge (other than those covered by Table 3.1.1)</h5>

        <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Nature of Supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Total taxable value</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Integrated tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Central tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">State/UT tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Cess</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(a) Outward taxable supplies (other than zero rated, nil rated and exempted)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['b2b']['taxable_amt'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['b2b']['igst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['b2b']['cgst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['b2b']['sgst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['b2b']['cess'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(b) Outward taxable supplies (zero rated)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['zero_rated']['taxable_amt'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['zero_rated']['igst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['zero_rated']['cess'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(c) Other outward supplies (nil rated, exempted)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['nil_exempted']['taxable_amt'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(d) Inward supplies (liable to reverse charge)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['taxable_amt'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['igst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cgst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['sgst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cess'], 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(e) Non-GST outward supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['non_gst']['taxable_amt'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
            </tr>
        </table>

        <!-- New Section 3.1.1 -->
        <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">3.1.1 Details of Supplies notified under section 9(5) of the CGST Act, 2017 and corresponding provisions in IGST/UTGST/SGST Acts</h5>

        <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Nature of Supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Total taxable value</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Integrated tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Central tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">State/UT tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Cess</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(i) Taxable supplies on which electronic commerce operator pays tax u/s 9(5) [to be furnished by electronic commerce operator]</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(ii) Taxable supplies made by registered person through electronic commerce operator, on which electronic commerce operator is required to pay tax u/s 9(5) [to be furnished by registered person making supplies through electronic commerce operator]</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">-</td>
            </tr>
        </table>

        <!-- New Section 3.2 -->
        <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">3.2 Out of supplies made in 3.1 (a) and 3.1.1 (i), details of inter-state supplies made</h5>

        <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Nature of Supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Total taxable value</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Integrated tax</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Supplies made to Unregistered Persons</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_2Data['unregistered']['taxable_value'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_2Data['unregistered']['igst'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Supplies made to Composition Taxable Persons</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_2Data['composition']['taxable_value'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_2Data['composition']['igst'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Supplies made to UIN holders</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_2Data['uin']['taxable_value'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_2Data['uin']['igst'] ?? 0, 2) }}</td>
            </tr>
        </table>


         <table style="width: 100%; margin-top: 20px;"  cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 75%; text-align: center; font-weight: bold; font-size: 18px;">Form GSTR-3B</td>
                <td>Year :</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $financialYear }}</td>
            </tr>
          
            <tr>
                <td style="width: 75%; text-align: center; font-weight: bold; font-size: 18px;">[ See Rule 61(5)]</td>
                <td>Month :</td>
                <td style="border: 1px solid #000; border-top: none; text-align: center;">{{ $previousMonth }}</td>
            </tr>
         </table>

           

         <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">4 Eligible ITC</h5>

          <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Details</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Integrated Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Centeral Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">State/UT Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Cess</td>

            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center; border-top: none;">1</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">2</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">3</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">4</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">5</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(A) ITC Available (whether in full or part)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>

            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(1) Imports of goods</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_goods']['igst'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_goods']['cgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_goods']['sgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_goods']['cess'] ?? 0, 2) }}</td>

            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(2) Imports of services</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_services']['igst'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_services']['cgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_services']['sgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['import_services']['cess'] ?? 0, 2) }}</td>

            </tr>

              <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(3) Inward supplies liable to reverse charge(Other
                than 1 & 2 above)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['igst'], 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cgst'], 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['sgst'], 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection3_1Data['reverse_charge']['cess'], 2) }}</td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(4) Inward supplies from ISD
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(5) All other ITC
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['all_other_itc']['igst'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['all_other_itc']['cgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['all_other_itc']['sgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($gstr3bSection4Data['all_other_itc']['cess'] ?? 0, 2) }}</td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(B) ITC Reversed
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(1) As per rules 42 & 43 of CGST Rules
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(2) Others
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(C) Net ITC Available (A)-(B)
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"> 31502684.38</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">2810991.00</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">2810991.00</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(D) Ineligible ITC
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(1) As per section 17(5)
                </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
            </tr>

        </table>

         <table style="width: 100%; margin-top: 20px;"  cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 75%; text-align: center; font-weight: bold; font-size: 18px;">Form GSTR-3B</td>
                <td>Year :</td>
                <td style="border: 1px solid #000; text-align: center;">{{ $financialYear }}</td>
            </tr>
          
            <tr>
                <td style="width: 75%; text-align: center; font-weight: bold; font-size: 18px;">[ See Rule 61(5)]</td>
                <td>Month :</td>
                <td style="border: 1px solid #000; border-top: none; text-align: center;">{{ $previousMonth }}</td>
            </tr>
         </table>

         

          <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">5 Values of exempt, nil-rated and non-GST inward supplies</h5>

          <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Nature of Supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Inter-State Supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Intra-State Supplies</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center; border-top: none;">1</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">2</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">3</td>
                
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">From a supplier under composition scheme,Exempt and Nil rated supply</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ $gstr3bSection5Data['composition_exempt_nil']['inter_state'] ?? '0.00' }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ $gstr3bSection5Data['composition_exempt_nil']['intra_state'] ?? '0.00' }}</td>

            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Non GST supply</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ $gstr3bSection5Data['non_gst']['inter_state'] ?? '0.00' }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ $gstr3bSection5Data['non_gst']['intra_state'] ?? '0.00' }}</td>

            </tr>



        </table>


        <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">
        5.1 Interest and Late fee for previous tax period
    </h5>

    <table style="width: 100%;" cellspacing="0" cellpadding="0">
        <tr>
            <td style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Details</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Integrated tax</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Central tax</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">State/UT tax</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Cess</td>
        </tr>

        <tr>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">System computed Interest</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">-</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">-</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">-</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">-</td>
        </tr>

        <tr>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Interest Paid</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
        </tr>

        <tr>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Late fee</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">-</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
            <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: center;">-</td>
        </tr>
    </table>


          <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">6.1 Payment of Tax</h5>

          <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td rowspan="2" style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Description</td>
                <td rowspan="2" style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Tax Payable</td>
                <td colspan="4" style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Paid through ITC</td>
                <td  rowspan="2" style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Tax Paid
                TDS/TCS</td>

                <td  rowspan="2" style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Tax/Cess
                Paid in</td>

                <td  rowspan="2" style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Interest</td>
                <td  rowspan="2" style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Late Fee</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center; border-top: none; border-left: none;">Integrated Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">Central Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">State/UT tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">Cess</td> 
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center; border-top: none;">1</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">2</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">3</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">4</td> 
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">5</td> 
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">6</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">7</td> 
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">8</td> 
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">9</td> 
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">10</td> 
            </tr>


            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Integrated Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">25966875.12</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

              <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Central Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">10943114.16</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">State/UT Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">10943114.16</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Cess</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">10943114.16</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Reverse
Charge</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">52121.45</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">52121.45</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Integrated Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Reverse
Charge</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">157160.60</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">157160.60</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Central Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Reverse
Charge</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">157160.60 </td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">157160.60</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">State/UT Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Reverse
Charge Cess</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

        
        </table>

          <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">6.2 TDS/TCS Credit</h5>

           <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Deatils</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Integrated Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Centeral Tax</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">State/UT Tax</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center; border-top: none;">1</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">2</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">3</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">4</td>

                
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">TDS</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>


            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">TCS</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>

            </tr>
        </table>

        <h6 style="font-weight: 600; font-size: 18px; margin: 0px; padding-top: 10px;">Verification (By Authorised Signatory)</h6>
        <p style="margin: 0px; padding-top: 5px; font-size: 16px; line-height: 20px; ">I hereby solemnly affirm and declare that the information given herein above is true and correct to the best of my knowledge and
beleif and nothing has been concealed there from.</p>

<h6 style="font-weight: 600; font-size: 16px; margin: 0px; padding-top: 10px;">INSTRUCTIONS :</h6>
 <p style="margin: 0px; padding-top: 5px; font-size: 14px; line-height: 18px;">
    1) Value of Taxable Supplies = Value of invoices + Value of Debit Notes - Value of Crebit Notes + Value of advances recieved for which
invoices have not been issued in the same month - Value of advances adjusted against invoices.

 </p>

     <p style="margin: 0px; padding-top: 8px; font-size: 14px; line-height: 18px;">
     2) Details of advances as well as adjustment of same against invoices to be adjusted and not shown seperately. </p>
    <p style="margin: 0px; padding-top: 8px; font-size: 14px;line-height: 18px; ">3) Amendment in any details to be adjusted and not shown seperately.</p>



    </div>

   

   

     
    
</body>
</html>