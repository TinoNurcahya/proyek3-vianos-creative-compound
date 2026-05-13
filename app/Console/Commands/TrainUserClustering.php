<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Category;
use App\Models\Order;
use Rubix\ML\Clusterers\KMeans;
use Rubix\ML\Datasets\Unlabeled;
use Illuminate\Support\Facades\DB;

class TrainUserClustering extends Command
{
    protected $signature = 'ai:cluster-users';
    protected $description = 'Cluster users based on their purchase history categories using K-Means';

    public function handle()
    {
        $this->info('Starting User Clustering process...');

        // 1. Ambil semua kategori aktif untuk membuat skeleton feature
        $categories = Category::pluck('id_kategori')->toArray();
        
        $categories = Category::pluck('id_kategori')->toArray();
        if (empty($categories)) {
            $this->error('No categories found.');
            return;
        }

        // 2. Kumpulkan data pembelian tiap user
        $users = User::whereHas('orders', function($q) {
            $q->where('order_status', 'completed');
        })->get();

        if ($users->count() < 2) {
            $this->warn('Not enough users with completed orders for meaningful clustering.');
            return;
        }

        $samples = [];
        $userIds = [];

        $this->info('Building feature vectors for ' . $users->count() . ' users...');
        foreach ($users as $user) {
            // Hitung pembelian user per kategori
            $categoryCounts = DB::table('order_items')
                ->join('orders', 'order_items.id_pesanan', '=', 'orders.id_pesanan')
                ->join('products', 'order_items.id_produk', '=', 'products.id_produk')
                ->where('orders.id_users', $user->id_users)
                ->where('orders.order_status', 'completed')
                ->select('products.id_kategori', DB::raw('SUM(order_items.quantity) as total_qty'))
                ->groupBy('products.id_kategori')
                ->pluck('total_qty', 'id_kategori')->toArray();

            // Susun vector berurutan sesuai $categories
            $vector = [];
            foreach ($categories as $catId) {
                $vector[] = (int) ($categoryCounts[$catId] ?? 0);
            }

            $samples[] = $vector;
            $userIds[] = $user->id_users;
        }

        // 3. Setup dataset & jalankan K-Means dari Rubix ML
        $dataset = new Unlabeled($samples);
        $k = min(3, count($users));
        $estimator = new KMeans($k);

        $this->info('Training K-Means with k=' . $k . '...');
        $estimator->train($dataset);
        
        // 4. Prediksi kluster tiap user
        $predictions = $estimator->predict($dataset);

        // 5. Update user table dengan cluster_id
        $this->info('Updating database with cluster IDs...');
        foreach ($userIds as $index => $id) {
            User::where('id_users', $id)->update(['cluster_id' => $predictions[$index]]);
        }

        $this->info('Successfully clustered users into ' . $k . ' groups!');
    }
}
