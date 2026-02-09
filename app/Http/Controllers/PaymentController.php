<?php

namespace App\Http\Controllers;

use App\Models\StudentFee;
use App\Services\SslCommerzService;
use App\Services\BkashService;
use App\Services\RocketService;
use App\Services\NagadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function initiate($feeId)
    {
        $studentFee = StudentFee::with(['student', 'feeLot'])->findOrFail($feeId);

        // Check if already paid
        if ($studentFee->status == 'paid') {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(warningMessage('warning', 'This fee has already been paid!'));
        }

        // Get active gateway
        $settings = gatewaySettings();
        $activeGateway = $settings->active_gateway ?? 'ssl_commerz';

        // Generate unique transaction ID
        $transactionId = 'FEE' . time() . Str::random(5);

        // Prepare payment data
        $paymentData = [
            'amount' => $studentFee->amount - ($studentFee->paid_amount ?? 0),
            'transaction_id' => $transactionId,
            'customer_name' => showStudentFullName(
                $studentFee->student->firstname ?? 'Student',
                $studentFee->student->middlname ?? '',
                $studentFee->student->lastname ?? ''
            ),
            'customer_email' => $studentFee->student->email ?? 'student@school.com',
            'customer_phone' => $studentFee->student->phone ?? '01700000000',
            'customer_address' => $studentFee->student->address ?? 'N/A',
            'customer_city' => 'Dhaka',
            'product_name' => $studentFee->feeLot->title . ' - Fee Payment',
            'product_category' => 'Education Fee',
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),
            'callback_url' => route('payment.success'),
        ];

        // Store transaction ID and gateway
        $studentFee->transaction_id = $transactionId;
        $studentFee->payment_gateway = $activeGateway;
        $studentFee->save();

        // Route to appropriate gateway
        return $this->processPaymentByGateway($activeGateway, $paymentData, $studentFee);
    }

    protected function processPaymentByGateway($gateway, $paymentData, $studentFee)
    {
        switch ($gateway) {
            case 'ssl_commerz':
                return $this->processSslCommerz($paymentData, $studentFee);
            
            case 'bkash':
                return $this->processBkash($paymentData, $studentFee);
            
            case 'rocket':
                return $this->processRocket($paymentData, $studentFee);
            
            case 'nagad':
                return $this->processNagad($paymentData, $studentFee);
            
            default:
                return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                    ->with(dangerMessage('danger', 'Invalid payment gateway configured!'));
        }
    }

    protected function processSslCommerz($paymentData, $studentFee)
    {
        $sslCommerz = new SslCommerzService();
        $response = $sslCommerz->createPaymentSession($paymentData);

        if (isset($response['status']) && $response['status'] == 'SUCCESS') {
            return redirect($response['GatewayPageURL']);
        } else {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(dangerMessage('danger', 'SSL COMMERZ payment initialization failed: ' . ($response['failedreason'] ?? 'Unknown error')));
        }
    }

    protected function processBkash($paymentData, $studentFee)
    {
        $bkash = new BkashService();
        $response = $bkash->createPayment($paymentData);

        if (isset($response['bkashURL'])) {
            // Store payment ID for execution
            $studentFee->update(['transaction_id' => $response['paymentID'] ?? $studentFee->transaction_id]);
            return redirect($response['bkashURL']);
        } else {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(dangerMessage('danger', 'bKash payment initialization failed!'));
        }
    }

    protected function processRocket($paymentData, $studentFee)
    {
        $rocket = new RocketService();
        $response = $rocket->createPayment($paymentData);

        if (isset($response['status']) && $response['status'] == 'SUCCESS') {
            return redirect($response['payment_url'] ?? '#');
        } else {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(dangerMessage('danger', 'Rocket payment initialization failed!'));
        }
    }

    protected function processNagad($paymentData, $studentFee)
    {
        $nagad = new NagadService();
        $response = $nagad->createPayment($paymentData);

        if (isset($response['status']) && $response['status'] == 'SUCCESS') {
            return redirect($response['redirectUrl']);
        } else {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(dangerMessage('danger', 'Nagad payment initialization failed!'));
        }
    }

    public function success(Request $request)
    {
        // Get active gateway to determine validation method
        $settings = gatewaySettings();
        $activeGateway = $settings->active_gateway ?? 'ssl_commerz';

        if ($activeGateway == 'ssl_commerz') {
            return $this->handleSslCommerzSuccess($request);
        } elseif ($activeGateway == 'bkash') {
            return $this->handleBkashSuccess($request);
        } elseif ($activeGateway == 'rocket') {
            return $this->handleRocketSuccess($request);
        } elseif ($activeGateway == 'nagad') {
            return $this->handleNagadSuccess($request);
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'Payment verification failed'));
    }

    protected function handleSslCommerzSuccess(Request $request)
    {
        $tranId = $request->input('tran_id');
        $valId = $request->input('val_id');

        $sslCommerz = new SslCommerzService();
        $validation = $sslCommerz->validateTransaction($valId);

        if (isset($validation['status']) && $validation['status'] == 'VALID') {
            return $this->updateStudentFee($tranId, $validation['amount'], 'ssl_commerz');
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'SSL COMMERZ payment verification failed'));
    }

    protected function handleBkashSuccess(Request $request)
    {
        $paymentID = $request->input('paymentID');
        
        $bkash = new BkashService();
        $execution = $bkash->executePayment($paymentID);

        if (isset($execution['transactionStatus']) && $execution['transactionStatus'] == 'Completed') {
            return $this->updateStudentFee($paymentID, $execution['amount'], 'bkash');
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'bKash payment verification failed'));
    }

    protected function handleRocketSuccess(Request $request)
    {
        $tranId = $request->input('transaction_id');
        
        $rocket = new RocketService();
        $validation = $rocket->validateTransaction($tranId);

        if (isset($validation['status']) && $validation['status'] == 'SUCCESS') {
            return $this->updateStudentFee($tranId, $validation['amount'], 'rocket');
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'Rocket payment verification failed'));
    }

    protected function handleNagadSuccess(Request $request)
    {
        $paymentRefId = $request->input('payment_ref_id');
        
        $nagad = new NagadService();
        $validation = $nagad->validateTransaction($paymentRefId);

        if (isset($validation['status']) && $validation['status'] == 'Success') {
            return $this->updateStudentFee($validation['orderId'], $validation['amount'], 'nagad');
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'Nagad payment verification failed'));
    }

    protected function updateStudentFee($tranId, $amount, $gateway)
    {
        $studentFee = StudentFee::where('transaction_id', $tranId)->first();

        if ($studentFee) {
            DB::beginTransaction();
            try {
                $paidAmount = ($studentFee->paid_amount ?? 0) + $amount;
                
                $studentFee->paid_amount = $paidAmount;
                $studentFee->payment_gateway = $gateway;
                $studentFee->payment_date = now();
                
                // Update status
                if ($paidAmount >= $studentFee->amount) {
                    $studentFee->status = 'paid';
                } else {
                    $studentFee->status = 'partial';
                }
                
                $studentFee->save();

                DB::commit();

                return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                    ->with(successMessage('success', 'Payment successful! Amount: ' . $amount . ' BDT'));
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                    ->with(dangerMessage('danger', 'Payment verification failed'));
            }
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'Payment record not found'));
    }

    public function fail(Request $request)
    {
        $tranId = $request->input('tran_id') ?? $request->input('transaction_id');
        $studentFee = StudentFee::where('transaction_id', $tranId)->first();

        if ($studentFee) {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(dangerMessage('danger', 'Payment failed! Please try again.'));
        }

        return redirect()->route('fee-lots.index')
            ->with(dangerMessage('danger', 'Payment failed!'));
    }

    public function cancel(Request $request)
    {
        $tranId = $request->input('tran_id') ?? $request->input('transaction_id');
        $studentFee = StudentFee::where('transaction_id', $tranId)->first();

        if ($studentFee) {
            return redirect()->route('fee-lots.show', $studentFee->fee_lot_id)
                ->with(warningMessage('warning', 'Payment was cancelled.'));
        }

        return redirect()->route('fee-lots.index')
            ->with(warningMessage('warning', 'Payment was cancelled.'));
    }
}
