<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\NguyenLieu;
use App\Models\LichSuKho;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = NguyenLieu::query();

        if ($request->filled('search')) {
            $query->where('ten_nguyen_lieu', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('trang_thai')) {
            if ($request->trang_thai === 'low') {
                $query->whereRaw('so_luong_ton <= muc_canh_bao');
            } elseif ($request->trang_thai === 'ok') {
                $query->whereRaw('so_luong_ton > muc_canh_bao');
            }
        }

        $inventory = $query->orderByRaw('so_luong_ton <= muc_canh_bao DESC, ten_nguyen_lieu ASC')
            ->paginate(20)->withQueryString();

        $lowCount = NguyenLieu::whereRaw('so_luong_ton <= muc_canh_bao')->count();

        // 10 lần nhập gần nhất
        $importLog = LichSuKho::with('nguyenLieu', 'nguoiTao')
            ->where('loai_giao_dich', 'nhap')
            ->latest('created_at')
            ->limit(10)
            ->get();

        // 10 lần xuất gần nhất
        $exportLog = LichSuKho::with('nguyenLieu', 'nguoiTao')
            ->where('loai_giao_dich', 'xuat')
            ->latest('created_at')
            ->limit(10)
            ->get();

        return view('manager.inventory.index', compact(
            'inventory', 'lowCount', 'importLog', 'exportLog'
        ));
    }

    public function import()
    {
        $nguyenLieus = NguyenLieu::orderBy('ten_nguyen_lieu')->get();
        return view('manager.inventory.import', compact('nguyenLieus'));
    }

    public function storeImport(Request $request)
    {
        $request->validate([
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'so_luong'       => 'required|numeric|min:0.01',
            'don_gia'        => 'nullable|numeric|min:0',
            'ghi_chu'        => 'nullable|string|max:500',
        ], [
            'nguyen_lieu_id.required' => 'Vui lòng chọn nguyên liệu.',
            'so_luong.required'       => 'Vui lòng nhập số lượng.',
            'so_luong.min'            => 'Số lượng phải lớn hơn 0.',
        ]);

        $nguyenLieu = NguyenLieu::findOrFail($request->nguyen_lieu_id);

        // Ghi lịch sử
        LichSuKho::create([
            'nguyen_lieu_id'   => $nguyenLieu->id,
            'loai_giao_dich'   => 'nhap',
            'so_luong'         => $request->so_luong,
            'don_gia'          => $request->don_gia,
            'nguoi_tao_id'     => auth()->id(),
            'ghi_chu'          => $request->ghi_chu,
            'created_at'       => now(),
        ]);

        // Cập nhật tồn kho
        $nguyenLieu->increment('so_luong_ton', $request->so_luong);

        return redirect()->route('manager.inventory.index')
            ->with('success', "Đã nhập {$request->so_luong} {$nguyenLieu->don_vi_tinh} «{$nguyenLieu->ten_nguyen_lieu}».");
    }

    public function export()
    {
        $nguyenLieus = NguyenLieu::where('so_luong_ton', '>', 0)
            ->orderBy('ten_nguyen_lieu')
            ->get();
        return view('manager.inventory.export', compact('nguyenLieus'));
    }

    public function storeExport(Request $request)
    {
        $request->validate([
            'nguyen_lieu_id' => 'required|exists:nguyen_lieu,id',
            'so_luong'       => 'required|numeric|min:0.01',
            'ly_do'          => 'nullable|string|max:500',
        ]);

        $nguyenLieu = NguyenLieu::findOrFail($request->nguyen_lieu_id);

        if ($request->so_luong > $nguyenLieu->so_luong_ton) {
            return back()->withErrors(['so_luong' => 'Số lượng xuất vượt tồn kho hiện tại.'])
                         ->withInput();
        }

        LichSuKho::create([
            'nguyen_lieu_id'   => $nguyenLieu->id,
            'loai_giao_dich'   => 'xuat',
            'so_luong'         => $request->so_luong,
            'nguoi_tao_id'     => auth()->id(),
            'ghi_chu'          => $request->ly_do,
            'created_at'       => now(),
        ]);

        $nguyenLieu->decrement('so_luong_ton', $request->so_luong);

        return redirect()->route('manager.inventory.index')
            ->with('success', "Đã xuất {$request->so_luong} {$nguyenLieu->don_vi_tinh} «{$nguyenLieu->ten_nguyen_lieu}».");
    }
}
