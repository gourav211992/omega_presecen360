<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\Helper;
use App\Models\NumberPattern;

class MaintWoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Base rules that are always required
        $rules = [
            'book_id' => 'required',
            'document_date' => 'required|date',
            'document_status' => 'required|string',
            'location_id' => 'required|integer',
        ];

        /**
         * ✅ Document Number Validation (copied from MaintBOMRequest)
         * Check for both draft and submit cases
         */
        if ($this->filled('book_id')) {
            $user = Helper::getAuthenticatedUser();
            $numPattern = NumberPattern::where('organization_id', $user->organization_id)
                ->where('book_id', $this->book_id)
                ->orderBy('id', 'DESC')
                ->first();

            if ($numPattern && $numPattern?->series_numbering == 'Manually') {
                $maintWoId = $this->route('maint_wo');
                if ($maintWoId) {
                    // For updates - exclude current record
                    $rules['document_number'] = 'required|unique:erp_plant_maint_wo,document_number,' . $maintWoId;
                } else {
                    // For create - check uniqueness (both draft and submit)
                    $rules['document_number'] = 'required|unique:erp_plant_maint_wo,document_number';
                }
            } else {
                // Auto series - document number is required
                $rules['document_number'] = 'required';
            }
        }

        // Additional validation for non-draft documents
        if ($this->input('document_status') !== 'draft') {
            $rules['reference_type'] = 'required|string';
        }

        // File validation
        if ($this->hasFile('upload_file')) {
            $rules['upload_file.*'] = 'file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120'; // 5MB max
        }

        if ($this->hasFile('supporting_documents')) {
            $rules['supporting_documents.*'] = 'file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120'; // 5MB max
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_number.unique' => 'Document number already exists. Please use a different document number.',
            'document_number.required' => 'Document number is required.',
            'book_id.required' => 'Series is required.',
            'document_date.required' => 'Document date is required.',
            'document_status.required' => 'Document status is required.',
            'location_id.required' => 'Location is required.',
            'location_id.integer' => 'Location must be a valid selection.',
            'reference_type.required' => 'Reference type is required.',
            'upload_file.*.mimes' => 'Upload file must be a file of type: pdf, docx, jpg, jpeg, png, xls, xlsx.',
            'upload_file.*.max' => 'Upload file may not be greater than 5MB.',
            'supporting_documents.*.mimes' => 'Supporting document must be a file of type: pdf, docx, jpg, jpeg, png, xls, xlsx.',
            'supporting_documents.*.max' => 'Supporting document may not be greater than 5MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'book_id' => 'series',
            'document_number' => 'document number',
            'document_date' => 'document date',
            'document_status' => 'document status',
            'location_id' => 'location',
            'reference_type' => 'reference type',
            'upload_file' => 'uploaded file',
            'supporting_documents' => 'supporting documents',
        ];
    }
}
