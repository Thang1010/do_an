<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HuggingFaceService
{
    protected $token;

    public function __construct()
    {
        $this->token = env('HUGGINGFACE_TOKEN');
    }

    /**
     * Phân tích cảm xúc của một đoạn văn bản (Review của khách hàng)
     * Trả về mảng chứa các nhãn (POS, NEG, NEU) và điểm số (score)
     */
    public function analyzeSentiment($text)
    {
        if (!$this->token) {
            Log::error('HuggingFace Token is missing!');
            return null;
        }

        // Mô hình phân tích cảm xúc tiếng Việt cực tốt
        $url = 'https://router.huggingface.co/hf-inference/models/wonrax/phobert-base-vietnamese-sentiment';

        try {
            $response = Http::withToken($this->token)
                ->timeout(15) // API có thể hơi chậm nếu model đang cold boot
                ->post($url, [
                    'inputs' => $text,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('HuggingFace Sentiment Error: ' . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error('HuggingFace Exception: ' . $e->getMessage());
            return null;
        }
    }
}
