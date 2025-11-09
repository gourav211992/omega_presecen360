<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Helpers\Helper;

class WipAccountRequest extends FormRequest
{

    public function rules()
    {

        return [
            'wip_accounts' => 'array', 
            'wip_accounts.*.group_id' => 'nullable|exists:organization_groups,id',
            'wip_accounts.*.company_id' => 'required|exists:organization_companies,id',
            'wip_accounts.*.organization_id' => 'required|exists:organizations,id',
            'wip_accounts.*.sub_category_id' => 'nullable|exists:erp_categories,id',
            'wip_accounts.*.item_id' => 'nullable|array',
            'wip_accounts.*.type' => 'nullable|in:consumption,wip',
            'wip_accounts.*.ledger_group_id' => 'required|exists:erp_groups,id',
            'wip_accounts.*.ledger_id' => 'required|exists:erp_ledgers,id',
            'wip_accounts.*.id' => 'nullable',
            'wip_accounts.*.book_id' => 'nullable|exists:erp_books,id',
        ];
    }

  public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $wipAccounts = $this->input('wip_accounts', []); 
            $existingCombinations = []; 
            $usedCombinations = [];
            $usedItemCombinations = [];
            $usedBookCombinations = [];

            foreach ($wipAccounts as $index => $wipAccount) {
                $companyId = $wipAccount['company_id'] ?? null;
                $organizationId = $wipAccount['organization_id'] ?? null;
                $subCategoryId = $wipAccount['sub_category_id'] ?? null;
                $itemIds = $wipAccount['item_id'] ?? [];
                $ledgerId = $wipAccount['ledger_id'] ?? null;
                $ledgerGroupId = $wipAccount['ledger_group_id'] ?? null;
                $bookIds = $wipAccount['book_id'] ?? [];
                $type = $wipAccount['type'] ?? null; 

                $itemIds = is_array($itemIds) ? $itemIds : [$itemIds];
                $bookIds = is_array($bookIds) ? $bookIds : [$bookIds]; 
 
                $combinationKey = $companyId . '-' . $organizationId . '-' . $ledgerId . '-' . $ledgerGroupId . '-' . $subCategoryId . '-' . $type;

                if (empty($itemIds) && empty($bookIds)) {
                    if (isset($existingCombinations[$combinationKey])) {
                        $validator->errors()->add("wip_accounts.{$index}.company_id", 'These combination already exists.');
                    } else {
                        $existingCombinations[$combinationKey] = true;
                    }
                }

                if (!empty($itemIds)) {
                    foreach ($itemIds as $itemId) {
                        $itemCombinationKey = $combinationKey . '-' . $itemId;
                        if (isset($usedItemCombinations[$itemCombinationKey])) {
                            $validator->errors()->add("wip_accounts.{$index}.company_id", 'These combination already exists (item-wise).');
                        } else {
                            $usedItemCombinations[$itemCombinationKey] = true;
                        }
                    }
                }

                if (empty($bookIds)) {
                    if (isset($existingCombinations[$combinationKey])) {
                        $validator->errors()->add("wip_accounts.{$index}.company_id", 'These combination already exists.');
                    } else {
                        $existingCombinations[$combinationKey] = true;
                    }
                } 
                
                if ($bookIds) {
                    foreach ($bookIds as $bookId) {
                        $bookCombinationKey = $combinationKey . '-' . $bookId;
                        if (isset($usedBookCombinations[$bookCombinationKey])) {
                            $validator->errors()->add("wip_accounts.{$index}.company_id", "These combination already exists.");
                        } else {
                            $usedBookCombinations[$bookCombinationKey] = true;
                        }
                    }
                }
            }
        });
    }
    
    public function messages()
    {
        return [
            'wip_accounts.array' => 'The wip accounts must be an array.',
            'wip_accounts.*.group_id.exists' => 'The selected group does not exist.',
            'wip_accounts.*.company_id.required' => 'The company field is required.',
            'wip_accounts.*.company_id.exists' => 'The selected company does not exist.',
            'wip_accounts.*.organization_id.required' => 'The organization field is required.',
            'wip_accounts.*.organization_id.exists' => 'The selected organization does not exist.',
            'wip_accounts.*.ledger_group_id.exists' => 'The selected ledger group does not exist.',
            'wip_accounts.*.ledger_group_id.required' => 'The ledger group field is required.',
            'wip_accounts.*.ledger_id.required' => 'The ledger field is required.',
            'wip_accounts.*.ledger_id.exists' => 'The selected ledger does not exist.',
            'wip_accounts.*.book_id.exists' => 'The selected book does not exist.',
        ];
        
    }
}
