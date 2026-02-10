<?php

namespace App\Services;

use App\Models\StudentFee;
use App\Models\PaymentTransaction;
use Illuminate\Support\Str;
use App\Library\SslCommerz\SslCommerzNotification;

/**
 * Class SslCommerzService - Student Fee Payment Integration
 * Uses PaymentTransaction table for detailed tracking
 */
class SslCommerzService
{
    public function initiate(StudentFee $studentFee)
    {
        $post_data = array();
        
        // Calculate amount to pay
        $amountToPay = $studentFee->amount - ($studentFee->paid_amount ?? 0);
        
        $post_data['total_amount'] = $amountToPay;
        $post_data['currency'] = "BDT";
        $post_data['tran_id'] = Str::upper('FEES' . time() . Str::random(5)); // tran_id must be unique

        # Student Information
        $studentName = showStudentFullName(
            $studentFee->student->firstname ?? 'Student',
            $studentFee->student->middlname ?? '',
            $studentFee->student->lastname ?? ''
        );
        $mobile = $studentFee->student->phone ?? '01700000000';
        $email = $studentFee->student->email ?? 'student@school.com';
        $address = $studentFee->student->address ?? 'N/A';
        $city_town = 'Dhaka';
        $post_code = '';

        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $studentName;
        $post_data['cus_email'] = $email;
        $post_data['cus_add1'] = $address;
        $post_data['cus_add2'] = $address;
        $post_data['cus_city'] = $city_town;
        $post_data['cus_state'] = $city_town;
        $post_data['cus_postcode'] = $post_code;
        $post_data['cus_country'] = "Bangladesh";
        $post_data['cus_phone'] = $mobile;
        $post_data['cus_fax'] = "";

        # SHIPMENT INFORMATION (not applicable for fees but required by SSL Commerz)
        $post_data['ship_name'] = $studentName;
        $post_data['ship_add1'] = $address;
        $post_data['ship_add2'] = $address;
        $post_data['ship_city'] = $city_town;
        $post_data['ship_state'] = $city_town;
        $post_data['ship_postcode'] = $post_code ?? "";
        $post_data['ship_phone'] = $mobile;
        $post_data['ship_country'] = "Bangladesh";

        $post_data['shipping_method'] = "NO";
        $post_data['product_name'] = $studentFee->feeLot->title . " - Student Fee";
        $post_data['product_category'] = "Education";
        $post_data['product_profile'] = "general";

        # OPTIONAL PARAMETERS
        $post_data['value_a'] = $studentFee->unique_id ?? '';
        $post_data['value_b'] = $studentFee->id;
        $post_data['value_c'] = $studentFee->fee_lot_id;
        $post_data['value_d'] = $studentFee->student->student_id ?? '';

        # Create PaymentTransaction record
        PaymentTransaction::create([
            'student_fee_id' => $studentFee->id,
            'transaction_id' => $post_data['tran_id'],
            'gateway' => 'ssl_commerz',
            'transaction_ip' => request()->ip(),
            'transaction_amount' => $post_data['total_amount'],
            'status' => 'PENDING',
            'currency' => $post_data['currency'],
        ]);

        // Update student fee with transaction ID only
        $studentFee->update(['transaction_id' => $post_data['tran_id']]);

        try {
            $sslc = new SslCommerzNotification();
            # initiate(Transaction Data, false: Redirect to SSLCOMMERZ gateway / true: Show all the Payment gateway here)
            $payment_options = $sslc->makePayment($post_data, 'hosted');

            // If makePayment fails, it returns a string error message
            if (!is_array($payment_options)) {
                // Update transaction as failed
                PaymentTransaction::where('transaction_id', $post_data['tran_id'])
                    ->update(['status' => 'FAILED', 'message' => $payment_options]);
                
                return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                    'type' => 'danger',
                    'message' => 'Payment gateway error: ' . $payment_options
                ]);
            }
        } catch (\Exception $e) {
            // Update transaction as failed
            PaymentTransaction::where('transaction_id', $post_data['tran_id'])
                ->update(['status' => 'FAILED', 'message' => $e->getMessage()]);
            
            return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                'type' => 'danger',
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ]);
        }
    }

    public function success($request)
    {
        $tran_id = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        $sslc = new SslCommerzNotification();
        $transaction = PaymentTransaction::where('transaction_id', $tran_id)
            ->select('transaction_id', 'status', 'currency', 'transaction_amount', 'student_fee_id')->firstOrFail();

        if (($request->input('status') === "VALID") and ($transaction->status === 'PENDING')) {
            $validation = $sslc->orderValidate($request->all(), $tran_id, $amount, $currency);

            if ($validation == TRUE) {
                // Update PaymentTransaction with all details
                PaymentTransaction::where('transaction_id', $tran_id)->update([
                    'gateway' => 'ssl_commerz',
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
                
                // Update StudentFee - ONLY basic fields
                $studentFee = StudentFee::find($transaction->student_fee_id);
                $paidAmount = ($studentFee->paid_amount ?? 0) + $amount;

                $studentFee->update([
                    'paid_amount' => $paidAmount,
                    'payment_gateway' => 'ssl_commerz',
                    'payment_date' => now(),
                    'status' => $paidAmount >= $studentFee->amount ? 'paid' : 'partial',
                ]);

                return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                    'type' => 'success',
                    'message' => 'Thank you! Your payment was successful. Amount paid: ' . numberFormat($amount) . ' BDT'
                ]);
            } else {
                // Validation failed
                PaymentTransaction::where('transaction_id', $tran_id)->update(['status' => 'FAILED']);
                
                $studentFee = StudentFee::find($transaction->student_fee_id);
                
                return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                    'type' => 'danger',
                    'message' => 'Transaction Failed! Please try again.'
                ]);
            }
        } else if ($transaction->status === 'SUCCESS') {
            $studentFee = StudentFee::find($transaction->student_fee_id);
            
            return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                'type' => 'success',
                'message' => 'Thank you! Your payment was successful.'
            ]);
        } else {
            return redirect()->route('fee-lots.index')->with([
                'type' => 'danger',
                'message' => 'Something went wrong when processing transaction. Please contact administration.'
            ]);
        }
    }

    public function fail($request)
    {
        $tran_id = $request->input('tran_id');
        
        if ($request->input('status') === "FAILED") {
            PaymentTransaction::where('transaction_id', $tran_id)
                ->update(['status' => 'FAILED', 'message' => $request->input('error')]);
        }

        $transaction = PaymentTransaction::where('transaction_id', $tran_id)->first();

        if ($transaction) {
            $studentFee = StudentFee::find($transaction->student_fee_id);
            
            return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                'type' => 'danger',
                'message' => 'Transaction Failed! Please try again.'
            ]);
        }

        return redirect()->route('fee-lots.index')->with([
            'type' => 'danger',
            'message' => 'Transaction Failed!'
        ]);
    }

    public function cancel($request)
    {
        $tran_id = $request->input('tran_id');
        
        if ($request->input('status') === "CANCELLED") {
            PaymentTransaction::where('transaction_id', $tran_id)
                ->update(['status' => 'CANCELLED', 'message' => $request->input('error')]);
        }

        $transaction = PaymentTransaction::where('transaction_id', $tran_id)->first();

        if ($transaction) {
            $studentFee = StudentFee::find($transaction->student_fee_id);
            
            return redirect()->route('fee-lots.show', encrypt_decrypt($studentFee->fee_lot_id, 'encrypt'))->with([
                'type' => 'warning',
                'message' => 'Transaction Cancelled! You can try again.'
            ]);
        }

        return redirect()->route('fee-lots.index')->with([
            'type' => 'danger',
            'message' => 'Invalid Transaction!'
        ]);
    }

    public function ipn($request)
    {
        if ($request->input('tran_id')) {
            $tran_id = $request->input('tran_id');
            $transaction = PaymentTransaction::where('transaction_id', $tran_id)->first();

            if ($transaction && $transaction->status === 'PENDING') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($request->all(), $tran_id, $transaction->transaction_amount, $transaction->currency);
                
                if ($validation == TRUE) {
                    // IPN worked - update transaction
                    PaymentTransaction::where('transaction_id', $tran_id)->update(['status' => 'SUCCESS']);

                    // Update student fee
                    $studentFee = StudentFee::find($transaction->student_fee_id);
                    $paidAmount = ($studentFee->paid_amount ?? 0) + $transaction->transaction_amount;
                    
                    $studentFee->update([
                        'paid_amount' => $paidAmount,
                        'payment_gateway' => 'ssl_commerz',
                        'payment_date' => now(),
                        'status' => $paidAmount >= $studentFee->amount ? 'paid' : 'partial',
                    ]);

                    return redirect()->route('fee-lots.index')->with([
                        'type' => 'success',
                        'message' => 'Transaction is successfully Completed!'
                    ]);
                } else {
                    PaymentTransaction::where('transaction_id', $tran_id)->update(['status' => 'FAILED']);

                    return redirect()->route('fee-lots.index')->with([
                        'type' => 'danger',
                        'message' => 'Transaction Failed!'
                    ]);
                }
            } else if ($transaction && $transaction->status === "SUCCESS") {
                return redirect()->route('fee-lots.index')->with([
                    'type' => 'success',
                    'message' => 'Transaction already completed!'
                ]);
            }
        }
        
        return redirect()->route('fee-lots.index')->with([
            'type' => 'danger',
            'message' => 'Invalid Data!'
        ]);
    }

    // Refund method
    public function initiateRefund(PaymentTransaction $transaction, $refundAmount, $remarks = '')
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

        $config = config('sslcommerz');
        $storeId = $config['apiCredentials']['store_id'];
        $storePassword = $config['apiCredentials']['store_password'];
        $apiUrl = $config['apiDomain'];

        $postData = [
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'refund_amount' => $refundAmount,
            'refund_remarks' => $remarks,
            'bank_tran_id' => $transaction->bank_tran_id,
            'refe_id' => Str::upper('REF' . time()),
        ];

        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $apiUrl . '/validator/api/merchantTransIDvalidationAPI.php');
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

                // Update student fee - reduce paid amount
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
        } else {
            curl_close($handle);
        }

        return [
            'success' => false,
            'message' => 'Refund request failed'
        ];
    }
}
