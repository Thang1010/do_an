<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\DonHang;
use App\Models\ThanhToan;
use App\Services\TableStatusService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SepayWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        if (! $this->isAuthorized($request)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        if (! $this->isIncomingTransaction($data)) {
            return response()->json(['message' => 'Ignored'], 200);
        }

        $content = $this->extractString($data, [
            'content', 'description', 'transfer_content', 'transferContent', 'message', 'note', 'addInfo', 'remark',
        ]);

        $orderCode = $this->extractOrderCode($data, $content);
        if (! $orderCode) {
            Log::warning('Sepay webhook ignored: missing order code.', [
                'transaction_id' => $this->extractString($data, ['transaction_id', 'transactionId', 'trans_id', 'txid', 'id']),
            ]);
            return response()->json(['message' => 'Ignored'], 200);
        }

        $order = DonHang::query()->where('ma_don_hang', $orderCode)->first();
        if (! $order) {
            $orderId = $this->extractOrderIdFromCode($orderCode);
            if ($orderId) {
                $order = DonHang::query()->find($orderId);
            }
        }

        if (! $order) {
            Log::warning('Sepay webhook ignored: order not found.', [
                'order_code' => $orderCode,
            ]);
            return response()->json(['message' => 'Ignored'], 200);
        }

        if ($order->trang_thai_thanh_toan === 'đã thanh toán') {
            return response()->json(['message' => 'Already paid'], 200);
        }

        $amount = $this->extractAmount($data);
        if ($amount !== null && (float) $amount + 0.01 < (float) ($order->tong_tien ?? 0)) {
            Log::warning('Sepay webhook ignored: amount mismatch.', [
                'order_code' => $order->ma_don_hang,
                'expected' => (float) ($order->tong_tien ?? 0),
                'received' => $amount,
            ]);
            return response()->json(['message' => 'Ignored'], 200);
        }

        $paymentPayload = $this->compactPayload([
            'phuong_thuc' => 'chuyển khoản',
            'so_tien' => $amount ?? (float) ($order->tong_tien ?? 0),
            'trang_thai' => 'đã thanh toán',
            'thanh_toan_luc' => $this->extractPaidAt($data) ?? now(),
            'noi_dung_chuyen_khoan' => $content,
            'ma_giao_dich' => $this->extractString($data, ['transaction_id', 'transactionId', 'trans_id', 'txid', 'id']),
            'ma_thanh_toan' => $this->extractString($data, ['payment_id', 'paymentId']),
            'ten_ngan_hang' => $this->extractString($data, ['bank_name', 'bankName', 'bank']),
            'ma_ngan_hang' => $this->extractString($data, ['bank_code', 'bankCode', 'bankId']),
            'so_tai_khoan' => $this->extractString($data, ['account_no', 'accountNo', 'bank_account', 'account']),
            'ten_chu_tai_khoan' => $this->extractString($data, ['account_name', 'accountName', 'owner', 'account_holder']),
            'ghi_chu' => $this->extractString($data, ['note_detail', 'noteDetail']),
        ]);

        $payment = ThanhToan::query()
            ->where('don_hang_id', $order->id)
            ->latest('id')
            ->first();

        if ($payment) {
            $payment->update($paymentPayload);
        } else {
            ThanhToan::create(array_merge(['don_hang_id' => $order->id], $paymentPayload));
        }

        if (in_array($order->trang_thai_don, ['chờ xác nhận', 'cho_xac_nhan'], true)) {
            $order->update([
                'trang_thai_don' => 'đã xác nhận',
            ]);
        }

        TableStatusService::refreshForTable($order->ban_an_id);

        return response()->json(['message' => 'OK'], 200);
    }

    private function isAuthorized(Request $request): bool
    {
        $expectedKey = (string) config('services.sepay.api_key');
        if ($expectedKey === '') {
            Log::warning('Sepay webhook blocked: missing SEPAY_API_KEY.');
            return false;
        }

        $headerKey = $request->header('X-SePay-Api-Key')
            ?? $request->header('X-Api-Key')
            ?? $request->header('X-Auth-Token');

        if (! $headerKey) {
            $authHeader = (string) $request->header('Authorization', '');
            if (str_starts_with($authHeader, 'Bearer ')) {
                $headerKey = substr($authHeader, 7);
            }
        }

        $provided = $headerKey ?: (string) $request->input('api_key', $request->input('key', ''));
        if ($provided === '') {
            return false;
        }

        return hash_equals($expectedKey, $provided);
    }

    private function isIncomingTransaction(array $data): bool
    {
        $direction = strtolower((string) ($data['direction'] ?? $data['type'] ?? $data['transaction_type'] ?? ''));
        if (in_array($direction, ['out', 'debit', 'withdraw', 'withdrawal', 'spend'], true)) {
            return false;
        }

        $isCredit = $data['is_credit'] ?? $data['isCredit'] ?? null;
        if (is_bool($isCredit)) {
            return $isCredit;
        }

        $amount = $this->extractAmount($data);
        if ($amount !== null && $amount < 0) {
            return false;
        }

        return true;
    }

    private function extractOrderCode(array $data, ?string $content): ?string
    {
        $direct = $this->extractString($data, ['order_code', 'orderCode', 'reference', 'ref', 'code']);
        if ($direct) {
            return $direct;
        }

        if ($content) {
            if (preg_match('/ORD-[A-Z0-9]+/i', $content, $match)) {
                return strtoupper($match[0]);
            }
            if (preg_match('/DON\s*(\d+)/i', $content, $match)) {
                return 'DON' . $match[1];
            }
        }

        return null;
    }

    private function extractOrderIdFromCode(string $code): ?int
    {
        if (preg_match('/DON\s*(\d+)/i', $code, $match)) {
            return (int) $match[1];
        }

        return null;
    }

    private function extractAmount(array $data): ?float
    {
        foreach (['amount', 'amount_in', 'amountIn', 'paid_amount', 'value', 'total_amount', 'money'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $raw = $data[$field];
            if (is_numeric($raw)) {
                return (float) $raw;
            }

            $string = $this->normalizeNumberString($raw);
            if ($string !== null) {
                return (float) $string;
            }
        }

        return null;
    }

    private function extractPaidAt(array $data): ?Carbon
    {
        foreach (['paid_at', 'paidAt', 'transaction_time', 'transactionTime', 'time', 'created_at', 'occurred_at'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if ($value instanceof Carbon) {
                return $value;
            }

            $value = $this->extractString($data, [$field]);
            if (! $value) {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function extractString(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            if (is_array($value)) {
                $value = implode(' ', array_filter($value, 'strlen'));
            }

            if (is_scalar($value)) {
                $value = trim((string) $value);
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return null;
    }

    private function normalizeNumberString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = preg_replace('/[^0-9\-\.]/', '', (string) $value);
        if ($string === '' || $string === '-') {
            return null;
        }

        return $string;
    }

    private function compactPayload(array $payload): array
    {
        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }
}
