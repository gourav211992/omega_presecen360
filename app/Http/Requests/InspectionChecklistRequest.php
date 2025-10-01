<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\ConstantHelper;
use App\Helpers\Helper;

class InspectionChecklistRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    protected $organization_id;
    protected $company_id;
    protected $group_id;


    protected function prepareForValidation()
    {
        $user = Helper::getAuthenticatedUser();
        $organization = $user->organization;
        $this->organization_id = $organization ? $organization->id : null;
        $this->group_id = $organization ? $organization->group_id : null;
        $this->company_id = $organization ? $organization->company_id : null;
    }

    public function rules()
    {
        $inspectionChecklistId = $this->route('id');
        $uniqueRule = Rule::unique('erp_inspection_checklists')
            ->ignore($inspectionChecklistId)
            ->whereNull('deleted_at');

        if ($this->group_id !== null) {
            $uniqueRule->where('group_id', $this->group_id);
        }

        if ($this->company_id !== null) {
            $companyId = $this->company_id;
            $uniqueRule->where(function ($query) use ($companyId) {
                $query->where('company_id', $companyId)
                      ->orWhereNull('company_id');
            });
        }

        if ($this->organization_id !== null) {
            $orgId = $this->organization_id;
            $uniqueRule->where(function ($query) use ($orgId) {
                $query->where('organization_id', $orgId)
                      ->orWhereNull('organization_id');
            });
        }
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                $uniqueRule,
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'type' => [
                'nullable',
                'string',
            ],
            'status' => [
                'required',
                Rule::in(ConstantHelper::STATUS),
            ],
            'group_id' => 'nullable|exists:groups,id',
            'company_id' => 'nullable|exists:companies,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'checklist_details' => [
                'nullable',
                'array',
            ],
            'checklist_details.*.id' => 'nullable|exists:erp_inspection_checklist_details,id',
            'checklist_details.*.name' => 'nullable|string|max:255',
            'checklist_details.*.data_type' => 'nullable|string|max:255',
            'checklist_details.*.value' => 'nullable|string|max:255',
            'checklist_details.*.description' => 'nullable|string',
            'checklist_details.*.mandatory' => 'nullable|in:true,false,1,0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The name is required.',
            'name.string' => 'The name must be a string.',
            'name.max' => 'The name may not exceed 255 characters.',
            'name.unique' => 'The name has already been taken.',
            'description.string' => 'The description must be a string.',
            'status.required' => 'The status field is required.',
            'status.in' => 'The selected status is invalid.',
            'group_id.exists' => 'The selected group is invalid.',
            'company_id.exists' => 'The selected company is invalid.',
            'organization_id.exists' => 'The selected organization is invalid.',
            'checklist_details.array' => 'The checklist details must be an array.',
            'checklist_details.*.name.string' => 'Each checklist detail name must be a string.',
            'checklist_details.*.name.max' => 'Each checklist detail name may not exceed 255 characters.',
            'checklist_details.*.data_type.string' => 'Each checklist detail data type must be a string.',
            'checklist_details.*.data_type.max' => 'Each checklist detail data type may not exceed 255 characters.',
            'checklist_details.*.description.string' => 'Each checklist detail description must be a string.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $details = collect($this->input('checklist_details'));
            foreach ($details as $index => $item) {
                if (!isset($item['name']) || trim($item['name']) === '') {
                    $validator->errors()->add("checklist_details.$index.name", "The name field is required.");
                }

                // Check if data_type is 'list' and mandatory is checked, then value must not be empty
                if (isset($item['data_type']) && $item['data_type'] === 'list' && 
                    isset($item['mandatory']) && ($item['mandatory'] == '1' || $item['mandatory'] == 1)) {
                    if (!isset($item['value']) || trim($item['value']) === '') {
                        $validator->errors()->add("checklist_details.$index.value", "List type with mandatory field must contain at least one item.");
                    }
                }

                // Validate value format based on data type
                if (isset($item['data_type']) && isset($item['value']) && !empty(trim($item['value']))) {
                    $value = trim($item['value']);
                    
                    switch ($item['data_type']) {
                        case 'number':
                            if (!is_numeric($value)) {
                                $validator->errors()->add("checklist_details.$index.value", "Value field must contain a valid number for Number data type.");
                            }
                            break;
                        case 'date':
                            $date = date_parse($value);
                            if ($date['error_count'] > 0 || !checkdate($date['month'], $date['day'], $date['year'])) {
                                $validator->errors()->add("checklist_details.$index.value", "Value field must contain a valid date for Date data type.");
                            }
                            break;
                        case 'boolean':
                            $lowerValue = strtolower($value);
                            if (!in_array($lowerValue, ['true', 'false', '1', '0', 'yes', 'no'])) {
                                $validator->errors()->add("checklist_details.$index.value", "Value field must be a valid boolean (true/false, yes/no, 1/0) for Boolean data type.");
                            }
                            break;
                        case 'text':
                            // Text accepts any value, no specific validation needed
                            break;
                        case 'list':
                            // List validation already handled above
                            break;
                    }
                }
            }

            $names = $details->pluck('name')->filter()
                ->map(fn($v) => strtolower(trim($v)));

            $duplicates = $names->duplicates();

            if ($duplicates->isNotEmpty()) {
                foreach ($names as $index => $value) {
                    if ($duplicates->contains($value)) {
                        $validator->errors()->add("checklist_details.$index.name", "The name '$value' is duplicated.");
                    }
                }
            }
        });
    }
}
