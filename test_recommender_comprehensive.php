#!/usr/bin/env php
<?php

/**
 * Comprehensive Recommender System Test Script
 * 
 * Tests both Content-Based and Collaborative Filtering models
 * Usage: php test_recommender_comprehensive.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\AI\Models\ContentBasedRecommender;
use App\AI\Models\CollaborativeFilteringRecommender;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────────────────────────────────
// Helper Functions
// ─────────────────────────────────────────────────────────────────────────

function title($text)
{
  echo "\n";
  echo "╔" . str_repeat("═", strlen($text) + 2) . "╗\n";
  echo "║ " . $text . " ║\n";
  echo "╚" . str_repeat("═", strlen($text) + 2) . "╝\n";
}

function section($text)
{
  echo "\n\n▶ " . $text . "\n";
  echo "─" . str_repeat("─", strlen($text)) . "\n";
}

function info($text)
{
  echo "  ℹ " . $text . "\n";
}

function success($text)
{
  echo "  ✓ " . $text . "\n";
}

function warning($text)
{
  echo "  ⚠ " . $text . "\n";
}

function error($text)
{
  echo "  ✗ " . $text . "\n";
}

// ─────────────────────────────────────────────────────────────────────────
// Main Test Script
// ─────────────────────────────────────────────────────────────────────────

title("🤖 Recommender System Test Suite");

// Test 1: Content-Based Filtering
section("1. Content-Based Filtering Model");

$cbf = new ContentBasedRecommender();

if ($cbf->exists()) {
  success("Model file exists: storage/app/ai-models/recommender_data.json");

  // Get product raw features
  $productRawMap = $cbf->getProductRawFeatures();
  info("Total products in model: " . count($productRawMap));

  // Show sample products
  $sampleProducts = array_slice($productRawMap, 0, 3, true);
  foreach ($sampleProducts as $prodId => $features) {
    $product = Product::find($prodId);
    if ($product) {
      $name = $product->name ?? "Unknown";
      $featureStr = implode(", ", array_map(fn($f) => round($f, 2), $features));
      info("Product #$prodId ($name): [$featureStr]");
    }
  }

  // Test recommendation for a real user
  section("1.1 Testing Content-Based Recommendations");

  $testUsers = User::whereHas('orders', function ($q) {
    $q->where('order_status', 'completed');
  })->limit(3)->get();

  if ($testUsers->count() > 0) {
    foreach ($testUsers as $user) {
      info("\nUser #" . $user->id_users . " (" . $user->name . ")");

      // Get purchase history
      $purchases = DB::table('order_items')
        ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
        ->where('orders.id_users', $user->id_users)
        ->where('orders.order_status', 'completed')
        ->select('order_items.id_produk', DB::raw('SUM(order_items.quantity) as total_qty'))
        ->groupBy('order_items.id_produk')
        ->pluck('total_qty', 'id_produk');

      info("  Purchase history: " . count($purchases) . " products");

      if ($purchases->count() > 0) {
        // Build user profile
        $weightedSum = null;
        $totalWeight = 0.0;

        foreach ($purchases as $productId => $qty) {
          if (!isset($productRawMap[$productId])) {
            continue;
          }
          $features = $productRawMap[$productId];
          $weight = (float) $qty;

          if ($weightedSum === null) {
            $weightedSum = array_map(fn($f) => $f * $weight, $features);
          } else {
            foreach ($features as $j => $val) {
              $weightedSum[$j] += $val * $weight;
            }
          }
          $totalWeight += $weight;
        }

        if ($weightedSum !== null && $totalWeight > 0) {
          $userProfile = array_map(fn($v) => $v / $totalWeight, $weightedSum);
          $excludeIds = $purchases->keys()->toArray();

          $recIds = $cbf->recommend($userProfile, $excludeIds, 6);

          if (!empty($recIds)) {
            success("  Got " . count($recIds) . " recommendations:");
            foreach ($recIds as $i => $id) {
              $prod = Product::find($id);
              if ($prod) {
                $price = "Rp " . number_format($prod->price, 0, ',', '.');
                info("    #" . ($i + 1) . ": {$prod->name} ({$price})");
              }
            }
          } else {
            warning("  No recommendations found");
          }
        }
      } else {
        warning("  User has no purchase history");
      }
    }
  } else {
    warning("No users with purchase history found");
  }
} else {
  error("Content-Based model not found. Train it first with TrainRecommenderJob");
}

// Test 2: Collaborative Filtering
section("2. Collaborative Filtering Model");

$cf = new CollaborativeFilteringRecommender();

if ($cf->exists()) {
  success("Model file exists: storage/app/ai-models/collaborative_data.json");

  $productIds = $cf->getProductIds();
  info("Total products in model: " . count($productIds));

  section("2.1 Testing Collaborative Recommendations");

  $testUsers = User::whereHas('orders', function ($q) {
    $q->where('order_status', 'completed');
  })->limit(3)->get();

  if ($testUsers->count() > 0) {
    foreach ($testUsers as $user) {
      info("\nUser #" . $user->id_users . " (" . $user->name . ")");

      // Get purchase history
      $purchases = DB::table('order_items')
        ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
        ->where('orders.id_users', $user->id_users)
        ->where('orders.order_status', 'completed')
        ->select('order_items.id_produk', DB::raw('SUM(order_items.quantity) as total_qty'))
        ->groupBy('order_items.id_produk')
        ->pluck('total_qty', 'id_produk');

      if ($purchases->count() > 0) {
        // Build user vector
        $userVec = array_fill(0, count($productIds), 0.0);
        $productIndexMap = array_flip($productIds);

        foreach ($purchases as $productId => $qty) {
          if (isset($productIndexMap[$productId])) {
            $userVec[$productIndexMap[$productId]] = (float) $qty;
          }
        }

        $excludeIds = $purchases->keys()->toArray();
        $recIds = $cf->recommend($user->id_users, $userVec, $excludeIds, 5, 6);

        if (!empty($recIds)) {
          success("  Got " . count($recIds) . " recommendations:");
          foreach ($recIds as $i => $id) {
            $prod = Product::find($id);
            if ($prod) {
              $price = "Rp " . number_format($prod->price, 0, ',', '.');
              info("    #" . ($i + 1) . ": {$prod->name} ({$price})");
            }
          }
        } else {
          warning("  No recommendations found");
        }
      } else {
        warning("  User has no purchase history");
      }
    }
  } else {
    warning("No users with purchase history found");
  }
} else {
  warning("Collaborative Filtering model not found. Train it first with TrainCollaborativeFilteringJob");
}

// Test 3: Summary
section("3. Test Summary & Recommendations");

$cbfExists = $cbf->exists();
$cfExists = $cf->exists();

echo "\n";
if ($cbfExists) {
  success("Content-Based Filtering: ✓ Ready");
} else {
  warning("Content-Based Filtering: ✗ Not trained");
}

if ($cfExists) {
  success("Collaborative Filtering: ✓ Ready");
} else {
  warning("Collaborative Filtering: ✗ Not trained");
}

echo "\n";

if ($cbfExists && $cfExists) {
  success("Both models are trained. Hybrid recommendations are active!");
  info("Recommendations will use both CBF and CF for best results.");
} elseif ($cbfExists) {
  warning("Only Content-Based model is trained.");
  info("Recommendations will use CBF + trending fallback.");
  info("Run TrainCollaborativeFilteringJob to enable Collaborative Filtering.");
} else {
  error("No ML models are trained!");
  info("Run these jobs to train models:");
  info("  1. TrainRecommenderJob (Content-Based)");
  info("  2. TrainCollaborativeFilteringJob (Collaborative)");
}

echo "\n";
echo "✨ Test complete!\n\n";
