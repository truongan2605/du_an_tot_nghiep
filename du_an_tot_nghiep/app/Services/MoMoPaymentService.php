<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MoMoPaymentService
{
    private $partnerCode;
    private $accessKey;
    private $secretKey;
    private $endpoint;

    public function __construct()
    {
        $this->partnerCode = config('services.momo.partner_code');
        $this->accessKey = config('services.momo.access_key');
        $this->secretKey = config('services.momo.secret_key');
        $this->endpoint = config('services.momo.endpoint');
        
        // Debug log
        Log::info('MoMoPaymentService Config Loaded', [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'secretKey' => substr($this->secretKey ?? '', 0, 10) . '...',
            'endpoint' => $this->endpoint,
        ]);
    }

    /**
     * Tạo URL thanh toán MoMo
     *
     * @param array $data
     * @return array ['payUrl' => string, 'deeplink' => string, 'qrCodeUrl' => string]
     */
    public function createPaymentUrl(array $data): array
    {
        $orderId = $data['orderId'];
        $amount = $data['amount'];
        $orderInfo = $data['orderInfo'];
        $returnUrl = $data['returnUrl'];
        $notifyUrl = $data['notifyUrl'];
        $extraData = $data['extraData'] ?? '';

        $requestId = $orderId . '-' . time();
        $requestType = 'captureWallet';

        // Tạo raw signature
        $rawSignature = "accessKey=" . $this->accessKey .
            "&amount=" . $amount .
            "&extraData=" . $extraData .
            "&ipnUrl=" . $notifyUrl .
            "&orderId=" . $orderId .
            "&orderInfo=" . $orderInfo .
            "&partnerCode=" . $this->partnerCode .
            "&redirectUrl=" . $returnUrl .
            "&requestId=" . $requestId .
            "&requestType=" . $requestType;

        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        $requestData = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'amount' => (string)$amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyUrl,
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
            'lang' => 'vi'
        ];

        Log::info('MoMo Payment Request', [
            'requestData' => $requestData,
            'rawSignature' => $rawSignature,
        ]);

        $response = $this->sendPostRequest($this->endpoint . '/v2/gateway/api/create', $requestData);

        Log::info('MoMo Payment Response', ['response' => $response]);

        if (isset($response['resultCode']) && $response['resultCode'] == 0) {
            return [
                'payUrl' => $response['payUrl'] ?? '',
                'deeplink' => $response['deeplink'] ?? '',
                'qrCodeUrl' => $response['qrCodeUrl'] ?? '',
            ];
        }

        throw new \Exception('MoMo API Error: ' . ($response['message'] ?? 'Unknown error'));
    }

    /**
     * Xác thực chữ ký từ MoMo callback/IPN
     *
     * @param array $data
     * @return bool
     */
    public function verifySignature(array $data): bool
    {
        $receivedSignature = $data['signature'] ?? '';

        $rawSignature = "accessKey=" . $this->accessKey .
            "&amount=" . ($data['amount'] ?? '') .
            "&extraData=" . ($data['extraData'] ?? '') .
            "&message=" . ($data['message'] ?? '') .
            "&orderId=" . ($data['orderId'] ?? '') .
            "&orderInfo=" . ($data['orderInfo'] ?? '') .
            "&orderType=" . ($data['orderType'] ?? '') .
            "&partnerCode=" . $this->partnerCode .
            "&payType=" . ($data['payType'] ?? '') .
            "&requestId=" . ($data['requestId'] ?? '') .
            "&responseTime=" . ($data['responseTime'] ?? '') .
            "&resultCode=" . ($data['resultCode'] ?? '') .
            "&transId=" . ($data['transId'] ?? '');

        $calculatedSignature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        Log::info('MoMo Signature Verification', [
            'rawSignature' => $rawSignature,
            'receivedSignature' => $receivedSignature,
            'calculatedSignature' => $calculatedSignature,
            'match' => ($calculatedSignature === $receivedSignature),
        ]);

        return $calculatedSignature === $receivedSignature;
    }

    /**
     * Gửi POST request đến MoMo API
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    private function sendPostRequest(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        
        // Skip SSL verification for sandbox/development (REMOVE in production!)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        // Set timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        if ($curlErrno) {
            Log::error('MoMo API cURL Error', [
                'error' => $curlError,
                'errno' => $curlErrno,
                'url' => $url,
            ]);
            return ['resultCode' => -1, 'message' => 'cURL Error: ' . $curlError];
        }

        if ($httpCode !== 200) {
            Log::error('MoMo API HTTP Error', [
                'httpCode' => $httpCode,
                'response' => $response,
                'url' => $url,
            ]);
            return ['resultCode' => -1, 'message' => 'HTTP Error: ' . $httpCode];
        }

        return json_decode($response, true) ?? [];
    }
}
