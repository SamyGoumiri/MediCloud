<?php
/**
 * Weighted Sum Model (WSM) Implementation
 * Multi-criteria decision making algorithm for cabinet ranking
 * 
 * WSM is a simple additive weighting method where:
 * - Each criterion is weighted by its importance
 * - Ratings are normalized to a 0-1 scale
 * - Final score is the weighted sum of all criteria
 * 
 * @version 2.0
 * @author Team 2.4
 */

class WSM {
    
    /**
     * Compute WSM scores for cabinets
     * 
     * @param array $cabinets List of cabinets with ratings
     * @param array $criteria Selected criteria for evaluation
     * @param array $criteriaMap Mapping from French to English criteria names
     * @param array|null $weights Weight for each criterion (1-10)
     * @return array Sorted cabinets by score (highest first)
     */
    public static function compute($cabinets, $criteria, $criteriaMap, $weights = null) {
        // Validate inputs
        if (empty($cabinets)) {
            return [];
        }
        
        if (empty($criteria)) {
            return self::assignDefaultScores($cabinets, 0);
        }
        
        // Initialize weights if not provided
        if ($weights === null) {
            $weights = array_fill_keys($criteria, 5); // Default weight of 5
        }
        
        // Calculate total weight for normalization
        $totalWeight = array_sum($weights);
        
        if ($totalWeight <= 0) {
            return self::assignDefaultScores($cabinets, 0);
        }
        
        $results = [];
        
        foreach ($cabinets as $cabinet) {
            $weightedSum = 0;
            $applicableWeight = 0;
            $criteriaWithData = 0;
            
            // Calculate weighted sum for this cabinet
            foreach ($criteria as $criterion) {
                $ratingKey = $criteriaMap[$criterion] ?? null;
                
                if (!$ratingKey) {
                    continue; // Skip unmapped criteria
                }
                
                $weight = $weights[$criterion] ?? 1;
                
                // Check if cabinet has rating for this criterion
                if (isset($cabinet['ratings'][$ratingKey])) {
                    $rating = (float)$cabinet['ratings'][$ratingKey];
                    
                    // Only use ratings in valid range (0-5)
                    if ($rating > 0 && $rating <= 5) {
                        // Normalize rating to 0-1 scale and apply weight
                        $normalizedRating = $rating / 5.0;
                        $weightedSum += $normalizedRating * $weight;
                        $applicableWeight += $weight;
                        $criteriaWithData++;
                    }
                }
            }
            
            // Calculate final score (0-100%)
            if ($applicableWeight > 0 && $criteriaWithData > 0) {
                // Normalize by applicable weight
                $normalizedScore = $weightedSum / $applicableWeight;
                $cabinet['score'] = round($normalizedScore * 100);
            } else {
                // No valid ratings for any criterion
                $cabinet['score'] = 0;
            }
            
            $cabinet['method'] = 'WSM';
            $cabinet['criteria_evaluated'] = $criteriaWithData;
            $results[] = $cabinet;
        }
        
        // Sort by score (descending)
        usort($results, function($a, $b) {
            if ($a['score'] === $b['score']) {
                // Secondary sort by feedback count
                return ($b['feedback_count'] ?? 0) <=> ($a['feedback_count'] ?? 0);
            }
            return $b['score'] <=> $a['score'];
        });
        
        return $results;
    }
    
    /**
     * Assign default scores to all cabinets
     * Used when no criteria are selected or evaluation fails
     * 
     * @param array $cabinets List of cabinets
     * @param int $score Default score to assign
     * @return array Cabinets with assigned scores
     */
    private static function assignDefaultScores($cabinets, $score = 0) {
        $results = [];
        
        foreach ($cabinets as $cabinet) {
            $cabinet['score'] = $score;
            $cabinet['method'] = 'WSM';
            $cabinet['criteria_evaluated'] = 0;
            $results[] = $cabinet;
        }
        
        // Sort by feedback count if scores are equal
        usort($results, function($a, $b) {
            return ($b['feedback_count'] ?? 0) <=> ($a['feedback_count'] ?? 0);
        });
        
        return $results;
    }
    
    /**
     * Calculate score statistics for debugging/analysis
     * 
     * @param array $results Scored cabinets
     * @return array Statistical summary
     */
    public static function getScoreStatistics($results) {
        if (empty($results)) {
            return [
                'count' => 0,
                'min' => 0,
                'max' => 0,
                'avg' => 0,
                'median' => 0
            ];
        }
        
        $scores = array_column($results, 'score');
        sort($scores);
        
        $count = count($scores);
        $sum = array_sum($scores);
        
        return [
            'count' => $count,
            'min' => min($scores),
            'max' => max($scores),
            'avg' => round($sum / $count, 2),
            'median' => $count % 2 === 0 
                ? ($scores[$count / 2 - 1] + $scores[$count / 2]) / 2 
                : $scores[floor($count / 2)]
        ];
    }
}

?>
