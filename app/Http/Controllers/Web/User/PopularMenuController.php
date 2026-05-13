<?php

namespace App\Http\Controllers\Web\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PopularMenuController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $popularItems = collect();
        $isPersonalized = false;

        if ($user && $user->cluster_id !== null) {
            // Ambil menu paling laku di CLUSTER milik user
            $popularItems = Product::select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->join('order_items', 'products.id_produk', '=', 'order_items.id_produk')
                ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
                ->join('users', 'orders.id_users', '=', 'users.id_users')
                ->where('users.cluster_id', $user->cluster_id)
                ->where('orders.order_status', 'completed')
                ->groupBy('products.id_produk')
                ->orderByDesc('total_sold')
                ->limit(6)
                ->get();

            $isPersonalized = true;
        }

        // Fallback: Jika belum ada transaksi/kluster, ambil menu terlaris global
        if ($popularItems->isEmpty()) {
            $popularItems = Product::select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
                ->join('order_items', 'products.id_produk', '=', 'order_items.id_produk')
                ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
                ->where('orders.order_status', 'completed')
                ->groupBy('products.id_produk')
                ->orderByDesc('total_sold')
                ->limit(6)
                ->get();

            $isPersonalized = false;
        }

        $userFavoriteIds = [];
        if ($user) {
            $userFavoriteIds = \App\Models\Favorite::where('id_users', $user->id_users)
                ->pluck('id_produk')
                ->toArray();
        }

        return view('user.popular', [
            'theme' => 'light',
            'popularItems' => $popularItems,
            'isPersonalized' => $isPersonalized,
            'userFavoriteIds' => $userFavoriteIds
        ]);
    }
}
