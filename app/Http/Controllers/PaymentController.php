<?php

namespace App\Http\Controllers;

use App\Models\StudentFee;
use App\Models\PaymentTransaction;
use App\Services\SslCommerzService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $sslCommerz;

    public function __construct()
    {
        $this->sslCommerz = new SslCommerzService();
    }

    public function initiate($feeId)
    {
        $studentFee = StudentFee::with(['student', 'feeLot'])->whereUniqueId($feeId)->firstOrFail();
        
        // Check if already paid
        if ($studentFee->status == 'paid') {
            return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))
                ->with(warningMessage('warning', 'This fee has already been paid!'));
        }

        // Get active gateway
        $settings = gatewaySettings();
        $activeGateway = $settings->active_gateway ?? 'ssl_commerz';

        // Only SSL Commerz supported
        if ($activeGateway !== 'ssl_commerz') {
            return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))
                ->with(dangerMessage('danger', 'Only SSL COMMERZ gateway is currently supported!'));
        }

        // Initiate SSL Commerz payment
        return $this->sslCommerz->initiate($studentFee);
    }

    public function success(Request $request)
    {
        return $this->sslCommerz->success($request);
    }

    public function fail(Request $request)
    {
        return $this->sslCommerz->fail($request);
    }

    public function cancel(Request $request)
    {
        return $this->sslCommerz->cancel($request);
    }

    public function ipn(Request $request)
    {
        return $this->sslCommerz->ipn($request);
    }

    public function transactionDetails($transactionId)
    {
        $transaction = PaymentTransaction::with(['studentFee.student', 'studentFee.feeLot'])
            ->where('transaction_id', $transactionId)
            ->firstOrFail();

        return view('payments.transaction_details', compact('transaction'));
    }

    public function refund(Request $request, $transactionId)
    {
        $request->validate([
            'refund_amount' => 'required|numeric|min:1',
            'refund_remarks' => 'nullable|string|max:500',
        ]);

        $transaction = PaymentTransaction::where('transaction_id', $transactionId)->firstOrFail();

        // Validate refund amount
        if ($request->refund_amount > $transaction->transaction_amount) {
            return back()->with(dangerMessage('danger', 'Refund amount cannot exceed transaction amount!'));
        }

        // Check if already refunded
        if ($transaction->refund_status === 'REFUNDED') {
            return back()->with(warningMessage('warning', 'This transaction has already been refunded!'));
        }

        try {
            $result = $this->sslCommerz->initiateRefund(
                $transaction,
                $request->refund_amount,
                $request->refund_remarks ?? 'Admin initiated refund'
            );

            if ($result['success']) {
                return back()->with(successMessage('success', $result['message']));
            } else {
                return back()->with(dangerMessage('danger', $result['message']));
            }
        } catch (\Exception $e) {
            return back()->with(dangerMessage('danger', 'Refund failed: ' . $e->getMessage()));
        }
    }
}
