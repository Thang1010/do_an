<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Đổi địa chỉ → toạ độ (kinh độ/vĩ độ) qua OpenStreetMap Nominatim (miễn phí,
 * không cần API key).
 *
 * Kết quả thành công được cache theo địa chỉ nên Nominatim chỉ bị gọi một lần
 * cho mỗi địa chỉ; khi địa chỉ đổi thì khoá cache đổi → tự tra lại. Không lưu
 * cột toạ độ riêng trong DB.
 *
 * Lưu ý chính sách Nominatim: tối đa 1 request/giây và phải gửi User-Agent định
 * danh ứng dụng. Vì đã cache nên thực tế gần như không gọi lặp.
 */
class GeocodingService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const CACHE_PREFIX = 'geocode:';

    /**
     * Nominatim không cần key nên luôn khả dụng.
     */
    public function configured(): bool
    {
        return true;
    }

    /**
     * Trả về ['lat' => float, 'lng' => float] hoặc null nếu không tra được.
     */
    public function geocode(?string $address): ?array
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . md5($address);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $coords = $this->fetchFromNominatim($address);

        // Chỉ cache khi thành công, tránh "khoá cứng" lỗi tạm thời của dịch vụ.
        if ($coords !== null) {
            Cache::forever($cacheKey, $coords);
        }

        return $coords;
    }

    private function fetchFromNominatim(string $address): ?array
    {
        try {
            $userAgent = sprintf(
                '%s Attendance/1.0 (%s)',
                (string) config('app.name', 'Laravel'),
                (string) config('app.url', 'http://localhost')
            );

            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => $userAgent])
                ->get(self::ENDPOINT, [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'vn',
                    'accept-language' => 'vi',
                ]);

            if (! $response->successful()) {
                Log::warning('Geocoding HTTP lỗi', ['status' => $response->status()]);
                return null;
            }

            $results = $response->json();

            if (empty($results[0]['lat']) || empty($results[0]['lon'])) {
                Log::warning('Geocoding không có kết quả', ['address' => $address]);
                return null;
            }

            return [
                'lat' => (float) $results[0]['lat'],
                'lng' => (float) $results[0]['lon'],
            ];
        } catch (\Throwable $e) {
            Log::warning('Geocoding ngoại lệ: ' . $e->getMessage());
            return null;
        }
    }
}
