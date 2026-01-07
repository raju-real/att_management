<?php

namespace App\Services;

class TwoFactorSmsService
{
    public function sendVerificationCode($user)
    {
        $code = rand(100000, 999999); // 6-digit code
        $expiresAt = now()->addMinutes(15);
        
        // Store code in database
        $user->update([
            'two_factor_verification_code' => encrypt($code),
            'two_factor_code_expires_at' => $expiresAt
        ]);
        
        // Send SMS (implementation depends on your SMS provider)
        // $this->sendSms(
        //     $user->phone_number,
        //     "Your verification code is: $code"
        // );
        
        // return $code;
    }
    
    protected function sendSms($phoneNumber, $message)
    {
        // Implement your SMS gateway integration here
        // Example using Twilio:
        // Twilio::message($phoneNumber, $message);
    }
}
}
