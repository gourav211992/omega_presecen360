<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransportDetailRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'trip_number' => ['required', 'string', 'max:50', 'exists:erp_trip_plan_headers,document_number'],
            'organization_id' => ['required', 'integer', 'exists:erp_trip_plan_headers,organization_id'],
            'transport_mode' => ['required'],
            'vehicle_number' => ['required', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z]{1,2}[0-9]{4}$/'],
            'transporter_name' => ['required', 'string', 'max:199'],
            'champ_name' => ['required', 'string', 'max:199'],
            'driver_name' => ['required', 'string', 'max:199'],
        ];
    }

    public function attributes(): array
    {
        return [
            'trip_number'       => 'trip Number',
            'organization_id'   => 'Organization',
        ];
    }
}
