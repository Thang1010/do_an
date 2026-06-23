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

    /**
     * Chuyển đổi Giọng nói thành Văn bản (Speech to Text)
     * Dùng Whisper Large v3 Turbo (đa ngôn ngữ, hỗ trợ tiếng Việt).
     * Lưu ý: PhoWhisper-small đã không còn được hf-inference phục vụ
     * (trả về "Model not supported by provider hf-inference").
     */
    public function speechToText($audioContent)
    {
        if (!$this->token) {
            Log::error('HuggingFace Token is missing!');
            return null;
        }

        $url = 'https://router.huggingface.co/hf-inference/models/openai/whisper-large-v3-turbo';

        try {
            // HF Inference cho Audio thường nhận raw bytes trong body
            $response = Http::withToken($this->token)
                ->withBody($audioContent, 'audio/webm') // Browser ghi âm thường ra webm hoặc mp4
                ->timeout(60) // Whisper có thể mất thời gian khởi động (cold boot)
                ->post($url);

            if ($response->successful()) {
                return $response->json(); // Thường trả về ['text' => 'nội dung...']
            }

            Log::error('HuggingFace STT Error: ' . $response->body());
            return null;
            
        } catch (\Exception $e) {
            Log::error('HuggingFace STT Exception: ' . $e->getMessage());
            return null;
        }
    }
}
