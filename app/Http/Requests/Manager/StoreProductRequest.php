<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as ValidatorInstance;
use App\Models\CongThucSanPham;
use App\Models\KichCo;
use App\Models\SanPham;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * S3 là object storage nên lưu được mọi định dạng ảnh.
     * Cho phép tất cả các định dạng ảnh phổ biến; định dạng nào GD không nén được
     * sẽ được lưu nguyên file gốc lên S3 (xử lý ở ProductController::storeProductImage).
     */
    private function supportedImageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'tiff', 'tif', 'svg', 'heic', 'heif', 'ico'];
    }

    /** Danh sách hiển thị cho người dùng (gọn, bỏ alias jpeg/tif/heif). */
    private function supportedImageLabel(): string
    {
        $exts = array_values(array_diff($this->supportedImageExtensions(), ['jpeg', 'tif', 'heif']));
        return implode(', ', array_map('strtoupper', $exts));
    }

    public function rules(): array
    {
        return [
            'ten_san_pham'              => [
                'required',
                'string',
                'max:200',
                // Chặn trùng tên sản phẩm; khi sửa thì bỏ qua chính bản ghi hiện tại.
                Rule::unique('san_pham', 'ten_san_pham')->ignore($this->route('id')),
            ],
            'mo_ta'                     => 'nullable|string|max:150',
            'danh_muc_id'               => 'required|exists:danh_muc,id',
            'gia_goc'                   => 'required|numeric|min:0',
            'gia_khuyen_mai'            => 'nullable|numeric|min:0',
            'trang_thai_ban'            => 'required|in:dang_ban,ngung_ban',
            'co_cong_thuc'              => 'nullable|in:0,1',
            'nhiet_do'                  => 'nullable|array',
            'nhiet_do.*'                => 'in:nóng,lạnh',
            'anh_chinh'                 => 'nullable|mimes:' . implode(',', $this->supportedImageExtensions()) . '|max:5120',
            'sizes'                     => 'nullable|array',
            'sizes.*.kich_co_id'        => 'required',
            'sizes.*.he_so_gia'         => 'nullable|numeric|min:1',
            'sizes.*.ma_kich_co_moi'    => 'nullable|string|max:20',
            'sizes.*.ten_kich_co_moi'   => 'nullable|string|max:50',
            'sizes.*.mo_ta_kich_co_moi' => 'nullable|string|max:500',
            'recipes'                   => 'nullable|array',
            'recipes.*.nguyen_lieu_id'  => 'nullable|exists:nguyen_lieu,id',
            'recipes.*.so_luong_can'    => 'nullable|numeric|min:0.001',
        ];
    }

    public function messages(): array
    {
        $tenSanPham = trim((string) $this->input('ten_san_pham'));

        return [
            'ten_san_pham.required'       => 'Vui lòng nhập tên sản phẩm.',
            'ten_san_pham.unique'         => "Sản phẩm \"{$tenSanPham}\" đã tồn tại.",
            'mo_ta.max'                   => 'Mô tả không được vượt quá 150 ký tự.',
            'danh_muc_id.required'        => 'Vui lòng chọn danh mục.',
            'gia_goc.required'            => 'Vui lòng nhập giá sản phẩm.',
            'sizes.*.kich_co_id.required' => 'Vui lòng chọn kích cỡ.',
            'anh_chinh.mimes'             => 'Chỉ được đăng ảnh định dạng: ' . $this->supportedImageLabel() . '.',
            'anh_chinh.max'               => 'Ảnh không được vượt quá 5MB.',
        ];
    }

    /**
     * Configure additional after-validation hooks.
     * Mirrors the same after() logic used in ProductController::validateProductRequest().
     */
    public function withValidator(ValidatorInstance $validator): void
    {
        $validator->after(function (ValidatorInstance $validator) {
            // Validate recipes
            $seenIngredients = [];
            foreach ($this->input('recipes', []) as $index => $recipe) {
                $ingredientId = (string) ($recipe['nguyen_lieu_id'] ?? '');
                $qtyValue = $recipe['so_luong_can'] ?? null;
                $qtyText = is_string($qtyValue) ? trim($qtyValue) : $qtyValue;

                if ($ingredientId === '' && ($qtyText === null || $qtyText === '')) {
                    continue;
                }

                if ($ingredientId === '') {
                    $validator->errors()->add("recipes.$index.nguyen_lieu_id", 'Vui lòng chọn nguyên liệu.');
                    continue;
                }

                if (!is_numeric($qtyText) || (float) $qtyText <= 0) {
                    $validator->errors()->add("recipes.$index.so_luong_can", 'Số lượng tiêu hao phải lớn hơn 0.');
                }

                if (isset($seenIngredients[$ingredientId])) {
                    $validator->errors()->add("recipes.$index.nguyen_lieu_id", 'Nguyên liệu bị trùng trong công thức.');
                }

                $seenIngredients[$ingredientId] = true;
            }

            // Chặn trùng 100% công thức (cùng bộ nguyên liệu + định lượng) với sản phẩm khác.
            $this->validateDuplicateRecipe($validator);

            // Validate sizes
            foreach ($this->input('sizes', []) as $index => $size) {
                $kichCoId = (string) ($size['kich_co_id'] ?? '');

                if ($kichCoId === 'khac') {
                    $tenMoi = trim((string) ($size['ten_kich_co_moi'] ?? ''));
                    $maMoi  = trim((string) ($size['ma_kich_co_moi'] ?? ''));

                    // Trùng mã/tên không còn bị chặn: sẽ cập nhật (sửa) kích cỡ đã có theo giá trị mới.
                    if ($tenMoi === '') {
                        $validator->errors()->add("sizes.$index.ten_kich_co_moi", 'Vui lòng nhập tên kích cỡ mới.');
                    }
                    if ($maMoi === '') {
                        $validator->errors()->add("sizes.$index.ma_kich_co_moi", 'Vui lòng nhập mã kích cỡ mới.');
                    }
                } else {
                    $selectedId = (int) $kichCoId;
                    if ($selectedId <= 0 || !KichCo::whereKey($selectedId)->exists()) {
                        $validator->errors()->add("sizes.$index.kich_co_id", 'Kích cỡ không hợp lệ.');
                    }
                }
            }
        });
    }

    /**
     * Quét CSDL xem bộ công thức (nguyên liệu + định lượng) vừa nhập có trùng
     * 100% với một sản phẩm khác không. Chỉ áp dụng khi món "có công thức".
     */
    private function validateDuplicateRecipe(ValidatorInstance $validator): void
    {
        if (! $this->boolean('co_cong_thuc')) {
            return;
        }

        // Gộp công thức vừa nhập thành map [nguyen_lieu_id => so_luong]; nguyên liệu
        // trùng lấy dòng cuối để khớp với cách lưu ở ProductController::syncProductRecipes.
        $submitted = [];
        foreach ($this->input('recipes', []) as $recipe) {
            $id = (int) ($recipe['nguyen_lieu_id'] ?? 0);
            $qtyRaw = $recipe['so_luong_can'] ?? null;
            $qty = is_numeric($qtyRaw) ? (float) $qtyRaw : 0;
            if ($id > 0 && $qty > 0) {
                $submitted[$id] = $qty;
            }
        }

        if (empty($submitted)) {
            return;
        }

        $signature = $this->recipeSignature($submitted);
        $ignoreId = (int) $this->route('id');

        $others = CongThucSanPham::query()
            ->when($ignoreId > 0, fn ($q) => $q->where('san_pham_id', '!=', $ignoreId))
            ->get(['san_pham_id', 'nguyen_lieu_id', 'so_luong_can'])
            ->groupBy('san_pham_id');

        foreach ($others as $sanPhamId => $rows) {
            $set = [];
            foreach ($rows as $row) {
                $set[(int) $row->nguyen_lieu_id] = (float) $row->so_luong_can;
            }

            if ($this->recipeSignature($set) === $signature) {
                $tenTrung = SanPham::whereKey($sanPhamId)->value('ten_san_pham') ?? ('#' . $sanPhamId);
                $validator->errors()->add(
                    'recipes',
                    "Công thức này trùng 100% với sản phẩm \"{$tenTrung}\". Vui lòng điều chỉnh nguyên liệu hoặc định lượng."
                );
                break;
            }
        }
    }

    /**
     * Tạo chữ ký chuẩn hoá cho một bộ công thức để so sánh bằng nhau.
     * Sắp xếp theo nguyên liệu và làm tròn định lượng 3 chữ số (khớp độ chính xác CSDL)
     * để 20 và 20.000 được xem là giống nhau.
     */
    private function recipeSignature(array $set): string
    {
        ksort($set);

        $parts = [];
        foreach ($set as $id => $qty) {
            $parts[] = $id . ':' . rtrim(rtrim(number_format((float) $qty, 3, '.', ''), '0'), '.');
        }

        return implode('|', $parts);
    }
}
