<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HinhAnhSanPham extends Model
{

    protected $table = 'hinh_anh_san_pham';

    public $timestamps = false;

    protected $fillable = [
        'san_pham_id',
        'duong_dan_anh',
        'la_anh_chinh',
        'created_at',
    ];

    public function sanPham()
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function getImageUrlAttribute(): string
    {
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->duong_dan_anh)) {
            return asset('storage/' . $this->duong_dan_anh);
        }
        try {
            return \Illuminate\Support\Facades\Storage::disk('s3')->url($this->duong_dan_anh);
        } catch (\Exception $e) {
            return asset('storage/' . $this->duong_dan_anh);
        }
    }
}