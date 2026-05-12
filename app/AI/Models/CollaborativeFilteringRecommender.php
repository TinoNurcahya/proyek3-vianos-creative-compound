<?php

namespace App\AI\Models;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Kernels\Distance\Cosine;
use Illuminate\Support\Facades\DB;

/**
 * Collaborative Filtering Recommender
 *
 * Uses user-to-user similarity (Cosine distance) based on purchase patterns.
 * Finds similar users and recommends products they bought but current user hasn't.
 *
 * User vector: [product_qty_1, product_qty_2, ..., product_qty_n]
 */
class CollaborativeFilteringRecommender
{
  protected string $dataPath;

  public function __construct()
  {
    $dir = storage_path('app/ai-models');
    if (!file_exists($dir)) {
      mkdir($dir, 0777, true);
    }
    $this->dataPath = $dir . '/collaborative_data.json';
  }

  public function exists(): bool
  {
    return file_exists($this->dataPath);
  }

  /**
   * Train: build user-product purchase matrix, normalize via ZScaleStandardizer,
   * then persist to JSON for similarity search.
   *
   * @param array $userIds          parallel array of user IDs
   * @param array $userPurchaseVecs [ [qty_prod1, qty_prod2, ...], ... ]
   * @param array $productIds       product IDs (column labels)
   */
  public function train(array $userIds, array $userPurchaseVecs, array $productIds): void
  {
    if (count($userPurchaseVecs) < 2) {
      return;
    }

    // --- Use Rubix ML's ZScaleStandardizer ---
    $dataset  = new Unlabeled($userPurchaseVecs);
    $scaler   = new ZScaleStandardizer();
    $scaler->fit($dataset);
    $samples = $userPurchaseVecs;
    $scaler->transform($samples);

    // Compute means & stds manually (for normalizing query vectors later)
    $n        = count($userPurchaseVecs);
    $numProds = count($userPurchaseVecs[0]);
    $means    = array_fill(0, $numProds, 0.0);
    $stds     = array_fill(0, $numProds, 1.0);

    for ($j = 0; $j < $numProds; $j++) {
      $col       = array_column($userPurchaseVecs, $j);
      $mean      = array_sum($col) / $n;
      $variance  = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $col)) / $n;
      $means[$j] = $mean;
      $stds[$j]  = sqrt($variance) ?: 1.0;
    }

    // Build entry list
    $entries = [];
    foreach ($samples as $i => $normalized) {
      $entries[] = [
        'id'       => $userIds[$i],
        'raw'      => $userPurchaseVecs[$i],
        'norm'     => $normalized,
      ];
    }

    file_put_contents($this->dataPath, json_encode([
      'means'       => $means,
      'stds'        => $stds,
      'entries'     => $entries,
      'product_ids' => $productIds,
    ], JSON_PRETTY_PRINT));
  }

  /**
   * Recommend products based on similar users' purchases.
   *
   * @param int   $userId              target user ID
   * @param array $userRawPurchaseVec raw user purchase vector (same length as training)
   * @param array $userBoughtIds       product IDs user already bought
   * @param int   $k                   number of similar users to find
   * @param int   $topRecommendations  number of products to recommend
   */
  public function recommend(int $userId, array $userRawPurchaseVec, array $userBoughtIds = [], int $k = 5, int $topRecommendations = 6): array
  {
    if (!$this->exists()) {
      return [];
    }

    $data       = json_decode(file_get_contents($this->dataPath), true);
    $means      = $data['means'];
    $stds       = $data['stds'];
    $entries    = $data['entries'];
    $productIds = $data['product_ids'];

    if (empty($entries) || empty($userRawPurchaseVec)) {
      return [];
    }

    // Normalize user vector
    $userNorm = [];
    foreach ($userRawPurchaseVec as $j => $val) {
      $userNorm[] = ($val - $means[$j]) / $stds[$j];
    }

    // Find k most similar users using Cosine distance
    $kernel      = new Cosine();
    $similarities = [];

    foreach ($entries as $entry) {
      if ($entry['id'] === $userId) {
        continue; // skip self
      }
      $dist = $kernel->compute($userNorm, $entry['norm']);
      // Cosine distance: lower is more similar
      // Convert to similarity score (higher = more similar)
      $similarity = 1 - $dist;
      if ($similarity > 0) {
        $similarities[$entry['id']] = [
          'score'      => $similarity,
          'purchased'  => array_map(fn($v, $i) => $v > 0 ? $productIds[$i] : null, $entry['raw'], array_keys($entry['raw'])),
        ];
      }
    }

    arsort($similarities);

    // Aggregate products from k most similar users
    $similarUserCount = 0;
    $productScores = [];

    foreach ($similarities as $similarUserId => $data) {
      if ($similarUserCount >= $k) {
        break;
      }

      $weight = $data['score'];
      foreach ($data['purchased'] as $prodId) {
        if ($prodId && !in_array($prodId, $userBoughtIds)) {
          $productScores[$prodId] = ($productScores[$prodId] ?? 0) + $weight;
        }
      }

      $similarUserCount++;
    }

    // Sort by aggregated score and return top
    arsort($productScores);
    return array_slice(array_keys($productScores), 0, $topRecommendations);
  }

  /**
   * Get product IDs available in model.
   */
  public function getProductIds(): array
  {
    if (!$this->exists()) {
      return [];
    }
    $data = json_decode(file_get_contents($this->dataPath), true);
    return $data['product_ids'] ?? [];
  }
}
