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
        
        <div style="text-align: right; margin-bottom: 20px;">
            <a href="{{ route('finance.gstr.gstr-3b-pdf') }}" class="pdf-button" target="_blank">
                📄 Download PDF
            </a>
        </div>

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
        <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">3.1 Detail of Outward Supplies and inward supplies liable to reverse charges</h5>

         <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;">Nature of Supplies</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Total Taxable Value</td>
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
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">6</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(a) Outward taxable supplies (other than
                zero rated, nil rated and exempted)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bData['taxable_amt'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bData['igst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bData['cgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bData['sgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bData['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(b) Outward taxable supplies (zero rated)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bZeroRatedData['taxable_amt'] ?? 0, 2) }}</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bZeroRatedData['igst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bZeroRatedData['cgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bZeroRatedData['sgst'] ?? 0, 2) }}</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ number_format($gstr3bZeroRatedData['cess'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(c) Other outward supplies, (Nil rated,
exempted)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

             <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(d) Inward supplies (liable to reverse charge)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">7328553.00</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">52121.45</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">157160.60</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">157160.60</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(d) Inward supplies (liable to reverse charge)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
            </tr>

        </table>

        <h5 style="font-weight: bold; font-size: 16px; margin: 0px; padding-top: 15px; padding-bottom: 15px;">
            3.2 Of the supplies shown in 3.1(a) above, details of inter-state supplies made to unregistered persons,
composition taxable persons and UIN holders</h5>
          <table style="width: 100%;" cellspacing="0" cellpadding="0">
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Place of supply (state/UT)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Total Taxable Value</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center;">Amount of Integrated Tax</td>
            </tr>
            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; font-weight: 600; text-align: center; border-top: none;">1</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">2</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">3</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-left: none; font-weight: 600; text-align: center; border-top: none;">4</td>
                
            </tr>
            @if(isset($gstr3bInterStateData) && $gstr3bInterStateData->count() > 0)
                @foreach($gstr3bInterStateData as $stateData)
                <tr>
                    <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(a) Supplies made to Unregistered Persons</td>
                    <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">{{ strtoupper($stateData->place_of_supply) }}(STATE)</td>
                    <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($stateData->taxable_amt ?? 0, 2) }}</td>
                     <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">{{ number_format($stateData->igst ?? 0, 2) }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(a) Supplies made to Unregistered Persons</td>
                    <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;">-</td>
                    <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                     <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">0.00</td>
                </tr>
            @endif

           
            
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
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">467824.72</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>

            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(2) Imports of services</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>

            </tr>

              <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">(3) Inward supplies liable to reverse charge(Other
                than 1 & 2 above)</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">52121.45</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">156225.00</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">156225.00</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
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
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">30982738.21</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">2654766.00</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">2654766.00</td>
                 <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>
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
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">From a supplier under composition scheme,Exempt and Nil rated supplyt</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;"></td>

            </tr>

            <tr>
                <td  style="padding: 7px 7px; border: 1px solid #000; border-top: none;">Non GST supply</td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none;"></td>
                <td style="padding: 7px 7px; border: 1px solid #000; border-top: none; border-left: none; text-align: right;">153040.00</td>

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