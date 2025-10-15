<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\NumberPattern;
use App\Helpers\Helper;

class MaintBOMRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isEdit = in_array($this->method(), ['PUT', 'PATCH']);
        $isDraft = $this->input('document_status') === 'draft';
        $isManual = $this->input('doc_number_type') === 'Manually';

        $rules = [
            'book_code' => $isEdit ? 'nullable|string' : ($isDraft ? 'nullable|string' : 'required|string'),
            'doc_number_type' => $isEdit ? 'nullable|string' : ($isDraft ? 'nullable|string' : 'required|string'),
            'doc_prefix' => 'nullable|string',
            'doc_suffix' => 'nullable|string',
            'doc_no' => ($isEdit || $isDraft || $isManual) ? 'nullable|integer' : 'required|integer',
            'document_status' => 'required|string',
            'book_id' => $isEdit ? 'nullable|integer' : ($isDraft ? 'nullable|integer' : 'required|integer'),
            'document_date' => $isEdit ? 'nullable|date' : ($isDraft ? 'nullable|date' : 'required|date'),
            'document.*' => 'nullable|file|mimes:png,jpeg,jpg,xls,xlsx,docx,pdf|max:5120',
            'spare_parts' => 'nullable|string',
            'item' => 'nullable|array',
            'uom' => 'nullable', 
            'qty' => 'nullable|array',
            'remarks' => 'nullable|string',
        ];

        /**
         * ✅ Document Number Validation (copied from PoRequest)
         */
        if ($this->filled('book_id')) {
            $user = Helper::getAuthenticatedUser();
            $numPattern = NumberPattern::where('organization_id', $user->organization_id)
                ->where('book_id', $this->book_id)
                ->orderBy('id', 'DESC')
                ->first();

            if ($numPattern && $numPattern?->series_numbering == 'Manually') {
                $bomId = $this->route('id');
                if ($bomId) {
                    $rules['document_number'] = 'required|unique:erp_plant_maint_bom,document_number,' . $bomId;
                } else {
                    $rules['document_number'] = 'required|unique:erp_plant_maint_bom,document_number';
                }
            } else {
                $rules['document_number'] = 'required';
            }
        }

        return $rules;
    }

   

    public function attributes()
    {
        return [
            'book_code' => 'Book Code',
            'doc_number_type' => 'Document Number Type',
            'doc_prefix' => 'Document Prefix',
            'doc_suffix' => 'Document Suffix',
            'doc_no' => 'Document Number',
            'document_status' => 'Document Status',
            'book_id' => 'Book ID',
            'document_date' => 'Document Date',
            'bom_name' => 'Bom Name',
            'document' => 'Document',
            'document_number' => 'Document Number',
        ];
    }

    public function messages()
    {
        return [
            'document_number.required' => 'The Document Number field is required.',
            'document_number.unique' => 'The Document Number has already been taken.',
            'book_id.required' => 'The Book ID field is required.',
            'book_id.integer' => 'The Book ID must be an integer.',
            'document_date.required' => 'The Document Date field is required.',
            'document_date.date' => 'The Document Date must be a valid date.',
        ];
    }
}
