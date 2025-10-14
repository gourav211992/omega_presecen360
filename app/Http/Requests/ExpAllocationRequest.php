<?php

namespace App\Http\Requests;

use Auth;

use App\Models\NumberPattern;
use App\Helpers\Helper;
use App\Helpers\ConstantHelper;
use Illuminate\Foundation\Http\FormRequest;

class ExpAllocationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    // public function authorize(): bool
    // {
    //     return false;
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        // $bomId = $this->route('id');
        $rules = [
            'book_id' => 'required',
            'document_number' => 'required|max:50', // Default rule for document_number
            'supplier_invoice_no' => 'nullable|max:50',
            'supplier_invoice_date' => 'nullable|date',
            'remarks' => 'nullable|max:500',
        ];

        // Check the condition only if book_id is present
        if ($this->filled('book_id')) {
            $user = Helper::getAuthenticatedUser();
            $numPattern = NumberPattern::where('organization_id', $user->organization_id)
                ->where('book_id', $this->book_id)
                ->orderBy('id', 'DESC')
                ->first();

            // Update document_number rule based on the condition
            if ($numPattern && $numPattern->series_numbering == 'Manually') {
                $rules['document_number'] = 'required|unique:erp_mrn_headers,document_number';
            }
        }

        $rules['component_item_name.*'] = 'required';
        $rules['components.po.*.po_qty'] = 'required|numeric|min:1';
        $rules['components.po.*.po_rate'] = 'required|numeric|min:1';
        $rules['components.po.*.po_value'] = 'required|numeric|min:1';
        $rules['components.grn.*.grn_qty'] = 'required|numeric|min:1';
        $rules['components.grn.*.grn_value'] = 'required|numeric|min:1';
        $rules['components.grn.*.grn_weight'] = 'nullable|numeric|min:0';
        $rules['components.grn.*.grn_volume'] = 'nullable|numeric|min:0';
        $rules['components.grn.*.allocation_cost'] = 'required|numeric|min:1';
        $rules['components.grn.*.landed_cost'] = 'required|numeric|min:1';

        return $rules;
    }

    public function messages(): array
    {
        return [
            'book_id.required' => 'The series is required.',
            'supplier_invoice_no.required' => 'Supplier Invoice No is required.',
            'supplier_invoice_date.required' => 'Supplier Invoice Date is required.',
            'remarks.required' => 'Remark is required.',
            'component_item_name.*.required' => 'Required',
            'components.po.*.po_qty.required' => 'Po Qty is required',
            'components.po.*.po_rate.required' => 'Po Rate is required',
            'components.po.*.po_value.required' => 'Po Value is required',
            'components.po.*.po_qty.numeric' => 'Po Qty must be integer',
            'components.po.*.po_rate.numeric' => 'Po Rate must be integer',
            'components.po.*.po_value.numeric' => 'Po Value must be integer',
            'components.grn.*.attr_group_id.*.attr_name.required' => 'Select Attribute',
            'components.grn.*.grn_qty.required' => 'Grn Qty is required',
            'components.grn.*.grn_value.required' => 'Grn Value is required',
            'components.grn.*.grn_weight.required' => 'Grn Weight is required',
            'components.grn.*.grn_volume.required' => 'Grn Volume is required',
            'components.grn.*.allocation_cost.required' => 'Allocation Cost is required',
            'components.grn.*.landed_cost.required' => 'Landed Cost is required',
            'components.grn.*.grn_qty.numeric' => 'Grn Qty must be integer',
            'components.grn.*.grn_value.numeric' => 'Grn Value must be integer',
            'components.grn.*.grn_weight.numeric' => 'Grn Weight must be integer',
            'components.grn.*.grn_volume.numeric' => 'Grn Volume must be integer',
            'components.grn.*.allocation_cost.numeric' => 'Allocation Cost must be integer',
            'components.grn.*.landed_cost.numeric' => 'Landed Cost must be integer',
        ];

    }
}
