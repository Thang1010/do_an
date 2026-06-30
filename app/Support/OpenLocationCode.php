<?php

namespace App\Support;

/**
 * Giải mã/ mã hoá Plus Code (Open Location Code) của Google — thuần thuật toán,
 * KHÔNG cần mạng/ API key. Dùng để chấp nhận mã vị trí dán từ Google Maps
 * (vd "2G7R+M3P Hòa Lạc, Hà Nội") khi cấu hình vị trí quán.
 *
 * Port từ bản tham chiếu chính thức: github.com/google/open-location-code (Python).
 */
class OpenLocationCode
{
    private const SEPARATOR = '+';
    private const SEPARATOR_POSITION = 8;
    private const PADDING = '0';
    private const ALPHABET = '23456789CFGHJMPQRVWX';
    private const ENCODING_BASE = 20;
    private const LATITUDE_MAX = 90;
    private const LONGITUDE_MAX = 180;
    private const MAX_DIGIT_COUNT = 15;
    private const PAIR_CODE_LENGTH = 10;
    private const PAIR_FIRST_PLACE_VALUE = 160000;   // 20^4
    private const PAIR_PRECISION = 8000;             // 20^3
    private const GRID_CODE_LENGTH = 5;
    private const GRID_COLUMNS = 4;
    private const GRID_ROWS = 5;
    private const GRID_LAT_FIRST_PLACE_VALUE = 625;  // 5^4
    private const GRID_LNG_FIRST_PLACE_VALUE = 256;  // 4^4
    private const FINAL_LAT_PRECISION = 25000000;    // 8000 * 5^5
    private const FINAL_LNG_PRECISION = 8192000;     // 8000 * 4^5

    public function isValid(string $code): bool
    {
        if ($code === '' || substr_count($code, self::SEPARATOR) !== 1) {
            return false;
        }
        $sep = strpos($code, self::SEPARATOR);
        if ($sep > self::SEPARATOR_POSITION || $sep % 2 === 1) {
            return false;
        }

        // Ký tự đứng sau dấu '+' (nếu có) tối đa GRID? Bản rút gọn: kiểm tra ký tự hợp lệ.
        $upper = strtoupper($code);
        for ($i = 0, $n = strlen($upper); $i < $n; $i++) {
            $c = $upper[$i];
            if ($c === self::SEPARATOR || $c === self::PADDING) {
                continue;
            }
            if (strpos(self::ALPHABET, $c) === false) {
                return false;
            }
        }

        return true;
    }

    public function isFull(string $code): bool
    {
        return $this->isValid($code) && strpos($code, self::SEPARATOR) === self::SEPARATOR_POSITION;
    }

    public function isShort(string $code): bool
    {
        return $this->isValid($code) && strpos($code, self::SEPARATOR) < self::SEPARATOR_POSITION;
    }

    /**
     * Mã hoá toạ độ thành Plus Code. $codeLength: 10 (pair) hoặc 11..15 (có grid).
     */
    public function encode(float $latitude, float $longitude, int $codeLength = self::PAIR_CODE_LENGTH): string
    {
        $codeLength = min($codeLength, self::MAX_DIGIT_COUNT);
        $latitude = $this->clipLatitude($latitude);
        $longitude = $this->normalizeLongitude($longitude);
        if ($latitude === (float) self::LATITUDE_MAX) {
            $latitude -= $this->latPrecisionForLength($codeLength);
        }

        $latVal = (int) round(($latitude + self::LATITUDE_MAX) * self::FINAL_LAT_PRECISION * 1e6);
        $latVal = intdiv($latVal, 1000000);
        $lngVal = (int) round(($longitude + self::LONGITUDE_MAX) * self::FINAL_LNG_PRECISION * 1e6);
        $lngVal = intdiv($lngVal, 1000000);

        $code = '';
        if ($codeLength > self::PAIR_CODE_LENGTH) {
            for ($i = 0; $i < self::GRID_CODE_LENGTH; $i++) {
                $latDigit = $latVal % self::GRID_ROWS;
                $lngDigit = $lngVal % self::GRID_COLUMNS;
                $ndx = $latDigit * self::GRID_COLUMNS + $lngDigit;
                $code = self::ALPHABET[$ndx] . $code;
                $latVal = intdiv($latVal, self::GRID_ROWS);
                $lngVal = intdiv($lngVal, self::GRID_COLUMNS);
            }
        } else {
            $latVal = intdiv($latVal, self::GRID_ROWS ** self::GRID_CODE_LENGTH);
            $lngVal = intdiv($lngVal, self::GRID_COLUMNS ** self::GRID_CODE_LENGTH);
        }

        for ($i = 0; $i < self::PAIR_CODE_LENGTH / 2; $i++) {
            $code = self::ALPHABET[$lngVal % self::ENCODING_BASE] . $code;
            $code = self::ALPHABET[$latVal % self::ENCODING_BASE] . $code;
            $latVal = intdiv($latVal, self::ENCODING_BASE);
            $lngVal = intdiv($lngVal, self::ENCODING_BASE);
        }

        $code = substr($code, 0, self::SEPARATOR_POSITION) . self::SEPARATOR . substr($code, self::SEPARATOR_POSITION);

        if ($codeLength >= self::SEPARATOR_POSITION) {
            return substr($code, 0, $codeLength + 1);
        }

        return substr($code, 0, $codeLength)
            . str_repeat(self::PADDING, self::SEPARATOR_POSITION - $codeLength)
            . self::SEPARATOR;
    }

