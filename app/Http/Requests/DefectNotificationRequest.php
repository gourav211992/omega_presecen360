<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\Helper;
use App\Models\NumberPattern;

class DefectNotificationRequest extends FormRequest
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
        // Base rules that are always required (from the image: Series, Doc No, Doc Date, Location)
        $rules = [
            'book_id' => 'required',
            'document_date' => 'required|date',
            'location_id' => 'required',
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
                $defectNotificationId = $this->route('defect_notification');
                if ($defectNotificationId) {
                    // For updates - exclude current record
                    $rules['document_number'] = 'required|unique:erp_defect_notifications,document_number,' . $defectNotificationId;
                } else {
                    // For create - check uniqueness (both draft and submit)
                    $rules['document_number'] = 'required|unique:erp_defect_notifications,document_number';
                }
            } else {
                // Auto series - document number is required
                $rules['document_number'] = 'required';
            }
        }

        // Add file validation if files are uploaded
        if ($this->hasFile('attachment')) {
            $rules['attachment.*'] = 'file|mimes:pdf,docx,jpg,jpeg,png,xls,xlsx|max:5120'; // 5MB max per file
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
            'location_id.required' => 'Location is required.',
            'attachment.*.mimes' => 'Attachment must be a file of type: pdf, docx, jpg, jpeg, png, xls, xlsx.',
            'attachment.*.max' => 'Attachment may not be greater than 5MB.',
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
            'location_id' => 'location',
        ];
    }
}
