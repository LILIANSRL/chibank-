<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletSigner extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_owner' => 'boolean',
        'can_initiate' => 'boolean',
        'can_approve' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Get the wallet this signer belongs to
     */
    public function wallet()
    {
        return $this->belongsTo(MultiSigWallet::class, 'multi_sig_wallet_id');
    }

    /**
     * Get the signer (polymorphic)
     */
    public function signer()
    {
        return $this->morphTo(__FUNCTION__, 'signer_type', 'signer_id');
    }

    /**
     * Scope for active signers
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for signers who can initiate transactions
     */
    public function scopeCanInitiate($query)
    {
        return $query->where('can_initiate', true)->where('status', true);
    }

    /**
     * Scope for signers who can approve transactions
     */
    public function scopeCanApprove($query)
    {
        return $query->where('can_approve', true)->where('status', true);
    }
}
