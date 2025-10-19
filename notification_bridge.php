<?php
/**
 * PHP Bridge for SmileBright Notifications
 * Sends booking events to Node.js notification service
 */

class NotificationBridge {
    private $nodeServiceUrl;
    private $apiSecret;
    private $timeout;
    private $enabled;

    public function __construct() {
        $this->nodeServiceUrl = 'http://localhost:3001/api';
        $this->apiSecret = 'your-secret-key-for-webhook-validation'; // Should match Node.js config
        $this->timeout = 10; // seconds
        $this->enabled = true; // Feature flag
    }

    /**
     * Send booking created notification
     */
    public function sendBookingCreated($bookingData) {
        if (!$this->enabled) {
            error_log('Notifications disabled, skipping booking created notification');
            return false;
        }

        $payload = [
            'to' => $bookingData['email'],
            'template_id' => 'booking_created',
            'variables' => [
                'reference_id' => $bookingData['reference_id'],
                'full_name' => $bookingData['full_name'],
                'email' => $bookingData['email'],
                'phone' => $bookingData['phone'],
                'clinic' => $bookingData['preferred_clinic'],
                'service' => $bookingData['service'],
                'date' => $bookingData['preferred_date'],
                'time' => $bookingData['preferred_time'],
                'message' => $bookingData['message'] ?? '',
                'reschedule_token' => $bookingData['reschedule_token'],
                'booking_id' => $bookingData['id'],
                'view_url' => $this->generateViewUrl($bookingData['reference_id'], $bookingData['reschedule_token']),
                'cancel_url' => $this->generateCancelUrl($bookingData['reference_id'], $bookingData['reschedule_token']),
                'token_expiry_date' => $this->formatTokenExpiry($bookingData['token_expires_at'])
            ]
        ];

        return $this->sendNotification('/notifications/booking-created', $payload);
    }

    /**
     * Send clinic adjustment notification
     */
    public function sendClinicAdjusted($bookingData, $oldData = []) {
        if (!$this->enabled) {
            error_log('Notifications disabled, skipping clinic adjustment notification');
            return false;
        }

        $payload = [
            'to' => $bookingData['email'],
            'template_id' => 'clinic_adjusted',
            'variables' => [
                'reference_id' => $bookingData['reference_id'],
                'full_name' => $bookingData['full_name'],
                'email' => $bookingData['email'],
                'phone' => $bookingData['phone'],
                'clinic' => $bookingData['preferred_clinic'],
                'service' => $bookingData['service'],
                'date' => $bookingData['preferred_date'],
                'time' => $bookingData['preferred_time'],
                'old_date' => $oldData['preferred_date'] ?? '',
                'old_time' => $oldData['preferred_time'] ?? '',
                'old_clinic' => $oldData['preferred_clinic'] ?? '',
                'reason' => $oldData['reason'] ?? 'Schedule adjustment',
                'view_url' => $this->generateViewUrl($bookingData['reference_id'], $bookingData['reschedule_token'])
            ]
        ];

        return $this->sendNotification('/notifications/clinic-adjusted', $payload);
    }

    /**
     * Send reschedule confirmation notification
     */
    public function sendRescheduleConfirmed($bookingData) {
        if (!$this->enabled) {
            error_log('Notifications disabled, skipping reschedule confirmation notification');
            return false;
        }

        $payload = [
            'to' => $bookingData['email'],
            'template_id' => 'rescheduled_by_client',
            'variables' => [
                'reference_id' => $bookingData['reference_id'],
                'full_name' => $bookingData['full_name'],
                'email' => $bookingData['email'],
                'phone' => $bookingData['phone'],
                'clinic' => $bookingData['preferred_clinic'],
                'service' => $bookingData['service'],
                'date' => $bookingData['preferred_date'],
                'time' => $bookingData['preferred_time'],
                'view_url' => $this->generateViewUrl($bookingData['reference_id'], $bookingData['reschedule_token'])
            ]
        ];

        return $this->sendNotification('/notifications/rescheduled-by-client', $payload);
    }

    /**
     * Send notification to Node.js service
     */
    private function sendNotification($endpoint, $payload) {
        try {
            $url = $this->nodeServiceUrl . $endpoint;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'X-API-Secret: ' . $this->apiSecret,
                    'User-Agent: SmileBright-PHP-Bridge/1.0'
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => false, // For development only
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                error_log("Notification bridge cURL error: $error");
                return false;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                $responseData = json_decode($response, true);
                error_log("Notification sent successfully: " . json_encode($responseData));
                return $responseData;
            } else {
                error_log("Notification failed with HTTP $httpCode: $response");
                return false;
            }

        } catch (Exception $e) {
            error_log("Notification bridge exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate view URL for appointment
     */
    private function generateViewUrl($referenceId, $token) {
        $baseUrl = 'https://smilebrightdental.sg'; // Update with your domain
        return "$baseUrl/confirm.php?ref=$referenceId&token=$token";
    }

    /**
     * Generate cancel URL for appointment
     */
    private function generateCancelUrl($referenceId, $token) {
        $baseUrl = 'https://smilebrightdental.sg'; // Update with your domain
        return "$baseUrl/cancel.php?ref=$referenceId&token=$token";
    }

    /**
     * Format token expiry date
     */
    private function formatTokenExpiry($expiryDate) {
        if (!$expiryDate) return '';
        return date('M j, Y', strtotime($expiryDate));
    }

    /**
     * Test connection to Node.js service
     */
    public function testConnection() {
        try {
            $url = $this->nodeServiceUrl . '/health';
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => false
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'error' => "Connection error: $error"
                ];
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    'success' => true,
                    'data' => $data
                ];
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP $httpCode: $response"
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Exception: " . $e->getMessage()
            ];
        }
    }

    /**
     * Enable/disable notifications
     */
    public function setEnabled($enabled) {
        $this->enabled = (bool)$enabled;
    }

    /**
     * Get notification status
     */
    public function isEnabled() {
        return $this->enabled;
    }
}

// Helper function for easy integration
function sendBookingNotification($bookingData, $event = 'booking_created') {
    static $bridge = null;
    
    if ($bridge === null) {
        $bridge = new NotificationBridge();
    }

    switch ($event) {
        case 'booking_created':
            return $bridge->sendBookingCreated($bookingData);
        case 'clinic_adjusted':
            return $bridge->sendClinicAdjusted($bookingData, $bookingData['old_data'] ?? []);
        case 'rescheduled_by_client':
            return $bridge->sendRescheduleConfirmed($bookingData);
        default:
            error_log("Unknown notification event: $event");
            return false;
    }
}

// Test function
function testNotificationBridge() {
    $bridge = new NotificationBridge();
    $result = $bridge->testConnection();
    
    if ($result['success']) {
        echo "✅ Node.js service is running\n";
        echo "Services: " . json_encode($result['data']['services']) . "\n";
    } else {
        echo "❌ Node.js service connection failed: " . $result['error'] . "\n";
    }
    
    return $result['success'];
}
?>