    /**
     * Giải mã mã ĐẦY ĐỦ. Trả ['lat','lng'] là tâm ô.
     */
    public function decodeCenter(string $code): array
    {
        $clean = strtoupper(str_replace([self::SEPARATOR, self::PADDING], '', $code));

        $normalLat = -self::LATITUDE_MAX * self::PAIR_PRECISION;
        $normalLng = -self::LONGITUDE_MAX * self::PAIR_PRECISION;
        $gridLat = 0;
        $gridLng = 0;

        $digits = min(strlen($clean), self::PAIR_CODE_LENGTH);
        $pv = self::PAIR_FIRST_PLACE_VALUE;
        for ($i = 0; $i < $digits; $i += 2) {
            $normalLat += strpos(self::ALPHABET, $clean[$i]) * $pv;
            $normalLng += strpos(self::ALPHABET, $clean[$i + 1]) * $pv;
            if ($i < $digits - 2) {
                $pv = intdiv($pv, self::ENCODING_BASE);
            }
        }
        $latPrecision = $pv / self::PAIR_PRECISION;
        $lngPrecision = $pv / self::PAIR_PRECISION;

        if (strlen($clean) > self::PAIR_CODE_LENGTH) {
            $rowpv = self::GRID_LAT_FIRST_PLACE_VALUE;
            $colpv = self::GRID_LNG_FIRST_PLACE_VALUE;
            $digits = min(strlen($clean), self::MAX_DIGIT_COUNT);
            for ($i = self::PAIR_CODE_LENGTH; $i < $digits; $i++) {
                $digitVal = strpos(self::ALPHABET, $clean[$i]);
                $row = intdiv($digitVal, self::GRID_COLUMNS);
                $col = $digitVal % self::GRID_COLUMNS;
                $gridLat += $row * $rowpv;
                $gridLng += $col * $colpv;
                if ($i < $digits - 1) {
                    $rowpv = intdiv($rowpv, self::GRID_ROWS);
                    $colpv = intdiv($colpv, self::GRID_COLUMNS);
                }
            }
            $latPrecision = $rowpv / self::FINAL_LAT_PRECISION;
            $lngPrecision = $colpv / self::FINAL_LNG_PRECISION;
        }

        $latLo = $normalLat / self::PAIR_PRECISION + $gridLat / self::FINAL_LAT_PRECISION;
        $lngLo = $normalLng / self::PAIR_PRECISION + $gridLng / self::FINAL_LNG_PRECISION;

        return [
            'lat' => $latLo + $latPrecision / 2,
            'lng' => $lngLo + $lngPrecision / 2,
        ];
    }

    /**
     * Khôi phục mã NGẮN thành mã đầy đủ dựa trên một điểm tham chiếu gần đó
     * (thường là toạ độ của địa danh đi kèm), rồi trả ['lat','lng'].
     */
    public function recoverAndDecode(string $shortCode, float $referenceLatitude, float $referenceLongitude): ?array
    {
        if ($this->isFull($shortCode)) {
            return $this->decodeCenter($shortCode);
        }
        if (! $this->isShort($shortCode)) {
            return null;
        }

        $referenceLatitude = $this->clipLatitude($referenceLatitude);
        $referenceLongitude = $this->normalizeLongitude($referenceLongitude);

        $digitsToRecover = self::SEPARATOR_POSITION - strpos($shortCode, self::SEPARATOR);
        $resolution = self::ENCODING_BASE ** (2 - ($digitsToRecover / 2));
        $halfResolution = $resolution / 2.0;

        $prefix = substr($this->encode($referenceLatitude, $referenceLongitude), 0, $digitsToRecover);
        $center = $this->decodeCenter($prefix . strtoupper($shortCode));
        $lat = $center['lat'];
        $lng = $center['lng'];

        if ($referenceLatitude + $halfResolution < $lat && $lat - $resolution >= -self::LATITUDE_MAX) {
            $lat -= $resolution;
        } elseif ($referenceLatitude - $halfResolution > $lat && $lat + $resolution <= self::LATITUDE_MAX) {
            $lat += $resolution;
        }
        if ($referenceLongitude + $halfResolution < $lng) {
            $lng -= $resolution;
        } elseif ($referenceLongitude - $halfResolution > $lng) {
            $lng += $resolution;
        }

        return ['lat' => $lat, 'lng' => $lng];
    }

    private function clipLatitude(float $latitude): float
    {
        return max(-self::LATITUDE_MAX, min(self::LATITUDE_MAX, $latitude));
    }

    private function normalizeLongitude(float $longitude): float
    {
        while ($longitude < -self::LONGITUDE_MAX) {
            $longitude += 360;
        }
        while ($longitude >= self::LONGITUDE_MAX) {
            $longitude -= 360;
        }

        return $longitude;
    }

    private function latPrecisionForLength(int $codeLength): float
    {
        if ($codeLength <= self::PAIR_CODE_LENGTH) {
            return self::ENCODING_BASE ** (2 - intdiv($codeLength, 2));
        }

        return (self::ENCODING_BASE ** -3) / (self::GRID_ROWS ** ($codeLength - self::PAIR_CODE_LENGTH));
    }
}
