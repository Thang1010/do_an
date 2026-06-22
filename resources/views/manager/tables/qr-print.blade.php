<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>In QR gọi món tại bàn</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, "Segoe UI", Roboto, Arial, sans-serif; background: #f1f0ee; color: #30261c; padding: 24px; }
        .toolbar { max-width: 1000px; margin: 0 auto 20px; display: flex; gap: 12px; align-items: center; justify-content: space-between; }
        .toolbar h1 { font-size: 20px; }
        .btn { padding: 10px 18px; border: 0; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-print { background: #30261c; color: #fff; }
        .btn-back { background: #e2d9c8; color: #30261c; }
        .grid { max-width: 1000px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
        .qr-card { background: #fff; border: 1px solid #e2d9c8; border-radius: 14px; padding: 18px; text-align: center; break-inside: avoid; }
        .qr-card .ten-quan { font-size: 13px; color: #7a6555; font-weight: 600; }
        .qr-card .so-ban { font-family: 'Playfair Display', Georgia, serif; font-size: 26px; font-weight: 700; margin: 4px 0 12px; }
        .qr-card img { width: 200px; height: 200px; max-width: 100%; }
        .qr-card .huong-dan { font-size: 12px; color: #7a6555; margin-top: 10px; line-height: 1.5; }
        .empty { max-width: 1000px; margin: 40px auto; text-align: center; color: #7a6555; }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .qr-card { border: 1px solid #ccc; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <h1>QR gọi món tại bàn ({{ $tables->count() }} bàn)</h1>
        <div>
            <a href="{{ route('manager.tables.index') }}" class="btn btn-back">Quay lại</a>
            <button type="button" class="btn btn-print" onclick="window.print()">In</button>
        </div>
    </div>

    @if($tables->isEmpty())
        <p class="empty">Chưa có bàn nào để tạo QR.</p>
    @else
        <div class="grid">
            @foreach($tables as $table)
                <div class="qr-card">
                    <div class="ten-quan">Quét để gọi món</div>
                    <div class="so-ban">Bàn {{ $table->so_ban }}</div>
                    <img src="{{ $qrcodes[$table->id] }}" alt="QR Bàn {{ $table->so_ban }}">
                    <div class="huong-dan">Dùng camera điện thoại quét mã để xem menu &amp; gọi món tại bàn này.</div>
                </div>
            @endforeach
        </div>
    @endif
</body>
</html>
