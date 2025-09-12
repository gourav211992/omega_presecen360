@extends('layouts.app')

@section('content')
<!-- BEGIN: Content-->
<div class="app-content content ">
    <div class="content-overlay"></div>
    <div class="header-navbar-shadow"></div>
    <div class="content-wrapper container-xxl p-0">

        <div class="content-body">

            <section id="basic-datatable">
                <div class="card border  overflow-hidden">
                <div class="row">
                    <div class="col-md-12 bg-light border-bottom mb-1 po-reportfileterBox">
                        <div class="row pofilterhead action-button align-items-center">
                            <div class="col-md-4">
                                <h3></h3>
                                <p></p>
                            </div>
                           
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card">

                            <div class="table-responsive">
                                <table class="datatables-basic table tableistlastcolumnfixed myrequesttablecbox ">
                                    <thead>
                                        <tr>
                                            <th>Sr. No</th>
                                            <th>Furbook Code</th>
                                            <th>Location</th>
                                            <th>Organization Name</th>
                                            <th>Currency Code</th>
                                            <th>Cost Center</th>
                                            <th>Debit Amount</th>
                                            <th>Credit Amount</th>
                                            <th>Remark</th>
                                            <th>Final Remark</th>
                                            <th>Amount</th>
                                            <th>Document Date</th>
                                           
                                            <th>Portal Remark</th>
                                             <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        
                                        @foreach ($data as $index=>$item)
                                            <tr>
                                                <td>{{ $index+1 }}</td>
                                                <td class="fw-bolder text-dark text-nowrap">{{ $item->furbooks_code}}</td>
                                                <td class="text-nowrap">{{ $item->location->name }}</td>
                                                <td class="text-nowrap">{{ $item?->organization?->name }}</td>
                                                <td class="text-nowrap">{{ $item->currency_code }}</td>
                                                <td class="text-nowrap">{{ $item->cost_center }}</td>
                                                <td class="text-nowrap">{{ $item->debit_amount}}</td>
                                                <td class="text-nowrap">{{ $item->credit_amount}}</td>
                                                <td class="text-nowrap">{{ $item->remark }}</td>
                                                <td class="text-nowrap">{{ $item->final_remark }}</td>
                                                <td class="text-nowrap">{{ $item->amount }}</td>
                                                <td class="text-nowrap">{{ date('d-m-y',strtotime($item->document_date)) }}</td>
                                                
                                                <td class="text-nowrap">{{ $item->remarks }}</td>
                                                <td class="text-nowrap">{{ $item->status }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                {{-- {{ $data->links('vendor.pagination.custom') }} --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </section>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script type="text/javascript" src="{{asset('assets/js/modules/finance-table.js')}}"></script>
<script>
     

    $(document).ready(function () {
       
       $('.datatables-basic').DataTable({
        processing: true,  // Show processing indicator
        scrollX: true,
        serverSide: false, // Disable server-side processing since data is already loaded
        drawCallback: function() {
            feather.replace(); // Re-initialize feather icons if needed (for custom icons like edit)
        },
        order: [[0, 'asc']], // Default ordering by the first column (Date)
        dom:
			'<"d-flex justify-content-between align-items-center mx-2 row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-3"f>>t<"d-flex justify-content-between mx-2 row"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
			lengthMenu: [7, 10, 25, 50, 75, 100], // Options for number of rows to show
            buttons:
                [{
                    init: function (api, node, config)
                    {
                    }
                }],
        columnDefs: [
            // { "orderable": false, "targets": [8] }  // Disable sorting on the action column
        ],
        language: {
            paginate: {
                previous: '&nbsp;',
                next: '&nbsp;'
            }
        }
    });
          handleRowSelection('.datatables-basic');

    // Optionally, you can add some custom logic or event listeners here
});


</script>


@endsection
