<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidatorInstance;
use App\Models\KichCo;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Các định dạng ảnh server (thư viện GD) thật sự xử lý/lưu được.
     * Định dạng nào GD không hỗ trợ trên server này sẽ không cho phép → tránh lỗi khi lưu.
     */
    private function supportedImageExtensions(): array
    {
        $gd = function_exists('gd_info') ? gd_info() : [];
        $exts = ['jpg', 'jpeg', 'png', 'gif']; // GD luôn hỗ trợ
        if (!empty($gd['WebP Support'])) {
            $exts[] = 'webp';
        }
        if (!empty($gd['AVIF Support'])) {
            $exts[] = 'avif';
        }
        if (!empty($gd['BMP Support'])) {
            $exts[] = 'bmp';
        }
        return $exts;
    }

    /** Danh sách hiển thị cho người dùng, vd "JPG, PNG, GIF, WEBP". */
    private function supportedImageLabel(): string
    {
        $exts = array_values(array_diff($this->supportedImageExtensions(), ['jpeg']));
        return implode(', ', array_map('strtoupper', $exts));
    }

    public function rules(): array
    {
        return [
            'ten_san_pham'              => 'required|string|max:200',
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
        return [
            'ten_san_pham.required'       => 'Vui lòng nhập tên sản phẩm.',
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
}
