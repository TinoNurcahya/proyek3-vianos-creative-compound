<?php

namespace App\Jobs;

use App\AI\Models\CollaborativeFilteringRecommender;
use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainCollaborativeFilteringJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public function handle(): void
  {
    Log::info('[CollaborativeFiltering] Memulai training Collaborative Filtering Recommender...');

    // Get all active products
    $products = Product::where('is_available', true)->orderBy('id_produk')->get();

    if ($products->count() < 2) {
      Log::warning('[CollaborativeFiltering] Produk tidak cukup untuk training (minimal 2).');
      return;
    }

    $productIds = $products->pluck('id_produk')->toArray();
    $productIndexMap = array_flip($productIds); // id => index

    // Get all users with purchase history
    $usersWithPurchases = User::whereHas('orders', function ($q) {
      $q->where('order_status', 'completed');
    })->get();

    if ($usersWithPurchases->count() < 2) {
      Log::warning('[CollaborativeFiltering] User dengan purchase history tidak cukup (minimal 2).');
      return;
    }

    // Build user-product purchase matrix
    $userIds = [];
    $userPurchaseVecs = [];

    foreach ($usersWithPurchases as $user) {
      $userIds[] = $user->id_users;

      // Get purchase quantities per product for this user
      $purchases = DB::table('order_items')
        ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
        ->where('orders.id_users', $user->id_users)
        ->where('orders.order_status', 'completed')
        ->select('order_items.id_produk', DB::raw('SUM(order_items.quantity) as total_qty'))
        ->groupBy('order_items.id_produk')
        ->pluck('total_qty', 'id_produk');

      // Build vector [qty_prod1, qty_prod2, ..., qty_prodN]
      $vec = array_fill(0, count($productIds), 0.0);
      foreach ($purchases as $productId => $qty) {
        if (isset($productIndexMap[$productId])) {
          $vec[$productIndexMap[$productId]] = (float) $qty;
        }
      }

      $userPurchaseVecs[] = $vec;
    }

    // Train Collaborative Filtering model
    $collab = new CollaborativeFilteringRecommender();
    $collab->train($userIds, $userPurchaseVecs, $productIds);

    Log::info('[CollaborativeFiltering] Training selesai.', [
      'total_users' => count($userIds),
      'total_products' => count($productIds),
    ]);
  }
}
