<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$rec = new \App\AI\Models\ContentBasedRecommender();
$map = $rec->getProductRawFeatures();

echo "Products in model: " . count($map) . PHP_EOL;

if (!empty($map)) {
    $firstProfile = array_values($map)[0];
    $ids = $rec->recommend($firstProfile, [], 6);
    echo "Recommended IDs: " . implode(', ', $ids) . PHP_EOL;

    $products = \App\Models\Product::whereIn('id_produk', $ids)->pluck('name', 'id_produk');
    foreach ($ids as $id) {
        echo "  [{$id}] " . ($products[$id] ?? '(not found)') . PHP_EOL;
    }
} else {
    echo "No product data found in model." . PHP_EOL;
}
