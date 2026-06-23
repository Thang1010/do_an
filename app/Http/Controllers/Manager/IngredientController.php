<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\NguyenLieu;
use App\Models\NguoiDung;
use App\Models\YeuCauNguyenLieu;
use App\Notifications\IngredientApprovalRequestNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class IngredientController extends Controller
{
    private const UNIT_OPTIONS = ['gram', 'gói', 'hộp', 'ml', 'chai'];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $purpose = trim((string) $request->input('muc_dich_su_dung', ''));

        $ingredients = NguyenLieu::query()
            ->dangSuDung()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where('ten_nguyen_lieu', 'like', "%{$search}%");
            })
            ->when($purpose !== '', function (Builder $query) use ($purpose) {
                $query->where('muc_dich_su_dung', $purpose);
            })
            ->orderBy('ten_nguyen_lieu')
            ->paginate(20)
            ->withQueryString();

        return view('manager.ingredients.index', [
            'ingredients' => $ingredients,
            'search' => $search,
            'purpose' => $purpose,
            'purposeOptions' => $this->purposeOptions(),
            'isStoreOwner' => $this->isStoreOwner(),
        ]);
    }

    public function create()
    {
        return view('manager.ingredients.create', [
            'isStoreOwner' => $this->isStoreOwner(),
            'unitOptions' => $this->unitOptions(),
            'purposeOptions' => $this->purposeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ingredients' => 'required|array|min:1',
            'ingredients.*.ten_nguyen_lieu' => 'required|string|max:150|distinct|unique:nguyen_lieu,ten_nguyen_lieu',
            'ingredients.*.don_vi_tinh' => ['nullable', Rule::in($this->unitOptions())],
            'ingredients.*.muc_dich_su_dung' => 'nullable|string|max:120',
            'ingredients.*.muc_dich_su_dung_khac' => 'nullable|string|max:120',
        ], [
            'ingredients.*.ten_nguyen_lieu.unique' => 'Tên nguyên liệu này đã tồn tại trong hệ thống.',
            'ingredients.*.ten_nguyen_lieu.required' => 'Vui lòng nhập tên nguyên liệu.',
            'ingredients.*.ten_nguyen_lieu.distinct' => 'Tên nguyên liệu trong danh sách bị trùng lặp.',
        ]);

        $items = $this->normalizeIngredients((array) $request->input('ingredients', []));
        if ($items->isEmpty()) {
            return back()->withInput()->with('error', 'Vui lòng nhập ít nhất một nguyên liệu hợp lệ.');
        }

        if ($this->isStoreOwner()) {
            [$createdCount, $skippedCount] = $this->createIngredientsDirectly($items);

            return redirect()->route('manager.ingredients.index')
                ->with('success', "Đã thêm trực tiếp {$createdCount} nguyên liệu.")
                ->with($skippedCount > 0 ? 'warning' : 'success', $skippedCount > 0 ? "Có {$skippedCount} nguyên liệu bị bỏ qua do trùng tên." : "Thao tác hoàn tất.");
        }

        $actor = $this->currentUser();
        if (! $actor) {
            abort(403, 'Bạn cần đăng nhập để thực hiện thao tác này.');
        }

        $ingredientRequest = YeuCauNguyenLieu::create([
            'cua_hang_id' => $this->currentStoreId(),
            'nguoi_gui_id' => $actor->id,
            'trang_thai' => 'cho_xac_nhan',
            'du_lieu' => $items->values()->all(),
            'ghi_chu' => null,
        ]);

        $this->notifyStoreOwnersForIngredientRequest($ingredientRequest, $actor);

        return redirect()->route('manager.ingredients.requests.index')
            ->with('success', 'Đã gửi yêu cầu thêm nguyên liệu lên chủ cửa hàng để xác nhận.');
    }

    public function edit(int $id)
    {
        $this->ensureStoreOwner();

        $ingredient = NguyenLieu::findOrFail($id);

        return view('manager.ingredients.edit', [
            'ingredient' => $ingredient,
            'unitOptions' => $this->unitOptions(),
            'purposeOptions' => $this->purposeOptions(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->ensureStoreOwner();

        $request->validate([
            'ten_nguyen_lieu' => 'required|string|max:150|unique:nguyen_lieu,ten_nguyen_lieu,' . $id,
            'don_vi_tinh' => ['required', Rule::in($this->unitOptions())],
            'muc_dich_su_dung' => 'required|string|max:120',
            'muc_dich_su_dung_khac' => 'nullable|string|max:120',
        ]);

        $purpose = $this->normalizePurposeValue(
            $request->input('muc_dich_su_dung'),
            $request->input('muc_dich_su_dung_khac')
        );

        if (! $purpose) {
            return back()->withErrors([
                'muc_dich_su_dung' => 'Vui lòng chọn mục đích sử dụng.',
            ])->withInput();
        }

        $ingredient = NguyenLieu::findOrFail($id);
        $ingredient->update([
            'ten_nguyen_lieu' => trim((string) $request->input('ten_nguyen_lieu')),
            'don_vi_tinh' => trim((string) $request->input('don_vi_tinh')),
            'muc_dich_su_dung' => $purpose,
        ]);

        return redirect()->route('manager.ingredients.index')
            ->with('success', 'Đã cập nhật nguyên liệu thành công.');
    }

    public function destroy(int $id)
    {
        $this->ensureStoreOwner();

        $ingredient = NguyenLieu::findOrFail($id);
        $ingredient->delete();

        return redirect()->route('manager.ingredients.index')
            ->with('success', 'Đã xóa nguyên liệu thành công.');
    }

    public function requestsIndex(Request $request)
    {
        $status = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $query = YeuCauNguyenLieu::query()
            ->with('nguoiGui', 'nguoiDuyet')
            ->when($status !== '' && in_array($status, ['cho_xac_nhan', 'da_duyet', 'tu_choi'], true), function (Builder $builder) use ($status) {
                $builder->where('trang_thai', $status);
            })
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $subQuery) use ($search) {
                    $subQuery->whereHas('nguoiGui', function (Builder $userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })->orWhere('ghi_chu', 'like', "%{$search}%");
                });
            });

        if (! $this->isStoreOwner()) {
            $actorId = $this->currentUser()?->id;
            $query->where('nguoi_gui_id', $actorId);
        } else {
            $this->applyStoreScopeToRequests($query);
        }

        $requests = $query->latest('created_at')->paginate(20)->withQueryString();

        return view('manager.ingredients.requests.index', [
            'requests' => $requests,
            'status' => $status,
            'search' => $search,
            'isStoreOwner' => $this->isStoreOwner(),
        ]);
    }

    public function requestShow(int $id)
    {
        $ingredientRequest = YeuCauNguyenLieu::with('nguoiGui', 'nguoiDuyet')->findOrFail($id);

        if (! $this->canViewRequest($ingredientRequest)) {
            abort(403, 'Bạn không có quyền truy cập yêu cầu này.');
        }

        return view('manager.ingredients.requests.show', [
            'ingredientRequest' => $ingredientRequest,
            'items' => collect((array) ($ingredientRequest->du_lieu ?? [])),
            'isStoreOwner' => $this->isStoreOwner(),
        ]);
    }

    public function storeRequest(Request $request)
    {
        return $this->store($request);
    }

    public function approveRequest(Request $request, int $id)
    {
        $this->ensureStoreOwner();

        $ingredientRequest = YeuCauNguyenLieu::findOrFail($id);
        if ($ingredientRequest->trang_thai !== 'cho_xac_nhan') {
            return back()->with('info', 'Yêu cầu này đã được xử lý trước đó.');
        }

        $items = collect((array) ($ingredientRequest->du_lieu ?? []));
        [$createdCount, $skippedCount] = $this->createIngredientsDirectly($items);

        $ingredientRequest->update([
            'trang_thai' => 'da_duyet',
            'nguoi_duyet_id' => $this->currentUser()?->id,
            'duyet_luc' => now(),
            'ghi_chu' => $request->filled('review_note') ? trim((string) $request->input('review_note')) : $ingredientRequest->ghi_chu,
        ]);

        $message = "Đã duyệt yêu cầu. Tạo mới {$createdCount} nguyên liệu.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} nguyên liệu bị bỏ qua do trùng tên.";
        }

        return redirect()->route('manager.ingredients.requests.index')
            ->with('success', $message);
    }

    public function rejectRequest(Request $request, int $id)
    {
        $this->ensureStoreOwner();

        $request->validate([
            'review_note' => 'nullable|string|max:1000',
        ]);

        $ingredientRequest = YeuCauNguyenLieu::findOrFail($id);
        if ($ingredientRequest->trang_thai !== 'cho_xac_nhan') {
            return back()->with('info', 'Yêu cầu này đã được xử lý trước đó.');
        }

        $ingredientRequest->update([
            'trang_thai' => 'tu_choi',
            'nguoi_duyet_id' => $this->currentUser()?->id,
            'tu_choi_luc' => now(),
            'ghi_chu' => $request->filled('review_note') ? trim((string) $request->input('review_note')) : $ingredientRequest->ghi_chu,
        ]);

        return redirect()->route('manager.ingredients.requests.index')
            ->with('success', 'Đã từ chối yêu cầu thêm nguyên liệu.');
    }

    private function normalizeIngredients(array $rows): Collection
    {
        $errors = [];
        $items = collect();

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['ten_nguyen_lieu'] ?? ''));
            $unit = trim((string) ($row['don_vi_tinh'] ?? ''));
            $purpose = $this->normalizePurposeValue(
                $row['muc_dich_su_dung'] ?? null,
                $row['muc_dich_su_dung_khac'] ?? null
            );

            $hasAny = $name !== '' || $unit !== '' || $purpose !== null;
            if (! $hasAny) {
                continue;
            }

            if ($name === '' || $unit === '' || $purpose === null) {
                $errors["ingredients.{$index}.muc_dich_su_dung"] = 'Vui lòng nhập đầy đủ tên, đơn vị và mục đích sử dụng.';
                continue;
            }

            $items->push([
                'ten_nguyen_lieu' => $name,
                'don_vi_tinh' => $unit,
                'muc_dich_su_dung' => $purpose,
            ]);
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }

        return $items
            ->unique(function (array $row) {
                return mb_strtolower($row['ten_nguyen_lieu'])
                    . '|' . mb_strtolower($row['don_vi_tinh'])
                    . '|' . mb_strtolower($row['muc_dich_su_dung']);
            })
            ->values();
    }

    private function createIngredientsDirectly(Collection $items): array
    {
        $createdCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use ($items, &$createdCount, &$skippedCount) {
            foreach ($items as $item) {
                $exists = NguyenLieu::query()
                    ->whereRaw('LOWER(ten_nguyen_lieu) = ?', [mb_strtolower((string) $item['ten_nguyen_lieu'])])
                    ->exists();

                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                NguyenLieu::create([
                    'ten_nguyen_lieu' => $item['ten_nguyen_lieu'],
                    'don_vi_tinh' => $item['don_vi_tinh'],
                    'muc_dich_su_dung' => $item['muc_dich_su_dung'],
                    'created_at' => now(),
                ]);

                $createdCount++;
            }
        });

        return [$createdCount, $skippedCount];
    }

    private function notifyStoreOwnersForIngredientRequest(YeuCauNguyenLieu $ingredientRequest, NguoiDung $requester): void
    {
        $owners = NguoiDung::query()
            ->where('vai_tro', 'chủ cửa hàng')
            ->where('trang_thai', 'hoạt động')
            ->when($ingredientRequest->cua_hang_id, function (Builder $query) use ($ingredientRequest) {
                $query->where('cua_hang_id', $ingredientRequest->cua_hang_id);
            })
            ->get();

        if ($owners->isEmpty()) {
            return;
        }

        $owners->each->notify(new IngredientApprovalRequestNotification($ingredientRequest, $requester));
    }

    private function isStoreOwner(): bool
    {
        return $this->currentUser()?->vai_tro === 'chủ cửa hàng';
    }

    private function purposeOptions(): array
    {
        return NguyenLieu::query()
            ->whereNotNull('muc_dich_su_dung')
            ->where('muc_dich_su_dung', '!=', '')
            ->orderBy('muc_dich_su_dung')
            ->pluck('muc_dich_su_dung')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePurposeValue(?string $selected, ?string $custom): ?string
    {
        $selected = trim((string) $selected);
        $custom = trim((string) $custom);

        if ($selected === '__other__') {
            $selected = $custom;
        }

        return $selected !== '' ? $selected : null;
    }

    private function ensureStoreOwner(): void
    {
        if (! $this->isStoreOwner()) {
            abort(403, 'Chỉ chủ cửa hàng mới có quyền thao tác chức năng này.');
        }
    }

    private function currentUser(): ?NguoiDung
    {
        return Auth::guard('nguoi_dung')->user() ?? Auth::user();
    }

    private function currentStoreId(): ?int
    {
        return $this->currentUser()?->cua_hang_id ?: $this->currentUser()?->hoSoQuanLy?->cua_hang_id;
    }

    private function applyStoreScopeToRequests(Builder $query): Builder
    {
        $storeId = $this->currentStoreId();
        if ($storeId) {
            $query->where('cua_hang_id', $storeId);
        }

        return $query;
    }

    private function canViewRequest(YeuCauNguyenLieu $ingredientRequest): bool
    {
        $actor = $this->currentUser();
        if (! $actor) {
            return false;
        }

        if ($this->isStoreOwner()) {
            $storeId = $this->currentStoreId();
            if (! $storeId) {
                return true;
            }

            return (int) $ingredientRequest->cua_hang_id === (int) $storeId;
        }

        return (int) $ingredientRequest->nguoi_gui_id === (int) $actor->id;
    }

    private function unitOptions(): array
    {
        return self::UNIT_OPTIONS;
    }
}
