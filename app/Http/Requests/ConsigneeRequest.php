<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\Helper;

class ConsigneeRequest extends FormRequest
{
    protected $organization_id;
    protected $group_id;
    protected $company_id;

    /**
     * Authorize request
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare data before validation
     */
    protected function prepareForValidation()
    {
        $user = Helper::getAuthenticatedUser();
        $organization = $user->organization;

        $this->organization_id = $organization ? $organization->id : null;
        $this->group_id = $organization ? $organization->group_id : null;
        $this->company_id = $organization ? $organization->company_id : null;
    }
    /**
     * Validation rules
     */
  public function rules(): array
    {
        $consigneeId = $this->route('id'); 

        $uniqueCodeRule = Rule::unique('erp_consignees', 'consignee_code')
            ->ignore($consigneeId)
            ->whereNull('deleted_at');

        if ($this->organization_id !== null) {
            $orgId = $this->organization_id;
            $uniqueCodeRule->where(function ($query) use ($orgId) {
                $query->where('organization_id', $orgId)
                    ->orWhereNull('organization_id');
            });
        }

        if ($this->group_id !== null) {
            $groupId = $this->group_id;
            $uniqueCodeRule->where('group_id', $groupId);
        }

        if ($this->company_id !== null) {
            $companyId = $this->company_id;
            $uniqueCodeRule->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        return [
            'organization_id' => 'nullable|exists:organizations,id',
            'group_id'        => 'nullable|exists:organization_groups,id',
            'company_id'      => 'nullable|exists:organization_companies,id',
            'consignee_name'  => 'required|string|max:255',
            'consignee_code'  => ['required', 'string', 'max:20', $uniqueCodeRule],
            'is_customer'     => 'nullable|boolean',
            'is_vendor'       => 'nullable|boolean',
            'email'           => 'nullable|email|max:100',
            'phone'           => ['nullable', 'regex:/^[0-9+\-\s]{7,15}$/', 'max:15'],
            'mobile'          => ['nullable', 'regex:/^[0-9]{10}$/', 'size:10'],
            'status'          => 'nullable|string|in:active,inactive',

            // Address validation
            'addresses' => 'nullable|array',
            'addresses.*.id' => 'nullable',
            'addresses.*.country_id' => 'required|exists:mysql_master.countries,id',
            'addresses.*.state_id' => 'required|exists:mysql_master.states,id',
            'addresses.*.city_id' => 'required|exists:mysql_master.cities,id',
            'addresses.*.type' => 'nullable|string|max:255',
            'addresses.*.pincode' => 'required|string|max:10',
            'addresses.*.pincode_master_id' => 'nullable|exists:mysql_master.erp_pincode_masters,id',
            'addresses.*.phone' => 'nullable|string|regex:/^\d{10,12}$/',
            'addresses.*.address' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'organization_id.exists' => 'Selected organization does not exist.',
            'group_id.exists'        => 'Selected group does not exist.',
            'company_id.exists'      => 'Selected company does not exist.',

            // Consignee fields
            'consignee_name.required' => 'Consignee name is required.',
            'consignee_name.string'   => 'Consignee name must be valid text.',
            'consignee_name.max'      => 'Consignee name must not exceed 255 characters.',

            'consignee_code.required' => 'Consignee code is required.',
            'consignee_code.string'   => 'Consignee code must be valid text.',
            'consignee_code.max'      => 'Consignee code must not exceed 20 characters.',
            'consignee_code.unique'   => 'This consignee code already exists. Please choose another.',

            'is_customer.boolean' => 'Invalid value for "Is Customer".',
            'is_vendor.boolean'   => 'Invalid value for "Is Vendor".',

            'email.email' => 'Please provide a valid email address.',
            'email.max'   => 'Email must not exceed 100 characters.',

            'phone.regex' => 'Phone number must contain only numbers, spaces, + or - and be 7 to 15 characters.',
            'phone.max'   => 'Phone number must not exceed 15 characters.',

            'mobile.regex' => 'Mobile number must be exactly 10 digits.',
            'mobile.size'  => 'Mobile number must be exactly 10 digits.',

            'status.string' => 'Status must be valid text.',
            'status.in'     => 'Status must be either "active" or "inactive".',

            // Addresses array
            'addresses.array' => 'Addresses must be an array.',

            // Individual address fields
            'addresses.*.id' => 'Invalid address ID.',

            'addresses.*.country_id.required' => 'Country is required.',
            'addresses.*.country_id.exists'   => 'The selected country is invalid.',

            'addresses.*.state_id.required' => 'State is required.',
            'addresses.*.state_id.exists'   => 'The selected state is invalid.',

            'addresses.*.city_id.required' => 'City is required.',
            'addresses.*.city_id.exists'   => 'The selected city is invalid.',

            'addresses.*.type.string' => 'Address type must be valid text.',
            'addresses.*.type.max'    => 'Address type must not exceed 255 characters.',

            'addresses.*.pincode.required' => 'Pincode is required.',
            'addresses.*.pincode.string'   => 'Pincode must be valid text.',
            'addresses.*.pincode.max'      => 'Pincode must not exceed 10 characters.',

            'addresses.*.pincode_master_id.exists' => 'Invalid pincode selected.',

            'addresses.*.phone.regex' => 'Address phone number must be between 10 and 12 digits.',

            'addresses.*.address.string' => 'Address must be valid text.',
            'addresses.*.address.max'    => 'Address must not exceed 255 characters.',
        ];
   }

}
