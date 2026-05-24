<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CuaHang;
use App\Models\NguoiDung;
use App\Notifications\CustomerPasswordResetRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $storeId = Auth::user()?->cua_hang_id;
        $search = trim((string) $request->query('search', ''));
        $createdDate = $request->query('created_date');

        $customersQuery = NguoiDung::query()
            ->where('vai_tro', 'khách hàng')
            ->whereIn('trang_thai', ['ngưng hoạt động', 'hoạt động'])
            ->when($storeId, fn($q) => $q->where('cua_hang_id', $storeId));

        if ($search !== '') {
            $customersQuery->where(function ($query) use ($search) {
                $query->where('email', 'like', '%' . $search . '%')
                    ->orWhere('so_dien_thoai', 'like', '%' . $search . '%');
            });
        }

        if (!empty($createdDate)) {
            $customersQuery->whereDate('created_at', $createdDate);
        }

        $customers = $customersQuery
            ->orderByRaw("CASE WHEN trang_thai = 'ngưng hoạt động' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $filters = [
            'search' => $search,
            'created_date' => $createdDate,
        ];

        return view('staff.customers.index', compact('customers', 'filters'));
    }

    public function requestPasswordReset(int $id)
    {
        $requester = Auth::user();
        $customer = NguoiDung::query()
            ->where('vai_tro', 'khách hàng')
            ->findOrFail($id);

        $store = CuaHang::query()
            ->when($requester?->cua_hang_id, fn($q) => $q->where('id', $requester->cua_hang_id))
            ->first();

        $recipients = NguoiDung::query()
            ->whereIn('vai_tro', ['quản lý', 'chủ cửa hàng'])
            ->when($requester?->cua_hang_id, fn($q) => $q->where('cua_hang_id', $requester->cua_hang_id))
            ->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'Không tìm thấy quản lý hoặc chủ cửa hàng để gửi yêu cầu.');
        }

        Notification::send($recipients, new CustomerPasswordResetRequestNotification($customer, $requester, $store));

        return back()->with('success', 'Đã gửi yêu cầu cấp lại mật khẩu cho quản lý/chủ cửa hàng.');
    }
}
