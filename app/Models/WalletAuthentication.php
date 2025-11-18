<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletAuthentication extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'nonce_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_primary' => 'boolean',
        'status' => 'boolean',
    ];

    /**
     * Get the user (polymorphic)
     */
    public function user()
    {
        return $this->morphTo(__FUNCTION__, 'user_type', 'user_id');
    }

    /**
     * Scope for verified wallets
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for active wallets
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for primary wallet
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Check if nonce is valid
     */
    public function isNonceValid()
    {
        return $this->nonce && 
               $this->nonce_expires_at && 
               $this->nonce_expires_at->isFuture();
    }

    /**
     * Generate new nonce
     */
    public function generateNonce()
    {
        $this->nonce = bin2hex(random_bytes(16));
        $this->nonce_expires_at = now()->addMinutes(15);
        $this->save();
        
        return $this->nonce;
    }
}
