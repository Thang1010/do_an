<?php

namespace App\Services;

use App\Support\OpenLocationCode;
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
     *
     * Thứ tự xử lý ô địa chỉ: (1) toạ độ lat,lng nhập trực tiếp → dùng luôn;
     * (2) Plus Code của Google (vd "2G7R+M3P Hòa Lạc, Hà Nội") → giải mã;
     * (3) còn lại là địa chỉ chữ → tra OpenStreetMap Nominatim (có cache).
     */
    public function geocode(?string $address): ?array
    {
        $address = trim((string) $address);
        if ($address === '') {
            return null;
        }

        // Cache theo chuỗi nhập, cache MỌI kết quả (lat/lng, Plus Code, Nominatim)
        // để Plus Code ngắn không phải gọi Nominatim lặp lại mỗi lần tra.
        $cacheKey = self::CACHE_PREFIX . md5($address);
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $coords = $this->resolveCoords($address);

        // Chỉ cache khi thành công, tránh "khoá cứng" lỗi tạm thời của dịch vụ.
        if ($coords !== null) {
            Cache::forever($cacheKey, $coords);
        }

        return $coords;
    }

    /**
     * Suy ra toạ độ từ chuỗi nhập theo thứ tự: lat,lng → Plus Code → Nominatim.
     */
    private function resolveCoords(string $address): ?array
    {
        return $this->parseLatLng($address)
            ?? $this->parsePlusCode($address)
            ?? $this->fetchFromNominatim($address);
    }

    /**
     * Nhận diện toạ độ "lat,lng" (hoặc "lat lng") nhập trực tiếp. Null nếu không phải.
     */
    private function parseLatLng(string $value): ?array
    {
        if (! preg_match('/^\s*(-?\d{1,3}(?:\.\d+)?)\s*[,;\s]\s*(-?\d{1,3}(?:\.\d+)?)\s*$/', $value, $m)) {
            return null;
        }

        $lat = (float) $m[1];
        $lng = (float) $m[2];
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return null;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * Nhận diện & giải mã Plus Code (Open Location Code). Hỗ trợ cả mã đầy đủ lẫn
     * mã ngắn kèm địa danh (mã ngắn cần geocode địa danh để lấy điểm tham chiếu).
     */
    private function parsePlusCode(string $value): ?array
    {
        if (! preg_match('/([23456789CFGHJMPQRVWX0]{2,8}\+[23456789CFGHJMPQRVWX]{2,3})/i', $value, $m)) {
            return null;
        }

        $code = strtoupper($m[1]);
        $olc = new OpenLocationCode();

        if ($olc->isFull($code)) {
            return $olc->decodeCenter($code);
        }

        if (! $olc->isShort($code)) {
            return null;
        }

        // Mã ngắn: cần điểm tham chiếu từ địa danh đi kèm (phần còn lại của chuỗi).
        $locality = trim(str_replace($m[1], '', $value), " \t\n\r,;");
        if ($locality === '') {
            return null;
        }

        $reference = $this->fetchFromNominatim($locality);
        if ($reference === null) {
            return null;
        }

        return $olc->recoverAndDecode($code, $reference['lat'], $reference['lng']);
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
