<table class="datatables-basic table myrequesttablecbox">
    <thead>
        <tr>
            <th>#</th>
            <th>HSN</th>
            <th>Description</th>
            <th>UQC</th>
            <th>Total Quantity</th>
            <th>Taxable Value</th>
            <th>Integrated Tax Amount</th>
            <th>Central Tax Amount</th>
            <th>State/UT Tax Amount</th>
            <th>Cess Amount</th>
            <th>Rate</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($gstrData as $key => $item)
            <?php
            $taxableAmt = $item->taxable_amt ? $item->taxable_amt : 0;
            $igst = $item->igst ? $item->igst : 0;
            $cgst = $item->cgst ? $item->cgst : 0;
            $sgst = $item->sgst ? $item->sgst : 0;
            $cess = $item->cess ? $item->cess : 0;
            $totalValue = $taxableAmt + $igst + $cgst + $sgst + $cess;
            ?>
            <tr class="trail-bal-tabl-none">
                <td>{{ $gstrData->firstItem() + $key }}</td>
                <td>{{ $item->hsn_code }}</td>
                <td>{{ $item->description ? $item->description : '-' }}</td>
                <td>{{ $item->uqc ? $item->uqc : '-' }}</td>
                <td>{{ $item->qty ? $item->qty : '-' }}</td>
                <td>{{ $totalValue ? number_format($totalValue, 2) : '-' }}</td>
                <td>{{ $igst ? number_format($igst, 2) : 0 }}</td>
                <td>{{ $cgst ? number_format($cgst, 2) : 0 }}</td>
                <td>{{ $sgst ? number_format($sgst, 2) : 0 }}</td>
                <td>{{ $cess ? number_format($cess, 2) : 0 }}</td>
                <td>{{ $item->rate ? $item->rate . '%' : 0 }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center text-danger">No record(s) found</td>
            </tr>
        @endforelse
    </tbody>
</table>
