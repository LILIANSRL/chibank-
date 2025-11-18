<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MultiSigTransaction extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'transaction_data' => 'object',
        'initiated_at' => 'datetime',
        'executed_at' => 'datetime',
    ];

    /**
     * Get the wallet this transaction belongs to
     */
    public function wallet()
    {
        return $this->belongsTo(MultiSigWallet::class, 'multi_sig_wallet_id');
    }

    /**
     * Get the initiator (polymorphic)
     */
    public function initiator()
    {
        return $this->morphTo(__FUNCTION__, 'initiator_type', 'initiated_by');
    }

    /**
     * Get all approvals for this transaction
     */
    public function approvals()
    {
        return $this->hasMany(TransactionApproval::class);
    }

    /**
     * Get approved approvals
     */
    public function approvedApprovals()
    {
        return $this->hasMany(TransactionApproval::class)->where('action', 'approve');
    }

    /**
     * Get rejected approvals
     */
    public function rejectedApprovals()
    {
        return $this->hasMany(TransactionApproval::class)->where('action', 'reject');
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for approved transactions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope for executed transactions
     */
    public function scopeExecuted($query)
    {
        return $query->where('status', 'executed');
    }

    /**
     * Scope for rejected transactions
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Check if transaction has enough approvals
     */
    public function hasEnoughApprovals()
    {
        return $this->current_approvals >= $this->required_approvals;
    }

    /**
     * Check if user has already approved
     */
    public function hasUserApproved($userId, $userType = 'user')
    {
        return $this->approvals()
            ->where('signer_id', $userId)
            ->where('signer_type', $userType)
            ->exists();
    }

    /**
     * Check if transaction can be executed
     */
    public function canBeExecuted()
    {
        return $this->status === 'approved' && 
               $this->hasEnoughApprovals() && 
               is_null($this->executed_at);
    }
}
