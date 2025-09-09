<div class="modal fade" id="summaryExpenModal" tabindex="-1" aria-labelledby="shareProjectTitle" aria-hidden="true">
    <div class="modal-dialog  modal-dialog-centered" style="max-width: 700px">
        <div class="modal-content">
            <div class="modal-header p-0 bg-transparent">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-sm-2 mx-50 pb-2">
                <h1 class="text-center mb-1" id="shareProjectTitle">Expenses</h1>
                <div class="text-end"></div>
                <table class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail"> 
                    <thead>
                        <tr>
                            <td>#</td>
                            <td>
                                <input type="text" id="new_exp_name" class="form-control mw-100" />
                            </td>
                            <td>
                                <input step="any" type="number" id="new_exp_perc" class="form-control mw-100" />
                            </td>
                            <td>
                                <input step="any" type="number" id="new_exp_value" class="form-control mw-100" />
                            </td>
                            <td>
                                <a href="javascript:;" id="add_new_head_exp" class="text-primary can_hide">
                                    <i data-feather="plus-square"></i>
                                </a>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <div class="table-responsive-md customernewsection-form">
                    <table id="summaryExpTable" class="mt-1 table myrequesttablecbox table-striped po-order-detail custnewpo-detail"> 
                        <thead>
                            <tr>
                                <th>S.No</th>
                                <th width="150px">Expense Name</th>
                                <th>Expense %</th>
                                <th>Expense Value</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($mrn->expenses) && $mrn->expenses->count())
                                @foreach($mrn->expenses as $expKey => $headerExpense)
                                    <tr class="display_summary_exp_row">
                                        <td>{{$expKey + 1}}</td>
                                        <td>{{$headerExpense->ted_name}}
                                            <input type="hidden" name="exp_summary[{{$expKey + 1}}][e_id]" value="{{$headerExpense->id}}">
                                            <input type="hidden" name="exp_summary[{{$expKey + 1}}][e_name]" value="{{$headerExpense->ted_name}}">
                                        </td>
                                        <td>{{floatval($headerExpense->ted_percentage) ?? 0}}
                                            <input type="hidden" name="exp_summary[{{$expKey + 1}}][e_perc]" value="{{floatval($headerExpense->ted_percentage) ?? ''}}"/>
                                        </td>
                                        <td>{{floatval($headerExpense->ted_amount) ?? ''}}
                                            <input type="hidden" name="exp_summary[{{$expKey + 1}}][e_amnt]" value="{{floatval($headerExpense->ted_amount) ?? ''}}" /></td>
                                        <td>
                                            <a href="javascript:;" class="text-danger deleteExpRow">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                            <tr id="expSummaryFooter">
                                <td colspan="2"></td>
                                <td class="text-dark"><strong>Total</strong></td>
                                <td class="text-dark text-end"><strong id="total" amount="{{@$mrn->expense_amount}}">{{number_format(@$mrn->expense_amount,2)}}</strong></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer justify-content-center">  
                <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary me-1">Cancel</button> 
                <button type="button" class="btn btn-primary summaryExpSubmit">Submit</button>
            </div>
        </div>
    </div>
</div>