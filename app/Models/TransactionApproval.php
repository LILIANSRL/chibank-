<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionApproval extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the transaction this approval belongs to
     */
    public function transaction()
    {
        return $this->belongsTo(MultiSigTransaction::class, 'multi_sig_transaction_id');
    }

    /**
     * Get the signer who made this approval (polymorphic)
     */
    public function signer()
    {
        return $this->morphTo(__FUNCTION__, 'signer_type', 'signer_id');
    }

    /**
     * Scope for approvals
     */
    public function scopeApproved($query)
    {
        return $query->where('action', 'approve');
    }

    /**
     * Scope for rejections
     */
    public function scopeRejected($query)
    {
        return $query->where('action', 'reject');
    }
}
