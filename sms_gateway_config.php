<?php
/**
 * SMS Gateway Configuration for Android SMS Forwarder
 * XAMPP Environment - PELIKULA Cinema
 */

class SMSGateway {
    private $gateway_url = "http://192.168.1.15:8080";
    private $username = "sms";
    private $password = "88888888";
    
    /**
     * Send SMS via Android Gateway
     */
    public function sendSMS($recipient, $message) {
        try {
            $url = $this->gateway_url . '/messages';
            
            // Validate phone format
            if (!$this->validatePhoneNumber($recipient)) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number format'
                ];
            }
            
            $payload = [
                "phoneNumbers" => [$recipient],
                "message" => $message
            ];
            
            $options = [
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Basic ' . base64_encode("$this->username:$this->password")
                    ],
                    'content' => json_encode($payload),
                    'timeout' => 30,
                    'ignore_errors' => true
                ]
            ];
            
            $context = stream_context_create($options);
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return [
                    'success' => false,
                    'error' => 'Cannot reach SMS Gateway at ' . $this->gateway_url . '. Please ensure Android device is online and connected to the same network.'
                ];
            }
            
            return [
                'success' => true,
                'response' => json_decode($response, true),
                'message' => 'SMS sent successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'SMS Gateway Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate 6-digit OTP
     */
    public function generateOTP() {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Validate Philippine phone number format
     */
    public function validatePhoneNumber($phone) {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Check Philippine format: +639XXXXXXXXX or 09XXXXXXXXX
        if (preg_match('/^(\+63|0)(9\d{9})$/', $phone, $matches)) {
            // Return in +63 format
            return '+63' . $matches[2];
        }
        
        return false;
    }
    
    /**
     * Format phone for display (mask middle digits)
     */
    public function maskPhoneNumber($phone) {
        if (strlen($phone) >= 10) {
            return substr($phone, 0, 6) . '****' . substr($phone, -2);
        }
        return $phone;
    }
}
?>