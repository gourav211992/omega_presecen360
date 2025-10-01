<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ErpExternalIntegrationRequest extends FormRequest
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
  public function rules()
{
    $integrationId = $this->route('id');

    return [
        // 'trip_book_id'          => 'required',
        // 'so_book_id'            => 'required',
        'organization_id'       => 'required',
        'store_id'              => 'required',
        'customer_id'           => 'required',
        'status'                => 'required',
    ];
}

}
