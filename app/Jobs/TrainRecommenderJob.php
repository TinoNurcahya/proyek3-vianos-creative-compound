<?php

namespace App\Jobs;

use App\AI\Models\ContentBasedRecommender;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrainRecommenderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('[Recommender] Memulai training Content-Based Recommender...');

        $products = Product::where('is_available', true)->get();

        if ($products->count() < 2) {
            Log::warning('[Recommender] Produk tidak cukup untuk training (minimal 2).');
            return;
        }

        // Popularity: total quantity sold per product (completed orders)
        $popularity = DB::table('order_items')
            ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
            ->where('orders.order_status', 'completed')
            ->select('order_items.id_produk', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('order_items.id_produk')
            ->pluck('total_sold', 'id_produk');

        $rawFeatures = [];
        $productIds  = [];

        foreach ($products as $product) {
            $pop = (float) $popularity->get($product->id_produk, 0);

            $productIds[]  = $product->id_produk;
            $rawFeatures[] = [
                (float) $product->id_kategori,   // category context
                (float) $product->price,          // price point
                (float) ($product->is_signature ? 1 : 0), // signature flag
                $pop,                             // popularity score
            ];
        }

        $recommender = new ContentBasedRecommender();
        $recommender->train($rawFeatures, $productIds);

        Log::info('[Recommender] Training selesai.', ['total_products' => count($productIds)]);
    }
}
