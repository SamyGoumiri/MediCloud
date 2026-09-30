<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/search_helper.php';
require_once __DIR__ . '/methods/wsm.php';
require_once __DIR__ . '/methods/topsis.php';

class Search {
    private $pdo;
    private $specialty;
    private $city;
    private $criteria;
    private $method;
    private $weights;
    
    const CRITERIA_MAP = [
        'accueil' => 'reception',
        'competence' => 'skills',
        'hygiene' => 'hygiene',
        'securite' => 'security',
        'experience' => 'experience',
        'localisation' => 'location',
        'frais' => 'price',
        'disponibilite' => 'availability',
        'equipement' => 'equipment',
        'ponctualite' => 'punctuality'
    ];
    
    const ALLOWED_SPECIALTIES = ['generaliste','cardiologue','pediatre','dermatologue'];
    const ALLOWED_WILAYAS = ['Alger','Tipaza','Blida','Tizi Ouzou'];
    
    public function __construct($mysqli_conn = null) {
        global $conn;
        $this->pdo = $mysqli_conn ?? $conn;
        if (!$this->pdo) die('Database error');

        $this->specialty = isset($_POST['specialty']) ? strtolower(trim($_POST['specialty'])) : '';
        $this->city = isset($_POST['wilaya']) ? trim($_POST['wilaya']) : (isset($_POST['city']) ? trim($_POST['city']) : '');
        $this->specialty = in_array($this->specialty, self::ALLOWED_SPECIALTIES, true) ? $this->specialty : '';
        $this->city = in_array($this->city, self::ALLOWED_WILAYAS, true) ? $this->city : '';
        
        $this->criteria = isset($_POST['criteria']) ? (is_array($_POST['criteria']) ? $_POST['criteria'] : [$_POST['criteria']]) : [];
        
        if (count($this->criteria) < 2) {
            $this->sendError('Veuillez sélectionner au moins 2 critères pour effectuer la recherche.');
        }
        
        $this->method = isset($_POST['method']) ? strtolower(trim($_POST['method'])) : 'wsm';
        $this->loadWeights();
    }
    
    private function loadWeights() {
        $this->weights = [];
        foreach ($this->criteria as $crit) {
            $weightKey = 'weight-' . $crit;
            $this->weights[$crit] = isset($_POST[$weightKey]) ? max(1, min(10, (int)$_POST[$weightKey])) : 5;
        }
    }
    
    public function execute() {
        $cabinets = $this->loadCabinets();
        
        error_log('DEBUG: Cabinets loaded: ' . count($cabinets));
        
        if (empty($cabinets)) {
            $this->sendError('Aucun cabinet trouvé dans la base de données.');
        }
        
        $filtered = $this->filterCabinets($cabinets);
        error_log('DEBUG: After filter by wilaya: ' . count($filtered));
        
        if (empty($filtered)) {
            $this->sendError('Aucun cabinet ne correspond à vos critères de recherche dans la wilaya sélectionnée.');
        }
        
        $this->loadDoctors($filtered);
        
        if ($this->specialty) {
            error_log('DEBUG: Filtering by specialty: ' . $this->specialty);
            $filtered = array_filter($filtered, function($c) {
                foreach ($c['doctors'] as $doctor) {
                    if (strtolower(trim($doctor['specialty'] ?? '')) === $this->specialty) {
                        return true;
                    }
                }
                return false;
            });
            error_log('DEBUG: After specialty filter: ' . count($filtered));
            
            if (empty($filtered)) {
                $this->sendError("Aucun cabinet trouvé avec des médecins de la spécialité: " . ucfirst($this->specialty));
            }
        }
        
        $this->loadFeedbackRatings($filtered);
        $results = $this->scoreResults($filtered);
        $this->displayResults($results);
    }
    
    private function sendError($message) {
        http_response_code(400);
        echo '<div class="error-message" style="padding: 20px; background: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c33; text-align: center;">';
        echo '<p style="margin: 0; font-size: 16px;">⚠️ ' . htmlspecialchars($message) . '</p>';
        echo '</div>';
        exit;
    }
    
    private function loadCabinets() {
        return get_all_cabinets($this->pdo, '', $this->city);
    }
    
    private function filterCabinets($cabinets) {
        $selectedCity = $this->city ? strtolower(trim($this->city)) : '';
        return array_filter($cabinets, function($c) use ($selectedCity) {
            if ($selectedCity) {
                $cabCity = strtolower(trim($c['city'] ?? ''));
                if ($cabCity !== $selectedCity) return false;
            }
            return true;
        });
    }
    
    private function loadDoctors(&$filtered) {
        foreach ($filtered as &$cabinet) {
            $cabinet['doctors'] = get_doctors_by_cabinet($this->pdo, $cabinet['id']);
        }
    }
    
