<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Account Model
 * 
 * Represents a Chart of Accounts entry with hierarchical structure.
 */
class Account extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'parent_id',
        'level',
        'is_postable',
        'requires_subsidiary',
        'is_active',
        'is_bank_account',
        'bank_name',
        'bank_account_number',
        'currency_code',
        'description',
    ];

    protected $casts = [
        'is_postable' => 'boolean',
        'requires_subsidiary' => 'boolean',
        'is_active' => 'boolean',
        'is_bank_account' => 'boolean',
        'level' => 'integer',
    ];

    // Account Types
    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';
    public const TYPE_EQUITY = 'equity';
    public const TYPE_REVENUE = 'revenue';
    public const TYPE_EXPENSE = 'expense';

    // Normal Balance
    public const BALANCE_DEBIT = 'debit';
    public const BALANCE_CREDIT = 'credit';

    /**
     * Get the parent account.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get all child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get all descendants recursively (with eager loading control).
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all journal entry lines for this account.
     */
    public function journalEntryLines(): HasMany
    {
        return $this->hasMany(\App\Models\JournalEntryLine::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePostable($query)
    {
        return $query->where('is_postable', true);
    }

    public function scopeHeaders($query)
    {
        return $query->where('is_postable', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSearchable($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%");
        });
    }

    /**
     * Business Logic Methods
     */

    /**
     * Check if account has any children.
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Check if account can be deleted.
     * 
     * Cannot delete if:
     * - Has children
     * - Has journal entry lines (to be implemented in ACC-3)
     * - Has balance (to be validated in service layer)
     */
    public function canBeDeleted(): bool
    {
        // Check children
        if ($this->hasChildren()) {
            return false;
        }

        // TODO: Check journal entry lines when JE table exists
        // if ($this->journalEntryLines()->exists()) {
        //     return false;
        // }

        return true;
    }

    /**
     * Check if is_postable can be changed to false.
     * 
     * Cannot change if:
     * - Has children (must be header)
     * - Has journal entries (data integrity)
     */
    public function canChangeToNonPostable(): bool
    {
        // TODO: Check journal entries when JE table exists
        // if ($this->journalEntryLines()->exists()) {
        //     return false;
        // }

        return true;
    }

    /**
     * Get full account path (e.g., "Assets > Current Assets > Cash").
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get code with hierarchy indicator.
     */
    public function getDisplayCodeAttribute(): string
    {
        $indent = str_repeat('  ', $this->level - 1);
        return $indent . $this->code;
    }

    /**
     * Get tree structure for a collection of accounts.
     * 
     * @param \Illuminate\Database\Eloquent\Collection $accounts
     * @param int|null $parentId
     * @return array
     */
    public static function buildTree($accounts, $parentId = null): array
    {
        $branch = [];

        foreach ($accounts as $account) {
            if ($account->parent_id == $parentId) {
                $children = self::buildTree($accounts, $account->id);

                $item = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'normal_balance' => $account->normal_balance,
                    'level' => $account->level,
                    'is_postable' => $account->is_postable,
                    'is_active' => $account->is_active,
                    'parent_id' => $account->parent_id,
                ];

                if (!empty($children)) {
                    $item['children'] = $children;
                }

                $branch[] = $item;
            }
        }

        return $branch;
    }
}
