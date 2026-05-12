<?php

namespace App\AI\Models;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Transformers\ZScaleStandardizer;
use Rubix\ML\Kernels\Distance\Euclidean;

/**
 * Content-Based Recommender
 *
 * Uses Rubix ML's ZScaleStandardizer for feature normalization and
 * Euclidean distance kernel to find products similar to a user's taste profile.
 *
 * Features per product: [category_id, price, is_signature, popularity]
 */
class ContentBasedRecommender
{
    protected string $dataPath;

    public function __construct()
    {
        $dir = storage_path('app/ai-models');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->dataPath = $dir . '/recommender_data.json';
    }

    public function exists(): bool
    {
        return file_exists($this->dataPath);
    }

    /**
     * Train: fit a ZScaleStandardizer on all product features using Rubix ML,
     * then persist the normalized vectors + scaling params to JSON.
     *
     * @param array $rawFeatures  [ [category_id, price, is_signature, popularity], ... ]
     * @param array $productIds   parallel array of product IDs
     */
    public function train(array $rawFeatures, array $productIds): void
    {
        if (count($rawFeatures) < 2) {
            return;
        }

        // --- Use Rubix ML's ZScaleStandardizer ---
        $dataset  = new Unlabeled($rawFeatures);
        $scaler   = new ZScaleStandardizer();
        $scaler->fit($dataset);
        // transform() takes samples array by reference
        $samples = $rawFeatures;
        $scaler->transform($samples);

        // Compute means & stds manually (needed to normalize query vectors later)
        $n        = count($rawFeatures);
        $numFeats = count($rawFeatures[0]);
        $means    = array_fill(0, $numFeats, 0.0);
        $stds     = array_fill(0, $numFeats, 1.0);

        for ($j = 0; $j < $numFeats; $j++) {
            $col       = array_column($rawFeatures, $j);
            $mean      = array_sum($col) / $n;
            $variance  = array_sum(array_map(fn($v) => ($v - $mean) ** 2, $col)) / $n;
            $means[$j] = $mean;
            $stds[$j]  = sqrt($variance) ?: 1.0; // avoid division by zero
        }

        // Build entry list with normalized features (from Rubix ML output)
        $entries = [];
        foreach ($samples as $i => $normalized) {
            $entries[] = [
                'id'       => $productIds[$i],
                'raw'      => $rawFeatures[$i],
                'norm'     => $normalized,
            ];
        }

        file_put_contents($this->dataPath, json_encode([
            'means'   => $means,
            'stds'    => $stds,
            'entries' => $entries,
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Return top-k product IDs most similar to the user profile.
     *
     * @param array $rawUserProfile  raw feature vector (same order as training)
     * @param array $excludeIds      product IDs to skip (already interacted)
     * @param int   $k               number of recommendations
     */
    public function recommend(array $rawUserProfile, array $excludeIds = [], int $k = 6): array
    {
        if (!$this->exists()) {
            return [];
        }

        $data    = json_decode(file_get_contents($this->dataPath), true);
        $means   = $data['means'];
        $stds    = $data['stds'];
        $entries = $data['entries'];

        if (empty($entries)) {
            return [];
        }

        // Normalize user profile using stored means/stds (same transform as training)
        $userNorm = [];
        foreach ($rawUserProfile as $j => $val) {
            $userNorm[] = ($val - $means[$j]) / $stds[$j];
        }

        // Compute Euclidean distances using Rubix ML's distance kernel
        $kernel    = new Euclidean();
        $distances = [];

        foreach ($entries as $entry) {
            if (in_array($entry['id'], $excludeIds)) {
                continue;
            }
            $distances[$entry['id']] = $kernel->compute($userNorm, $entry['norm']);
        }

        asort($distances); // nearest first
        return array_keys(array_slice($distances, 0, $k, true));
    }

    /**
     * Return raw feature data keyed by product ID (for building user profiles).
     */
    public function getProductRawFeatures(): array
    {
        if (!$this->exists()) {
            return [];
        }
        $data = json_decode(file_get_contents($this->dataPath), true);
        $map  = [];
        foreach ($data['entries'] as $entry) {
            $map[$entry['id']] = $entry['raw'];
        }
        return $map;
    }
}
