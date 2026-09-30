<?php
/**
 * TOPSIS Implementation - Technique for Order Preference by Similarity to Ideal Solution
 * Advanced multi-criteria decision making algorithm for cabinet ranking
 * 
 * TOPSIS Algorithm:
 * 1. Normalize the decision matrix
 * 2. Calculate weighted normalized decision matrix
 * 3. Determine ideal best and ideal worst solutions
 * 4. Calculate separation measures (Euclidean distance)
 * 5. Calculate relative closeness to ideal solution
 * 6. Rank alternatives by closeness coefficient
 * 
 * @version 2.0
 * @author Team 2.4
 */

class TOPSIS {
    
    // Criteria that are "cost" type (lower is better)
    // All others are "benefit" type (higher is better)
    private const COST_CRITERIA = ['frais'];
    
    /**
     * Compute TOPSIS scores for cabinets
     * 
     * @param array $cabinets List of cabinets with ratings
     * @param array $criteria Selected criteria for evaluation
     * @param array $criteriaMap Mapping from French to English criteria names
     * @param array|null $weights Weight for each criterion (1-10)
     * @return array Sorted cabinets by closeness coefficient (highest first)
     */
    public static function compute($cabinets, $criteria, $criteriaMap, $weights = null) {
        // Validate inputs
        if (empty($cabinets) || empty($criteria)) {
            return self::assignDefaultScores($cabinets, 0);
        }
        
        // Initialize weights if not provided
        if ($weights === null) {
            $weights = array_fill_keys($criteria, 5);
        }
        
        // Normalize weights to sum to 1
        $totalWeight = array_sum($weights);
        if ($totalWeight <= 0) {
            return self::assignDefaultScores($cabinets, 0);
        }
        
        $normalizedWeights = [];
        foreach ($criteria as $criterion) {
            $normalizedWeights[$criterion] = $weights[$criterion] / $totalWeight;
        }
        
        // Build decision matrix - only include cabinets with complete data
        $matrix = [];
        $validCabinets = [];
        
        foreach ($cabinets as $cabinet) {
            $row = [];
            $hasCompleteData = true;
            
            foreach ($criteria as $criterion) {
                $ratingKey = $criteriaMap[$criterion] ?? null;
                
                if (!$ratingKey || !isset($cabinet['ratings'][$ratingKey])) {
                    $hasCompleteData = false;
                    break;
                }
                
                $rating = (float)$cabinet['ratings'][$ratingKey];
                
                // Validate rating is in valid range
                if ($rating <= 0 || $rating > 5) {
                    $hasCompleteData = false;
                    break;
                }
                
                $row[] = $rating;
            }
            
            // Only include cabinets with all criteria rated
            if ($hasCompleteData && count($row) === count($criteria)) {
                $matrix[] = $row;
                $validCabinets[] = $cabinet;
            }
        }
        
        // Need at least 2 alternatives for meaningful comparison
        if (count($validCabinets) < 2) {
            // Fallback to WSM for cabinets with incomplete data
            return WSM::compute($cabinets, $criteria, $criteriaMap, $weights);
        }
        
        // Execute TOPSIS algorithm
        try {
            $normalized = self::normalizeMatrix($matrix);
            $weighted = self::applyWeights($normalized, $normalizedWeights, $criteria);
            $idealBest = self::findIdealSolution($weighted, $criteria, true);
            $idealWorst = self::findIdealSolution($weighted, $criteria, false);
            
            $results = [];
            
            foreach ($validCabinets as $index => $cabinet) {
                $distanceToBest = self::euclideanDistance($weighted[$index], $idealBest);
                $distanceToWorst = self::euclideanDistance($weighted[$index], $idealWorst);
                
                $totalDistance = $distanceToBest + $distanceToWorst;
                
                // Calculate closeness coefficient (0-1)
                $closeness = $totalDistance > 0 
                    ? $distanceToWorst / $totalDistance 
                    : 0;
                
                $cabinet['score'] = round($closeness * 100);
                $cabinet['method'] = 'TOPSIS';
                $cabinet['criteria_evaluated'] = count($criteria);
                $cabinet['distance_to_ideal'] = round($distanceToBest, 4);
                $cabinet['distance_to_worst'] = round($distanceToWorst, 4);
                $cabinet['closeness'] = round($closeness, 4);
                
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
            
        } catch (Exception $e) {
            error_log('TOPSIS Computation Error: ' . $e->getMessage());
            // Fallback to WSM
            return WSM::compute($cabinets, $criteria, $criteriaMap, $weights);
        }
    }
    
    /**
     * Normalize decision matrix using vector normalization
     * Each column is divided by the square root of sum of squares
     * 
     * @param array $matrix Decision matrix (rows = alternatives, cols = criteria)
     * @return array Normalized matrix
     */
    private static function normalizeMatrix($matrix) {
        $rows = count($matrix);
        $cols = count($matrix[0]);
        $normalized = [];
        
        // Calculate normalization factors for each column
        $normFactors = [];
        for ($j = 0; $j < $cols; $j++) {
            $sumSquares = 0;
            for ($i = 0; $i < $rows; $i++) {
                $sumSquares += $matrix[$i][$j] * $matrix[$i][$j];
            }
            $normFactors[$j] = sqrt($sumSquares);
            
            // Prevent division by zero
            if ($normFactors[$j] == 0) {
                $normFactors[$j] = 1;
            }
        }
        
        // Normalize each element
        for ($i = 0; $i < $rows; $i++) {
            $normalized[$i] = [];
            for ($j = 0; $j < $cols; $j++) {
                $normalized[$i][$j] = $matrix[$i][$j] / $normFactors[$j];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Apply weights to normalized matrix
     * 
     * @param array $normalized Normalized decision matrix
     * @param array $weights Normalized weights for each criterion
     * @param array $criteria List of criteria
     * @return array Weighted normalized matrix
     */
    private static function applyWeights($normalized, $weights, $criteria) {
        $weighted = [];
        
        foreach ($normalized as $i => $row) {
            $weighted[$i] = [];
            foreach ($row as $j => $value) {
                $criterion = $criteria[$j];
                $weight = $weights[$criterion] ?? 0;
                $weighted[$i][$j] = $value * $weight;
            }
        }
        
        return $weighted;
    }
    
    /**
     * Find ideal best or ideal worst solution
     * 
     * @param array $weighted Weighted normalized matrix
     * @param array $criteria List of criteria
     * @param bool $isBest True for ideal best, false for ideal worst
     * @return array Ideal solution vector
     */
    private static function findIdealSolution($weighted, $criteria, $isBest) {
        $cols = count($criteria);
        $ideal = [];
        
        for ($j = 0; $j < $cols; $j++) {
            $column = array_column($weighted, $j);
            $criterion = $criteria[$j];
            
            // Check if this is a cost criterion (lower is better)
            $isCost = in_array($criterion, self::COST_CRITERIA, true);
            
            if ($isBest) {
                // Ideal best: max for benefit, min for cost
                $ideal[$j] = $isCost ? min($column) : max($column);
            } else {
                // Ideal worst: min for benefit, max for cost
                $ideal[$j] = $isCost ? max($column) : min($column);
            }
        }
        
        return $ideal;
    }
    
    /**
     * Calculate Euclidean distance between two vectors
     * 
     * @param array $point1 First vector
     * @param array $point2 Second vector
     * @return float Euclidean distance
     */
    private static function euclideanDistance($point1, $point2) {
        $sumSquares = 0;
        
        foreach ($point1 as $j => $value) {
            $diff = $value - $point2[$j];
            $sumSquares += $diff * $diff;
        }
        
        return sqrt($sumSquares);
    }
    
    /**
     * Assign default scores to all cabinets
     * Used when TOPSIS cannot be applied
     * 
     * @param array $cabinets List of cabinets
     * @param int $score Default score to assign
     * @return array Cabinets with assigned scores
     */
    private static function assignDefaultScores($cabinets, $score = 0) {
        $results = [];
        
        foreach ($cabinets as $cabinet) {
            $cabinet['score'] = $score;
            $cabinet['method'] = 'TOPSIS';
            $cabinet['criteria_evaluated'] = 0;
            $results[] = $cabinet;
        }
        
        // Sort by feedback count
        usort($results, function($a, $b) {
            return ($b['feedback_count'] ?? 0) <=> ($a['feedback_count'] ?? 0);
        });
        
        return $results;
    }
    
    /**
     * Calculate TOPSIS statistics for analysis
     * 
     * @param array $results Scored cabinets with TOPSIS data
     * @return array Statistical summary
     */
    public static function getTopsisStatistics($results) {
        if (empty($results)) {
            return [
                'count' => 0,
                'avg_closeness' => 0,
                'avg_distance_to_ideal' => 0,
                'avg_distance_to_worst' => 0
            ];
        }
        
        $closeness = array_column($results, 'closeness');
        $distToIdeal = array_column($results, 'distance_to_ideal');
        $distToWorst = array_column($results, 'distance_to_worst');
        
        $count = count($results);
        
        return [
            'count' => $count,
            'avg_closeness' => round(array_sum($closeness) / $count, 4),
            'avg_distance_to_ideal' => round(array_sum($distToIdeal) / $count, 4),
            'avg_distance_to_worst' => round(array_sum($distToWorst) / $count, 4),
            'min_score' => min(array_column($results, 'score')),
            'max_score' => max(array_column($results, 'score'))
        ];
    }
}

?>