    private function loadFeedbackRatings(&$filtered) {
        foreach ($filtered as &$cabinet) {
            $feedback = get_cabinet_feedback($this->pdo, $cabinet['id'], $this->specialty);
            $feedbackCount = (int)($feedback['feedback_count'] ?? 0);

            if ($feedback && $feedbackCount > 0) {
                // Ratings come from MediCloud.feedbacks (filled by patient_feedback submissions)
                $cabinet['ratings'] = [
                    'security' => (float)$feedback['security'],
                    'equipment' => (float)$feedback['equipment'],
                    'hygiene' => (float)$feedback['hygiene'],
                    'availability' => (float)$feedback['availability'],
                    'skills' => (float)$feedback['skills'],
                    'experience' => (float)$feedback['experience'],
                    'location' => (float)$feedback['location'],
                    'price' => (float)$feedback['price'],
                    'reception' => (float)$feedback['reception'],
                    'punctuality' => (float)$feedback['punctuality'],
                ];
                $cabinet['feedback_count'] = $feedbackCount;
            } else {
                // No patient feedback yet: use default neutral ratings (3/5) to allow WSM/TOPSIS to process
                $cabinet['ratings'] = [
                    'security' => 3.0,
                    'equipment' => 3.0,
                    'hygiene' => 3.0,
                    'availability' => 3.0,
                    'skills' => 3.0,
                    'experience' => 3.0,
                    'location' => 3.0,
                    'price' => 3.0,
                    'reception' => 3.0,
                    'punctuality' => 3.0,
                ];
                $cabinet['feedback_count'] = 0;
            }
        }
    }
    
    private function scoreResults($filtered) {
        if ($this->method === 'topsis') {
            return TOPSIS::compute($filtered, $this->criteria, self::CRITERIA_MAP, $this->weights);
        }
        return WSM::compute($filtered, $this->criteria, self::CRITERIA_MAP, $this->weights);
    }
    
    private function displayResults($results) {
        $validCriteriaCount = count($this->criteria);
        
        if (empty($results)) {
            echo '<div class="error-message" style="padding: 20px; background: #fef3cd; border: 1px solid #f0ad4e; border-radius: 8px; color: #856404; text-align: center;">';
            echo '<p style="margin: 0; font-size: 16px;">ℹ️ Aucun cabinet trouvé avec des avis patients pour les critères sélectionnés. <a href="/medicloud/cab-template/main/index.php" style="color: #2F81F7; text-decoration: underline;">Retour à l\'accueil</a></p>';
            echo '</div>';
            return;
        }
        
        echo '<div class="leaderboard-container">'; 
        echo '<div class="leaderboard-header">';
        echo '<h3>Classement des Cabinets</h3>';
        echo '<p>Méthode: <strong>' . strtoupper($results[0]['method'] ?? 'N/A') . '</strong> • Critères: <strong>' . $validCriteriaCount . '</strong></p>';
        echo '</div>';

        echo '<table>';
        echo '<thead><tr>'
            . '<th>Rang</th>'
            . '<th>Cabinet</th>'
            . '<th>Wilaya</th>'
            . '<th>Adresse</th>'
            . '<th>Téléphone</th>'
            . '<th>Tarif (DA)</th>'
            . '<th>Médecin</th>'
            . '<th>Spécialité</th>'
            . '<th>Expérience (ans)</th>'
            . '<th>Score (%)</th>'
            . '<th>Action</th>'
            . '</tr></thead>';
        echo '<tbody>';

        $rank = 1;
        foreach ($results as $r) {
            $matchingDoctors = [];
            if (!empty($r['doctors'])) {
                foreach ($r['doctors'] as $doc) {
                    $docSpec = strtolower(trim($doc['specialty'] ?? ''));
                    if (!$this->specialty || $docSpec === $this->specialty) {
                        $matchingDoctors[] = $doc;
                    }
                }
            }

            $doc = !empty($matchingDoctors) ? $matchingDoctors[0] : null;

            echo '<tr style="cursor: pointer;" onclick="window.location.href=\'/medicloud/cab-template/main/index.php\'">';
            echo '<td>' . $rank . '</td>';
            echo '<td>' . htmlspecialchars($r['name'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($r['city'] ?? '-') . '</td>';
            echo '<td>' . (!empty($r['address']) ? htmlspecialchars($r['address']) : '-') . '</td>';
            echo '<td>' . (!empty($r['phone']) ? htmlspecialchars($r['phone']) : '-') . '</td>';
            echo '<td>' . ($r['price'] ? number_format($r['price']) : '-') . '</td>';
            if ($doc) {
                echo '<td>' . htmlspecialchars($doc['name'] ?? ($doc['prenom'] ?? '') . ' ' . ($doc['nom'] ?? '')) . '</td>';
                echo '<td>' . htmlspecialchars(ucfirst($doc['specialty'] ?? '-')) . '</td>';
                echo '<td>' . (!empty($doc['years_experience']) ? (int)$doc['years_experience'] : '-') . '</td>';
            } else {
                echo '<td>-</td><td>-</td><td>-</td>';
            }
            echo '<td><strong>' . (int)($r['score'] ?? 0) . '</strong></td>';
            echo '<td><a href="/medicloud/cab-template/main/index.php" class="btn btn-primary" style="padding: 6px 12px; font-size: 14px; text-decoration: none;">Voir</a></td>';
            echo '</tr>';

            $rank++;
        }

        echo '</tbody>';
        echo '</table>';
        echo '<p style="text-align: center; margin-top: 20px;"><a href="/medicloud/cab-template/main/index.php" style="color: #2F81F7; text-decoration: underline;">← Retour à l\'accueil</a></p>';
        echo '</div>';
    }
    
    private function getSpecialtyIcon($specialty) {
        $icons = [
            'generaliste' => '👨‍⚕️',
            'pediatre' => '👶',
            'cardiologue' => '❤️',
            'dermatologue' => '🧴',
            'dentiste' => '🦷',
            'radiologue' => '📸',
            'gynecologue' => '👩‍⚕️',
            'ophtalmologue' => '👁️',
            'orl' => '👂',
            'psychiatre' => '🧠',
            'autre' => '🏥'
        ];
        return $icons[$specialty] ?? '🏥';
    }
}

// Instantiate and execute search
$search = new Search($conn);
$search->execute();

?>
