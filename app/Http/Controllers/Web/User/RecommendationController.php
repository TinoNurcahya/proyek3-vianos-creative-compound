<?php

namespace App\Http\Controllers\Web\User;

use App\AI\Models\ContentBasedRecommender;
use App\AI\Models\CollaborativeFilteringRecommender;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    public function index()
    {
        $user        = Auth::user();
        $contentRec  = new ContentBasedRecommender();
        $collabRec   = new CollaborativeFilteringRecommender();

        // ── 1. Gather user interaction signals ──────────────────────────────
        $purchaseHistory = DB::table('order_items')
            ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
            ->where('orders.id_users', $user->id_users)
            ->where('orders.order_status', 'completed')
            ->select('order_items.id_produk', DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.id_produk')
            ->pluck('total_qty', 'id_produk');

        $favoriteIds = DB::table('favorites')
            ->where('id_users', $user->id_users)
            ->pluck('id_produk');

        // All IDs the user has interacted with (to exclude from recs)
        $interactedIds = $purchaseHistory->keys()
            ->merge($favoriteIds)
            ->unique()
            ->values()
            ->toArray();

        // ── 2. ML Recommendation (Content-Based + Collaborative Filtering) ──
        $recommendations = [];
        $usedMl          = false;
        $algorithm       = 'trending';

        // Strategy: Content-Based first, then Collaborative, then fallback
        if ($contentRec->exists() && $purchaseHistory->isNotEmpty()) {
            // ── Content-Based: Build user taste profile ──
            $productRawMap = $contentRec->getProductRawFeatures();
            $weightedSum = null;
            $totalWeight = 0.0;

            foreach ($purchaseHistory as $productId => $qty) {
                if (!isset($productRawMap[$productId])) {
                    continue;
                }
                $features = $productRawMap[$productId];
                $weight   = (float) $qty;

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
                $contentRecIds = $contentRec->recommend($userProfile, $interactedIds, 6);

                if (!empty($contentRecIds)) {
                    $keyed = Product::whereIn('id_produk', $contentRecIds)
                        ->where('is_available', true)
                        ->with('category')
                        ->get()
                        ->keyBy('id_produk');

                    foreach ($contentRecIds as $id) {
                        if ($keyed->has($id)) {
                            $recommendations[] = $keyed[$id];
                        }
                    }

                    $usedMl = true;
                    $algorithm = 'content-based';
                }
            }
        }

        // ── Hybrid: Fill remaining slots with Collaborative Filtering ──
        if ($collabRec->exists() && $purchaseHistory->isNotEmpty() && count($recommendations) < 6) {
            $productIds = $collabRec->getProductIds();

            // Build user's purchase vector matching model's product order
            $userVec = array_fill(0, count($productIds), 0.0);
            $productIndexMap = array_flip($productIds);

            foreach ($purchaseHistory as $productId => $qty) {
                if (isset($productIndexMap[$productId])) {
                    $userVec[$productIndexMap[$productId]] = (float) $qty;
                }
            }

            $needed = 6 - count($recommendations);
            $excludeIds = array_merge(
                $interactedIds,
                collect($recommendations)->pluck('id_produk')->toArray()
            );

            $collabRecIds = $collabRec->recommend($user->id_users, $userVec, $excludeIds, 5, $needed);

            if (!empty($collabRecIds)) {
                $keyed = Product::whereIn('id_produk', $collabRecIds)
                    ->where('is_available', true)
                    ->with('category')
                    ->get()
                    ->keyBy('id_produk');

                foreach ($collabRecIds as $id) {
                    if ($keyed->has($id) && count($recommendations) < 6) {
                        $recommendations[] = $keyed[$id];
                    }
                }

                if ($algorithm === 'content-based') {
                    $algorithm = 'hybrid'; // Mark as hybrid if used both models
                } else {
                    $algorithm = 'collaborative';
                    $usedMl = true;
                }
            }
        }

        // ── 3. Fallback: Fill up to 6 products ──────────────────────────────
        if (count($recommendations) < 6) {
            $needed     = 6 - count($recommendations);
            $excludeIds = array_merge(
                $interactedIds,
                collect($recommendations)->pluck('id_produk')->toArray()
            );

            // A. Coba cari produk populer yang BELUM pernah dibeli/favorit
            $popular = Product::where('is_available', true)
                ->whereNotIn('id_produk', $excludeIds)
                ->with('category')
                ->withCount(['orderItems as sold_count' => function ($q) {
                    $q->whereHas('order', fn($o) => $o->where('order_status', 'completed'));
                }])
                ->orderByDesc('is_signature')
                ->orderByDesc('sold_count')
                ->limit($needed)
                ->get();

            $recommendations = array_merge($recommendations, $popular->all());

            // B. Jika masih kurang dari 6 (karena semua produk sudah dibeli), 
            // ambil produk apa saja yang tersedia untuk mengisi slot (max 6)
            if (count($recommendations) < 6) {
                $stillNeeded = 6 - count($recommendations);
                $alreadyIn   = collect($recommendations)->pluck('id_produk')->toArray();
                
                $anyAvailable = Product::where('is_available', true)
                    ->whereNotIn('id_produk', $alreadyIn)
                    ->with('category')
                    ->orderByDesc('is_signature')
                    ->limit($stillNeeded)
                    ->get();

                $recommendations = array_merge($recommendations, $anyAvailable->all());
            }
        }

        // ── 4. Resolve user favorite IDs for heart-toggle UI ───────────────
        $userFavoriteIds = DB::table('favorites')
            ->where('id_users', $user->id_users)
            ->pluck('id_produk')
            ->toArray();

        $hasHistory = $purchaseHistory->isNotEmpty();
        $theme = 'light';

        return view('user.recommendation', array_merge(compact(
            'recommendations',
            'hasHistory',
            'usedMl',
            'algorithm',
            'userFavoriteIds'
        ), [
            'theme' => $theme,
        ]));
    }
}
