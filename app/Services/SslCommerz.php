<?php

namespace App\Services;

use App\Models\StudentFee;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;

class SslCommerzService
{
    protected $storeId;
    protected $storePassword;
    protected $sandboxMode;
    protected $apiUrl;

    public function __construct()
    {
        $settings = gatewaySettings();
        $sslConfig = $settings->gateways->ssl_commerz ?? null;
        
        $this->storeId = $sslConfig->store_id ?? '';
        $this->storePassword = $sslConfig->store_password ?? '';
        $this->sandboxMode = $sslConfig->sandbox_mode ?? true;
        
        $this->apiUrl = $this->sandboxMode 
            ? 'https://sandbox.sslcommerz.com' 
            : 'https://securepay.sslcommerz.com';
    }

    public function initiatePayment(StudentFee $studentFee)
    {
        // Generate unique transaction ID
        $transactionId = Str::upper('FEES' . time() . Str::random(5));
        
        // Calculate amount to pay
        $amountToPay = $studentFee->amount - ($studentFee->paid_amount ?? 0);
        
        // Create transaction record
        $transaction = PaymentTransaction::create([
            'student_fee_id' => $studentFee->id,
            'transaction_id' => $transactionId,
            'gateway' => 'ssl_commerz',
            'status' => 'PENDING',
            'transaction_amount' => $amountToPay,
            'currency' => 'BDT',
            'transaction_ip' => request()->ip(),
            'transaction_date' => now(),
        ]);

        // Update student fee with transaction ID
        $studentFee->update(['transaction_id' => $transactionId]);

        // Prepare payment data
        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'total_amount' => $amountToPay,
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => route('payment.success'),
            'fail_url' => route('payment.fail'),
            'cancel_url' => route('payment.cancel'),
            
            // Customer information
            'cus_name' => showStudentFullName(
                $studentFee->student->firstname ?? 'Student',
                $studentFee->student->middlname ?? '',
                $studentFee->student->lastname ?? ''
            ),
            'cus_email' => $studentFee->student->email ?? 'student@school.com',
            'cus_add1' => $studentFee->student->address ?? 'N/A',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $studentFee->student->phone ?? '01700000000',
            
            // Product information
            'product_name' => $studentFee->feeLot->title . ' - Student Fee',
            'product_category' => 'Education',
            'product_profile' => 'general',
            'shipping_method' => 'NO',
            
            // Optional parameters
            'value_a' => $studentFee->unique_id ?? '',
            'value_b' => $studentFee->id,
            'value_c' => $studentFee->fee_lot_id,
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/gwprocess/v4/api.php');
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $response = json_decode($content, true);
            return $response;
        } else {
            curl_close($handle);
            return [
                'status' => 'FAILED',
                'failedreason' => 'Unable to connect to payment gateway'
            ];
        }
    }

    public function handleSuccess($request)
    {
        $tranId = $request->input('tran_id');
        $amount = $request->input('amount');
        $valId = $request->input('val_id');

        // Find transaction
        $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();
        
        if (!$transaction || $transaction->status !== 'PENDING') {
            return [
                'success' => false,
                'message' => 'Invalid or already processed transaction'
            ];
        }

        // Validate with SSL Commerz
        $validation = $this->validateTransaction($valId);

        if (isset($validation['status']) && $validation['status'] == 'VALID') {
            // Update transaction record with all SSL Commerz data
            $transaction->update([
                'status' => 'SUCCESS',
                'val_id' => $request->input('val_id'),
                'card_type' => $request->input('card_type'),
                'store_amount' => $request->input('store_amount'),
                'card_no' => $request->input('card_no'),
                'bank_tran_id' => $request->input('bank_tran_id'),
                'transaction_date' => $request->input('tran_date'),
                'card_issuer' => $request->input('card_issuer'),
                'card_brand' => $request->input('card_brand'),
                'card_sub_brand' => $request->input('card_sub_brand'),
                'card_issuer_country' => $request->input('card_issuer_country'),
                'card_issuer_country_code' => $request->input('card_issuer_country_code'),
                'verify_sign' => $request->input('verify_sign'),
                'verify_key' => $request->input('verify_key'),
                'verify_sign_sha2' => $request->input('verify_sign_sha2'),
                'currency_amount' => $request->input('currency_amount'),
                'currency_rate' => $request->input('currency_rate'),
                'risk_level' => $request->input('risk_level'),
                'risk_title' => $request->input('risk_title'),
            ]);

            // Update student fee
            $studentFee = $transaction->studentFee;
            $paidAmount = ($studentFee->paid_amount ?? 0) + $amount;
            
            $studentFee->update([
                'paid_amount' => $paidAmount,
                'payment_gateway' => 'ssl_commerz',
                'payment_date' => now(),
                'status' => $paidAmount >= $studentFee->amount ? 'paid' : 'partial',
            ]);

            return [
                'success' => true,
                'message' => 'Payment successful',
                'student_fee' => $studentFee,
            ];
        } else {
            $transaction->update([
                'status' => 'FAILED',
                'message' => 'Validation failed',
            ]);

            return [
                'success' => false,
                'message' => 'Payment validation failed'
            ];
        }
    }

    public function handleFail($request)
    {
        $tranId = $request->input('tran_id');
        $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'FAILED',
                'error' => $request->input('error'),
                'message' => $request->input('error'),
            ]);
        }

        $studentFee = $transaction ? $transaction->studentFee : null;

        return [
            'success' => false,
            'message' => 'Payment failed',
            'student_fee' => $studentFee,
        ];
    }

    public function handleCancel($request)
    {
        $tranId = $request->input('tran_id');
        $transaction = PaymentTransaction::where('transaction_id', $tranId)->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'CANCELLED',
                'message' => 'Payment cancelled by user',
            ]);
        }

        $studentFee = $transaction ? $transaction->studentFee : null;

        return [
            'success' => false,
            'message' => 'Payment cancelled',
            'student_fee' => $studentFee,
        ];
    }

    public function validateTransaction($validationId)
    {
        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'val_id' => $validationId,
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/validator/api/validationserverAPI.php');
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $response = json_decode($content, true);
            return $response;
        } else {
            curl_close($handle);
            return [
                'status' => 'FAILED',
                'error' => 'Unable to validate transaction'
            ];
        }
    }

    public function initiateRefund($transaction, $refundAmount, $remarks = '')
    {
        if ($transaction->status !== 'SUCCESS') {
            return [
                'success' => false,
                'message' => 'Only successful transactions can be refunded'
            ];
        }

        if ($transaction->refund_status === 'REFUNDED') {
            return [
                'success' => false,
                'message' => 'Transaction already refunded'
            ];
        }

        $postData = [
            'store_id' => $this->storeId,
            'store_passwd' => $this->storePassword,
            'refund_amount' => $refundAmount,
            'refund_remarks' => $remarks,
            'bank_tran_id' => $transaction->bank_tran_id,
            'refe_id' => Str::upper('REF' . time()),
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $this->apiUrl . '/validator/api/merchantTransIDvalidationAPI.php');
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);

        $content = curl_exec($handle);
        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $response = json_decode($content, true);
            
            if (isset($response['status']) && $response['status'] == 'success') {
                // Update transaction
                $transaction->update([
                    'status' => 'REFUNDED',
                    'refund_amount' => $refundAmount,
                    'refund_ref_id' => $postData['refe_id'],
                    'refund_date' => now(),
                    'refund_status' => 'REFUNDED',
                    'refund_remarks' => $remarks,
                ]);

                // Update student fee
                $studentFee = $transaction->studentFee;
                $newPaidAmount = ($studentFee->paid_amount ?? 0) - $refundAmount;
                
                $studentFee->update([
                    'paid_amount' => max(0, $newPaidAmount),
                    'status' => $newPaidAmount >= $studentFee->amount ? 'paid' : ($newPaidAmount > 0 ? 'partial' : 'pending'),
                ]);

                return [
                    'success' => true,
                    'message' => 'Refund processed successfully',
                    'refund_ref_id' => $postData['refe_id'],
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Refund request failed'
        ];
    }
}
