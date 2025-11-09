<!DOCTYPE html>
@use(App\Models\ErpPslipItem)
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Material Identification Tag</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 30px;">
  
 
@foreach ($bundle as $index => $pack)
@php
  $pitem=$pack->pslipItem;
  $pwMapping=$pitem?->mo_product?->pwoMapping;
  if($pwMapping&&$pwMapping->main_so_item>0){
        $pslipSoMap=ErpPslipItem::where('so_item_id', $pwMapping->so_item_id)
              ->get();
        $pitemDetails = '';

        foreach ($pslipSoMap as $key => $value) {
            $pitemDetails .= strtoupper(substr($value?->item_name, 0, 1))
                . ' - ' 
                . date('d-m-Y', strtotime($value->pslip->document_date))
                . "\n";
        }

  }else{
    $pitemDetails = strtoupper(substr($pitem?->item_name, 0, 1)) . ' - ' . date('d-m-Y', strtotime($pslip->document_date));
  }
  $batch = [];

  foreach ($pitem->consumptions as $consumption) {
      $stockBatch = $consumption->getStockBatch();
      if ($stockBatch) {
          $batch[] = $stockBatch->lot_number;
      }
  }

  // keep only unique values
  $batch = array_unique($batch);

  // join into comma-separated string
  $batchList = implode(',', $batch);
 
@endphp
  <div style="width: 600px; border: 2px solid #000; padding: 10px; position: relative; margin: 15px auto;display: flex; flex-wrap: nowrap; justify-content: space-between; align-items: flex-start; gap: 10px; box-sizing: border-box;">

    <!-- Left section -->
    <div style="width: 70%; float: left;">
      <h3 style="margin: 0; font-weight: bold;">{{ strtoupper($address?->address) }}</h3>
      <p style="margin: 2px 0; font-size: 13px;">MATERIAL IDENTIFICATION TAGS</p>

      <p style="margin: 4px 0; font-size: 13px;"><b>PART CODE:</b> {{ $pack->pslipItem?->item_code }}</p>
      <p style="margin: 4px 0; font-size: 13px;"><b>PART NAME:</b> {{ $pack->pslipItem?->item_name }}</p>
      <p style="margin: 4px 0; font-size: 13px;"><b>SUPPLIER:</b> {{ strtoupper($address?->address) }}</p>
      
      <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 5px;">
        <tr>
          <td><b>BILL QTY:</b> -</td>
          <td><b>PACK QTY:</b> {{ $pack->qty }}</td>
        </tr>
        <tr>
          <td><b>BILL NO.:</b> -</td>
          <td><b>DATE:</b>{{date('d-m-Y', strtotime($pslip->document_date))}} </td>
        </tr>
      </table>

      <p style="margin: 4px 0; font-size: 13px;"><b>MRN NO:</b> ___________________</p>
      <p style="margin: 4px 0; font-size: 13px;"><b>REMARKS:</b> ___________________</p>

      <table style="width: 100%; border: 1px solid #000; margin-top: 15px; border-collapse: collapse; text-align: center; font-size: 13px;">
        <tr>
          <td style="border: 1px solid #000; width: 50%; height: 30px;">SUPPLIER SIGN.</td>
          <td style="border: 1px solid #000; width: 50%;">
            <div>WB SIGN.</div>
            <div style="display: flex; justify-content: space-around; font-size: 12px; margin-top: 4px;">
              <span>STORE</span>
              <span>Q.A</span>
            </div>
          </td>
        </tr>
      </table>
    </div>

    <!-- Right section -->
    <div style="width: 28%; float: right; border-left: 1px solid #000; text-align: center;">
      <div style="margin-top: 10px;">
        <img 
          src="https://api.qrserver.com/v1/create-qr-code/?data={{ urlencode(
              '1. ' . $pack->pslipItem?->item_code . ',' .
              ' 2. ' . ($pack->pslipItem?->customer->company_name ?? 'N/A') . ',' .
              ' 3. ' . $batchList . ',' 
               . $pitemDetails 
          ) }}&size=120x120" alt="QR Code" style="width:120px; height:120px;">

      </div>

      <div style="margin-top: 10px; text-align: left; font-size: 12px; padding-left:5px; line-height: 1.4; word-wrap: break-word; overflow-wrap: break-word;">
        <div>1. {{ $pack->pslipItem?->item_code }},</div>
        <div>2. {{ $pack->pslipItem?->customer->company_name ?? 'N/A' }},</div>
        <div>3. {{ $batchList}}</div>
     <div>{!! nl2br(e($pitemDetails)) !!}</div>
     <div style="padding-left:5px;"><b>{{ $pack->bundle_no }}</b></div>
      </div>
    </div>

    <div style="clear: both;"></div>
  </div>

  {{-- ✅ Add page break after every 2 labels --}}
  @if (($index + 1) % 2 == 0)
    <div style="page-break-after: always;"></div>
  @endif
@endforeach

</body>
</html>
