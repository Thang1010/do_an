<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cảnh báo tồn kho</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    
    <div style="text-align: center; margin-bottom: 20px;">
        <h2 style="color: #d9534f; margin-bottom: 0;">CẢNH BÁO TỒN KHO</h2>
        <p style="color: #777; margin-top: 5px;">Hệ thống quản lý CafeTea</p>
    </div>

    <div style="background-color: #f9f9f9; border: 1px solid #e3e3e3; padding: 20px; border-radius: 5px;">
        <p>Kính gửi Quản lý,</p>
        
        <p>Hệ thống ghi nhận nguyên liệu <strong>{{ $ingredient->ten_nguyen_lieu }}</strong> hiện đang ở trạng thái 
            @if($status === 'het')
                <strong style="color: #d9534f;">HẾT HÀNG</strong>.
            @else
                <strong style="color: #f0ad4e;">SẮP HẾT</strong>.
            @endif
        </p>

        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; width: 40%;"><strong>Nguyên liệu:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $ingredient->ten_nguyen_lieu }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Tồn kho hiện tại:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd; font-weight: bold; color: #d9534f;">
                    {{ number_format($currentStock, 2, ',', '.') }} {{ $ingredient->don_vi_tinh }}
                </td>
            </tr>
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;"><strong>Mục đích sử dụng:</strong></td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $ingredient->muc_dich_su_dung ?: 'Chưa phân loại' }}</td>
            </tr>
        </table>

        <p style="margin-top: 20px;">Vui lòng kiểm tra và lên kế hoạch nhập kho để đảm bảo hoạt động kinh doanh không bị gián đoạn.</p>
        
        <div style="text-align: center; margin-top: 25px;">
            <a href="{{ route('manager.inventory.index') }}" style="background-color: #337ab7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                Đến trang Quản lý Kho
            </a>
        </div>
    </div>

    <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        <p>Đây là email tự động từ hệ thống CafeTea. Vui lòng không trả lời email này.</p>
    </div>
</body>
</html>
