<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shipping Label</title>
</head>
<body>
    @php
        $organizationAddWork=$organizationAddress->firstWhere('type', 'work');
        $origin=$organizationAddWork?->country;
        $city=$organizationAddWork?->city;
        // $district=$organizationAddWork?->district;
        $state=$organizationAddWork?->state;
    @endphp
    @foreach ($pslipItems as $pItems)
        
    @php
      $customer=$pItems->customer;
      $category=$pItems->item->categoryWithoutScope;
      $mo=$pItems->mo_product->mo;
      $package=$packRaw[$pItems->id];
      @endphp
    @foreach ($package as $key=>$pack)
    
    @php
      $package=$pItems->item->packagingDetails->firstWhere('id', $pack['package']);
      if(!$package){
          $packege_no=isset($pack->packet_no)?$pack->packet_no:1;

          $length_in_feet  = $pItems?->item?->length_in_feet  ?? 0;
          $breadth_in_feet = $pItems?->item?->breadth_in_feet ?? 0;
          $height_in_feet  = $pItems?->item?->height_in_feet  ?? 0;
          $weight_in_kg=$pItems?->item?->storage_weight??0;
      }else{
          $packege_no=$package->packet_no;
          $length_in_feet=$package->length_in_feet??0;
          $breadth_in_feet=$package->breadth_in_feet??0;
          $height_in_feet=$package->height_in_feet??0;
          $weight_in_kg=$package->weight_in_kg??0;
      }
    @endphp
   
    @for ($i = 0; $i < $pack['qty']; $i++)

      <div style="width:700px; font-size: 11px; font-family:Arial; height:100%">

        <table style="width:100%; border-collapse:collapse; border:1px solid #000; font-size:13px;">
          <thead>
            <tr>
              <th colspan="2" style="text-align:center; font-weight:bold; background-color:#f3f3f3; border:1px solid #000; padding:8px;">
                SHIPPING LABEL
              </th>
            </tr>
          </thead>
          
          <tbody>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px; width:35%;">Item Name</td><td style="border:1px solid #000; padding:6px;">{{$pItems->item_name}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">SKU Code</td><td style="border:1px solid #000; padding:6px;">{{$pItems->item_code}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Box No.</td><td style="border:1px solid #000; padding:6px;">{{$packege_no}} / {{$pItems->item->storage_uom_count}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Item Type</td><td style="border:1px solid #000; padding:6px;">{{$category?->name}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Net Quantity</td><td style="border:1px solid #000; padding:6px;">1</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">MRP (incl. of all taxes)</td><td style="border:1px solid #000; padding:6px;">{{$pItems?->item?->mrp}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Packed MM/YYYY</td><td style="border:1px solid #000; padding:6px;">{{ \Carbon\Carbon::parse($pslip->document_date)->format('M-Y') }}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Country of origin</td><td style="border:1px solid #000; padding:6px;">{{strtoupper($origin?->name)}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Box Weight (Kg)</td><td style="border:1px solid #000; padding:6px;">{{$weight_in_kg}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">MO Number</td><td style="border:1px solid #000; padding:6px;">{{$mo->book_code}} - {{$mo->document_number}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Lot Number</td><td style="border:1px solid #000; padding:6px;">{{$pslip->lot_number}}</td></tr>
            <tr><td style="font-weight:bold; border:1px solid #000; padding:6px;">Box Dimension (L×B×H)mm</td><td style="border:1px solid #000; padding:6px;">{{$length_in_feet}}×{{$breadth_in_feet}}×{{$height_in_feet}}</td></tr>

            <tr>
              <td style="font-weight:bold; border:1px solid #000; padding:6px;">Manufactured by</td>
              <td style="border:1px solid #000; padding:6px; font-size:12px; line-height:1.3;">
              
                <strong>{{$organizationAddWork?->name}}</strong><br>
                {{$organizationAddWork->line_1}}</br>
                {{$organizationAddWork?->line_2}}</br>
                {{$city?->name}},{{$state?->name}},{{$organizationAddWork?->postal_code}}

              </td>
            </tr>

            <tr>
              <td style="font-weight:bold; border:1px solid #000; padding:6px;">Marketed by</td>
              <td style="border:1px solid #000; padding:6px; font-size:12px; line-height:1.3;">
               @php
                      $organizationAdd=$organizationAddress->firstWhere('type',null);
                      $origin=$organizationAdd?->country;
                      $city=$organizationAdd?->city;
                      $state=$organizationAdd?->state;
                @endphp
                {{$organizationAdd?->line_1}}</br>
                {{$organizationAdd?->line_2}}</br>
                {{$city?->name}},{{$state?->name}},{{$organizationAdd?->postal_code}}

              </td>
            </tr>

            <tr>
              <td style="font-weight:bold; border:1px solid #000; padding:6px;">Customer Service</td>
              <td style="border:1px solid #000; padding:6px; font-size:12px; line-height:1.3;">
                {{$organizationAdd?->line_1}}</br>
                {{$organizationAdd?->line_2}}</br>
                {{$city?->name}},{{$state?->name}},{{$organizationAdd?->postal_code}}

              </td>
            </tr>

            <tr>
              <td style="font-weight:bold; border:1px solid #000; padding:6px;">QA Approved Seal</td>
              <td style="border:1px dashed #000; padding:6px; text-align:center; height:80px;">&nbsp;</td>
            </tr>
          </tbody>
        </table>

      </div>
    @endfor
       
    @endforeach
    @endforeach
</body>
</html>
