<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Create a dummy image file
    $dummyFile = 'dummy.jpg';
    $img = imagecreatetruecolor(800, 800);
    imagejpeg($img, $dummyFile);
    imagedestroy($img);
    
    // Simulate SplFileInfo for intervention
    $file = new \SplFileInfo(realpath($dummyFile));
    
    $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
    $image = $manager->decode($file)->cover(500, 500);

    $filename = 'avatars/test-' . time() . '.jpg';
    \Illuminate\Support\Facades\Storage::disk('s3')->put($filename, (string) $image->encodeUsingFileExtension('jpg', 80));
    
    echo "UPLOAD SUCCESS: $filename\n";
    unlink($dummyFile);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
