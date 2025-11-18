<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\MultiSigWallet;
use App\Models\WalletSigner;
use App\Models\Admin\Currency;
use App\Traits\PaymentGateway\Tatum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class MultiSigWalletController extends Controller
{
    use Tatum;

    /**
     * Display a listing of multi-sig wallets
     */
    public function index()
    {
        $page_title = "Multi-Signature Wallets";
        $wallets = MultiSigWallet::auth()
            ->with(['signers', 'activeSigners'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $supported_chains = $this->tatumActiveChains();

        return view('user.sections.multi-sig-wallet.index', compact('page_title', 'wallets', 'supported_chains'));
    }

    /**
     * Show the form for creating a new multi-sig wallet
     */
    public function create()
    {
        $page_title = "Create Multi-Signature Wallet";
        $supported_chains = $this->tatumActiveChains();
        $crypto_currencies = Currency::where('type', 'CRYPTO')->active()->get();

        return view('user.sections.multi-sig-wallet.create', compact('page_title', 'supported_chains', 'crypto_currencies'));
    }

    /**
     * Store a newly created multi-sig wallet
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'blockchain' => 'required|string|max:100',
            'currency_code' => 'required|string|max:10',
            'required_signatures' => 'required|integer|min:1',
            'signers' => 'required|array|min:1',
            'signers.*.email' => 'required|email',
            'signers.*.name' => 'required|string|max:255',
            'signers.*.weight' => 'nullable|integer|min:1',
            'signers.*.can_initiate' => 'nullable|boolean',
            'signers.*.can_approve' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Validate that required_signatures doesn't exceed total signers
        if ($validated['required_signatures'] > count($validated['signers'])) {
            return back()->withErrors(['required_signatures' => 'Required signatures cannot exceed total signers'])->withInput();
        }

        DB::beginTransaction();
        try {
            // Create the multi-sig wallet
            $wallet = MultiSigWallet::create([
                'name' => $validated['name'],
                'wallet_type' => count($validated['signers']) > 1 ? 'multi_sig' : 'single',
                'owner_id' => auth()->user()->id,
                'owner_type' => 'user',
                'blockchain' => $validated['blockchain'],
                'currency_code' => $validated['currency_code'],
                'required_signatures' => $validated['required_signatures'],
                'total_signers' => count($validated['signers']),
                'status' => true,
            ]);

            // Add the owner as the first signer
            WalletSigner::create([
                'multi_sig_wallet_id' => $wallet->id,
                'signer_id' => auth()->user()->id,
                'signer_type' => 'user',
                'signer_name' => auth()->user()->fullname ?? auth()->user()->username,
                'signer_email' => auth()->user()->email,
                'weight' => 1,
                'is_owner' => true,
                'can_initiate' => true,
                'can_approve' => true,
                'status' => true,
            ]);

            // Add additional signers
            foreach ($validated['signers'] as $signer) {
                WalletSigner::create([
                    'multi_sig_wallet_id' => $wallet->id,
                    'signer_id' => 0, // Will be updated when user registers
                    'signer_type' => 'user',
                    'signer_name' => $signer['name'],
                    'signer_email' => $signer['email'],
                    'weight' => $signer['weight'] ?? 1,
                    'is_owner' => false,
                    'can_initiate' => $signer['can_initiate'] ?? true,
                    'can_approve' => $signer['can_approve'] ?? true,
                    'status' => true,
                ]);
            }

            DB::commit();

            return redirect()->route('user.multi.sig.wallet.index')
                ->with(['success' => ['Multi-signature wallet created successfully!']]);

        } catch (Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create wallet: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified multi-sig wallet
     */
    public function show($id)
    {
        $wallet = MultiSigWallet::auth()
            ->with(['signers', 'transactions'])
            ->findOrFail($id);

        $page_title = "Wallet Details: " . $wallet->name;

        return view('user.sections.multi-sig-wallet.details', compact('page_title', 'wallet'));
    }

    /**
     * Show transactions for a specific wallet
     */
    public function transactions($id)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($id);
        $page_title = "Transactions: " . $wallet->name;

        $transactions = $wallet->transactions()
            ->with(['approvals', 'initiator'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('user.sections.multi-sig-wallet.transactions', compact('page_title', 'wallet', 'transactions'));
    }

    /**
     * Update wallet status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $wallet = MultiSigWallet::auth()->findOrFail($id);

        if (!$wallet->isOwner(auth()->user()->id, 'user')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $wallet->update(['status' => $request->status]);

        return response()->json(['success' => 'Wallet status updated successfully']);
    }

    /**
     * Delete a multi-sig wallet
     */
    public function destroy($id)
    {
        $wallet = MultiSigWallet::auth()->findOrFail($id);

        if (!$wallet->isOwner(auth()->user()->id, 'user')) {
            return back()->withErrors(['error' => 'Unauthorized']); 
        }

        // Check if wallet has balance
        if ($wallet->balance > 0) {
            return back()->withErrors(['error' => 'Cannot delete wallet with balance. Please withdraw funds first.']);
        }

        $wallet->delete();

        return redirect()->route('user.multi.sig.wallet.index')
            ->with(['success' => ['Wallet deleted successfully!']]);
    }
}
