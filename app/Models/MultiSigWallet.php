<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultiSigWallet extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'wallet_data' => 'object',
        'status' => 'boolean',
    ];

    /**
     * Get the owner of the wallet (polymorphic)
     */
    public function owner()
    {
        return $this->morphTo(__FUNCTION__, 'owner_type', 'owner_id');
    }

    /**
     * Get all signers for this wallet
     */
    public function signers()
    {
        return $this->hasMany(WalletSigner::class);
    }

    /**
     * Get active signers
     */
    public function activeSigners()
    {
        return $this->hasMany(WalletSigner::class)->where('status', true);
    }

    /**
     * Get wallet transactions
     */
    public function transactions()
    {
        return $this->hasMany(MultiSigTransaction::class);
    }

    /**
     * Get pending transactions
     */
    public function pendingTransactions()
    {
        return $this->hasMany(MultiSigTransaction::class)->where('status', 'pending');
    }

    /**
     * Scope for authenticated user's wallets
     */
    public function scopeAuth($query)
    {
        $guard = get_auth_guard();
        $user = auth()->guard($guard)->user();
        
        if (!$user) {
            return $query->where('id', 0); // Return empty result
        }

        $ownerType = match($guard) {
            'web' => 'user',
            'merchant' => 'merchant',
            'agent' => 'agent',
            'admin' => 'admin',
            default => 'user'
        };

        return $query->where('owner_id', $user->id)->where('owner_type', $ownerType);
    }

    /**
     * Scope for active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for specific blockchain
     */
    public function scopeBlockchain($query, $blockchain)
    {
        return $query->where('blockchain', $blockchain);
    }

    /**
     * Check if wallet is multi-signature
     */
    public function isMultiSig()
    {
        return $this->wallet_type === 'multi_sig' && $this->required_signatures > 1;
    }

    /**
     * Check if user is a signer
     */
    public function isSigner($userId, $userType = 'user')
    {
        return $this->signers()
            ->where('signer_id', $userId)
            ->where('signer_type', $userType)
            ->where('status', true)
            ->exists();
    }

    /**
     * Check if user is the owner
     */
    public function isOwner($userId, $userType = 'user')
    {
        return $this->owner_id == $userId && $this->owner_type == $userType;
    }

    /**
     * Get total signature weight
     */
    public function getTotalWeight()
    {
        return $this->activeSigners()->sum('weight');
    }
}
