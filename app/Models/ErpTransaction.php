<?php
namespace App\Models;

use App\Helpers\ConstantHelper;
use App\Traits\DefaultGroupCompanyOrg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class ErpTransaction extends Model
{
    use DefaultGroupCompanyOrg;
    protected $table = 'erp_transactions'; // View name in the database

    protected $primaryKey = 'document_id';
    public $incrementing = false; 
    public $timestamps = false; 

    protected $guarded = []; 
    // Define relationships if necessary
    public function book()
    {
        return $this -> belongsTo(Book::class, 'book_id');
    }
    public function bookLevel()
    {
        return $this -> belongsTo(BookLevel::class,'book_id');
    }
    public function documentApproval()
    {
        return $this->hasMany(DocumentApproval::class, 'document_id', 'document_id')
            ->where('document_type', $this->document_type)
            ->where('revision_number', $this->revision_number);
    }
    public function getDocumentStatusAttribute()
    {
        if ($this->attributes['document_status'] == ConstantHelper::APPROVAL_NOT_REQUIRED) {
            return ConstantHelper::APPROVED;
        }
        return $this->attributes['document_status'];
    }
    public function getDisplayStatusAttribute()
    {
        $status = str_replace('_', ' ', $this->document_status);
        return ucwords($status);
    }
    public function party(): ?MorphTo
    {
        if (empty($this->party_type) || empty($this->party_id)) {
            return null;
        }

        // Normalize type to lowercase before mapping
        $normalizedType = strtolower($this->party_type);

        Relation::morphMap([
            'vendor' => \App\Models\Vendor::class,
            'customer' => \App\Models\Customer::class,
            'items' => \App\Models\Item::class,
            'department' => \App\Models\Department::class,
            'user' => \App\Models\AuthUser::class,
            'voucher' => \App\Models\Voucher::class,
            'ledger' => \App\Models\Ledger::class,
        ]);

        // Dynamically set normalized type so morphTo works
        $this->party_type = $normalizedType;

        return $this->morphTo(__FUNCTION__, 'party_type', 'party_id');
    }

    public function getPartyDisplayName(): ?string
    {
        // Return null if not linked
        if (empty($this->party_type) || empty($this->party_id)) {
            return null;
        }

        // Ensure relationship is loaded or fetched
        $party = $this->party;

        if (!$party) {
            return null;
        }

        switch (strtolower($this->party_type)) {
            case 'vendor':
                return $party->display_name ?? null;
                
            case 'customer':
                return $party->display_name ?? null;

            case 'item':
                return $party->item_name ?? null;

            case 'department':
                return $party->name ?? null;

            case 'user':
                return $party->name ?? null;

            case 'voucher':
                return $party->voucher_no ?? null;

            case 'ledger':
                return $party->name ?? null;

            default:
                return null;
        }
    }


}
