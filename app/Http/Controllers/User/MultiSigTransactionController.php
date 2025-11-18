<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MultiSigWallet;
use App\Models\MultiSigTransaction;
use App\Models\TransactionApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class MultiSigTransactionController extends Controller
{
    /**
     * Show form to create new transaction
     */
    public function create($walletId)
    {
        $wallet = MultiSigWallet::auth()->with('activeSigners')->findOrFail($walletId);

        // Check if user can initiate transactions
        if (!$wallet->isSigner(auth()->id(), 'user')) {
            return back()->withErrors(['error' => 'You are not authorized to initiate transactions for this wallet']);
        }

        $page_title = "New Transaction: " . $wallet->name;

        return view('user.sections.multi-sig-wallet.transaction-create', compact('page_title', 'wallet'));
    }

    /**
     * Store a new multi-sig transaction
     */
    public function store(Request $request, $walletId)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($walletId);

        $validator = Validator::make($request->all(), [
            'to_address' => 'required|string',
            'amount' => 'required|numeric|min:0.00000001',
            'transaction_type' => 'required|in:send,internal_transfer,contract_call',
            'fee' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Check if user can initiate transactions
        $signer = $wallet->signers()
            ->where('signer_id', auth()->id())
            ->where('signer_type', 'user')
            ->where('can_initiate', true)
            ->first();

        if (!$signer) {
            return back()->withErrors(['error' => 'You are not authorized to initiate transactions']);
        }

        // Check wallet balance
        if ($wallet->balance < ($request->amount + ($request->fee ?? 0))) {
            return back()->withErrors(['error' => 'Insufficient wallet balance'])->withInput();
        }

        DB::beginTransaction();
        try {
            $trxId = 'MSIG' . time() . rand(1000, 9999);

            $transaction = MultiSigTransaction::create([
                'multi_sig_wallet_id' => $wallet->id,
                'transaction_type' => $request->transaction_type,
                'trx_id' => $trxId,
                'from_address' => $wallet->address ?? 'pending',
                'to_address' => $request->to_address,
                'amount' => $request->amount,
                'currency' => $wallet->currency_code,
                'fee' => $request->fee ?? 0,
                'transaction_data' => $request->only(['memo', 'gas_limit', 'gas_price']),
                'required_approvals' => $wallet->required_signatures,
                'current_approvals' => 0,
                'status' => 'pending',
                'initiated_by' => auth()->id(),
                'initiator_type' => 'user',
                'initiated_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('user.multi.sig.transaction.show', ['walletId' => $walletId, 'id' => $transaction->id])
                ->with(['success' => ['Transaction created successfully and awaiting approvals']]);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create transaction: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Show transaction details
     */
    public function show($walletId, $id)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($walletId);
        $transaction = MultiSigTransaction::where('multi_sig_wallet_id', $walletId)
            ->with(['approvals', 'initiator'])
            ->findOrFail($id);

        $page_title = "Transaction Details: " . $transaction->trx_id;

        $userCanApprove = $wallet->signers()
            ->where('signer_id', auth()->id())
            ->where('signer_type', 'user')
            ->where('can_approve', true)
            ->exists();

        $hasUserApproved = $transaction->hasUserApproved(auth()->id(), 'user');

        return view('user.sections.multi-sig-wallet.transaction-details', compact(
            'page_title',
            'wallet',
            'transaction',
            'userCanApprove',
            'hasUserApproved'
        ));
    }

    /**
     * Approve a transaction
     */
    public function approve(Request $request, $walletId, $id)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($walletId);
        $transaction = MultiSigTransaction::where('multi_sig_wallet_id', $walletId)
            ->findOrFail($id);

        // Check if transaction is pending
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'Transaction is not pending approval']);
        }

        // Check if user can approve
        $signer = $wallet->signers()
            ->where('signer_id', auth()->id())
            ->where('signer_type', 'user')
            ->where('can_approve', true)
            ->first();

        if (!$signer) {
            return back()->withErrors(['error' => 'You are not authorized to approve transactions']);
        }

        // Check if user already approved
        if ($transaction->hasUserApproved(auth()->id(), 'user')) {
            return back()->withErrors(['error' => 'You have already approved this transaction']);
        }

        DB::beginTransaction();
        try {
            // Create approval record
            TransactionApproval::create([
                'multi_sig_transaction_id' => $transaction->id,
                'signer_id' => auth()->id(),
                'signer_type' => 'user',
                'action' => 'approve',
                'signature' => $request->signature ?? null,
                'comment' => $request->comment ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Update transaction approval count
            $transaction->increment('current_approvals');

            // Check if transaction has enough approvals
            if ($transaction->fresh()->hasEnoughApprovals()) {
                $transaction->update(['status' => 'approved']);
                
                // TODO: Execute transaction on blockchain
                // For now, we'll mark it as ready for execution
            }

            DB::commit();

            return back()->with(['success' => ['Transaction approved successfully']]);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to approve transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject a transaction
     */
    public function reject(Request $request, $walletId, $id)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($walletId);
        $transaction = MultiSigTransaction::where('multi_sig_wallet_id', $walletId)
            ->findOrFail($id);

        // Check if transaction is pending
        if ($transaction->status !== 'pending') {
            return back()->withErrors(['error' => 'Transaction is not pending approval']);
        }

        // Check if user can approve (can reject if can approve)
        $signer = $wallet->signers()
            ->where('signer_id', auth()->id())
            ->where('signer_type', 'user')
            ->where('can_approve', true)
            ->first();

        if (!$signer) {
            return back()->withErrors(['error' => 'You are not authorized to reject transactions']);
        }

        // Check if user already acted on this transaction
        if ($transaction->hasUserApproved(auth()->id(), 'user')) {
            return back()->withErrors(['error' => 'You have already acted on this transaction']);
        }

        DB::beginTransaction();
        try {
            // Create rejection record
            TransactionApproval::create([
                'multi_sig_transaction_id' => $transaction->id,
                'signer_id' => auth()->id(),
                'signer_type' => 'user',
                'action' => 'reject',
                'comment' => $request->reason ?? 'Rejected by signer',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Update transaction status
            $transaction->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason ?? 'Rejected by signer',
            ]);

            DB::commit();

            return back()->with(['success' => ['Transaction rejected successfully']]);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to reject transaction: ' . $e->getMessage()]);
        }
    }

    /**
     * Execute an approved transaction
     */
    public function execute($walletId, $id)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($walletId);
        $transaction = MultiSigTransaction::where('multi_sig_wallet_id', $walletId)
            ->findOrFail($id);

        if (!$transaction->canBeExecuted()) {
            return back()->withErrors(['error' => 'Transaction cannot be executed']);
        }

        DB::beginTransaction();
        try {
            // TODO: Implement actual blockchain transaction execution
            // For now, we'll simulate execution

            $transaction->update([
                'status' => 'executed',
                'executed_at' => now(),
                'blockchain_txn_hash' => 'simulated_' . bin2hex(random_bytes(16)),
            ]);

            // Update wallet balance
            $wallet->decrement('balance', $transaction->amount + $transaction->fee);

            DB::commit();

            return back()->with(['success' => ['Transaction executed successfully']]);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to execute transaction: ' . $e->getMessage()]);
        }
    }
}
