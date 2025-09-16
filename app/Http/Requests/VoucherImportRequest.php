<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VoucherImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'book_type_id' => 'required',
            'book_id' => 'required',
            'date' => 'required|date',
            'location' => 'required',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'book_type_id.required' => 'Voucher type is required.',
            'book_id.required' => 'Series is required.',
            'date.required' => 'Date is required.',
            'location.required' => 'Location is required.',
        ];
    }
}
