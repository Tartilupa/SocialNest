<?php
session_start();

// Preverimo, ali je uporabnik prijavljen
if (!isset($_SESSION['username'])) {
    echo "<p>Niste prijavljeni. Prosimo, prijavite se.</p>";
    exit();
}

// Povezava na bazo podatkov
include 'db_post.php';

class HashtagPersonalizedFeed {
    private $conn;
    private $username;
    
    public function __construct($connection, $username) {
        $this->conn = $connection;
        $this->username = $username;
    }
    
    /**
     * Ustvari tabele za sledenje uporabniških preferenc z hashtagi
     */
    public function createPreferenceTables() {
        // Najprej preverimo, ali tabela posts obstaja
        $checkPostsTable = "SHOW TABLES LIKE 'posts'";
        $result = $this->conn->query($checkPostsTable);
        
        if ($result->num_rows == 0) {
            // Če tabela posts ne obstaja, jo ustvarimo
            $createPostsTable = "CREATE TABLE IF NOT EXISTS posts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                content TEXT NOT NULL,
                author VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                likes INT DEFAULT 0
            )";
            
            if (!$this->conn->query($createPostsTable)) {
                throw new Exception("Napaka pri ustvarjanju tabele posts: " . $this->conn->error);
            }
        }
        
        // Preverimo strukturo tabele posts
        $checkPostsStructure = "DESCRIBE posts";
        $result = $this->conn->query($checkPostsStructure);
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        if (!in_array('id', $columns)) {
            throw new Exception("Tabela posts nima stolpca 'id'");
        }
        
        // Tabela za sledenje všečkov uporabnikov
        $sql1 = "CREATE TABLE IF NOT EXISTS user_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (username, post_id),
            INDEX idx_post_id (post_id),
            INDEX idx_username (username)
        )";
        
        // Tabela za sledenje hashtag interesom
        $sql2 = "CREATE TABLE IF NOT EXISTS user_hashtag_interests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            hashtag VARCHAR(255) NOT NULL,
            weight FLOAT DEFAULT 1.0,
            interaction_count INT DEFAULT 1,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_hashtag_interest (username, hashtag),
            INDEX idx_username_hashtag (username, hashtag),
            INDEX idx_hashtag (hashtag)
        )";
        
        // Tabela za sledenje avtorjem
        $sql3 = "CREATE TABLE IF NOT EXISTS user_author_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL,
            preferred_author VARCHAR(255) NOT NULL,
            preference_score FLOAT DEFAULT 1.0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_author_pref (username, preferred_author),
            INDEX idx_username_author (username, preferred_author)
        )";
        
        // Tabela za shranjevanje hashtagov iz objav
        $sql4 = "CREATE TABLE IF NOT EXISTS post_hashtags (
            id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            hashtag VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_post_hashtag (post_id, hashtag),
            INDEX idx_post_id (post_id),
            INDEX idx_hashtag (hashtag)
        )";
        
        // Izvršimo poizvedbe z error handling
        if (!$this->conn->query($sql1)) {
            throw new Exception("Napaka pri ustvarjanju tabele user_likes: " . $this->conn->error);
        }
        
        if (!$this->conn->query($sql2)) {
            throw new Exception("Napaka pri ustvarjanju tabele user_hashtag_interests: " . $this->conn->error);
        }
        
        if (!$this->conn->query($sql3)) {
            throw new Exception("Napaka pri ustvarjanju tabele user_author_preferences: " . $this->conn->error);
        }
        
        if (!$this->conn->query($sql4)) {
            throw new Exception("Napaka pri ustvarjanju tabele post_hashtags: " . $this->conn->error);
        }
        
        // Dodaj foreign key constraints
        $this->addForeignKeyConstraints();
        
        // Analiziraj obstoječe objave za hashtage
        $this->analyzeExistingPostsForHashtags();
    }
    
    /**
     * Poskusi dodati foreign key constraints
     */
    private function addForeignKeyConstraints() {
        try {
            // Check if constraints already exist
            $checkConstraints = "SELECT CONSTRAINT_NAME 
                               FROM information_schema.KEY_COLUMN_USAGE 
                               WHERE TABLE_NAME IN ('user_likes', 'post_hashtags') 
                               AND CONSTRAINT_NAME LIKE 'fk_%'
                               AND TABLE_SCHEMA = DATABASE()";
            
            $result = $this->conn->query($checkConstraints);
            
            if ($result->num_rows == 0) {
                $addFK1 = "ALTER TABLE user_likes 
                          ADD CONSTRAINT fk_user_likes_post_id 
                          FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE";
                
                $addFK2 = "ALTER TABLE post_hashtags 
                          ADD CONSTRAINT fk_post_hashtags_post_id 
                          FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE";
                
                $this->conn->query($addFK1);
                $this->conn->query($addFK2);
            }
        } catch (Exception $e) {
            // Tiho ignoriramo napake pri dodajanju foreign key constraints
        }
    }
    
    /**
     * Analiziraj obstoječe objave za hashtage
     */
    private function analyzeExistingPostsForHashtags() {
        try {
            $stmt = $this->conn->prepare("SELECT id, content FROM posts");
            if (!$stmt) return;
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $hashtags = $this->extractHashtags($row['content']);
                foreach ($hashtags as $hashtag) {
                    $this->savePostHashtag($row['id'], $hashtag);
                }
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Tiho ignoriramo napake
        }
    }
    
    /**
     * Ekstraktira hashtage iz besedila
     */
    private function extractHashtags($text) {
        // Poišči vse hashtage v formatu #beseda
        preg_match_all('/#([a-zA-ZšđčćžŠĐČĆŽ0-9_]+)/u', $text, $matches);
        
        $hashtags = [];
        if (!empty($matches[1])) {
            foreach ($matches[1] as $hashtag) {
                // Normaliziraj hashtag (lowercase, odstrani presledke)
                $normalizedHashtag = strtolower(trim($hashtag));
                if (strlen($normalizedHashtag) > 1) { // Ignoriraj preveč kratke hashtage
                    $hashtags[] = $normalizedHashtag;
                }
            }
        }
        
        return array_unique($hashtags);
    }
    
    /**
     * Shrani hashtag za objavo
     */
    private function savePostHashtag($postId, $hashtag) {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO post_hashtags (post_id, hashtag) 
            VALUES (?, ?)
        ");
        
        if ($stmt) {
            $stmt->bind_param("is", $postId, $hashtag);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    /**
     * Analizira uporabnikove všečke in posodobi hashtag preference
     */
    public function analyzeUserHashtagPreferences() {
        // Pridobi vse všečke uporabnika z hashtagi
        $stmt = $this->conn->prepare("
            SELECT p.content, p.author, ph.hashtag
            FROM user_likes ul 
            JOIN posts p ON ul.post_id = p.id 
            LEFT JOIN post_hashtags ph ON p.id = ph.post_id
            WHERE ul.username = ?
        ");
        
        if (!$stmt) {
            throw new Exception("Napaka pri pripravi poizvedbe: " . $this->conn->error);
        }
        
        $stmt->bind_param("s", $this->username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $authorPreferences = [];
        $hashtagInterests = [];
        
        while ($row = $result->fetch_assoc()) {
            // Analiziraj avtorje
            if (!isset($authorPreferences[$row['author']])) {
                $authorPreferences[$row['author']] = 0;
            }
            $authorPreferences[$row['author']]++;
            
            // Analiziraj hashtage iz baze
            if ($row['hashtag']) {
                if (!isset($hashtagInterests[$row['hashtag']])) {
                    $hashtagInterests[$row['hashtag']] = 0;
                }
                $hashtagInterests[$row['hashtag']]++;
            }
            
            // Dodatno ekstraktiraj hashtage iz vsebine (za primer, če niso v bazi)
            $contentHashtags = $this->extractHashtags($row['content']);
            foreach ($contentHashtags as $hashtag) {
                if (!isset($hashtagInterests[$hashtag])) {
                    $hashtagInterests[$hashtag] = 0;
                }
                $hashtagInterests[$hashtag]++;
            }
        }
        
        // Posodobi preference avtorjev
        foreach ($authorPreferences as $author => $count) {
            $this->updateAuthorPreference($author, $count);
        }
        
        // Posodobi hashtag interese
        foreach ($hashtagInterests as $hashtag => $count) {
            $this->updateHashtagInterest($hashtag, $count);
        }
        
        $stmt->close();
    }
    
    /**
     * Posodobi preference za avtorja
     */
    private function updateAuthorPreference($author, $score) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_author_preferences (username, preferred_author, preference_score) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            preference_score = preference_score + VALUES(preference_score),
            last_updated = CURRENT_TIMESTAMP
        ");
        
        if (!$stmt) {
            throw new Exception("Napaka pri pripravi poizvedbe za avtorje: " . $this->conn->error);
        }
        
        $stmt->bind_param("ssd", $this->username, $author, $score);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Posodobi zanimanje za hashtag
     */
    private function updateHashtagInterest($hashtag, $weight) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_hashtag_interests (username, hashtag, weight, interaction_count) 
            VALUES (?, ?, ?, 1) 
            ON DUPLICATE KEY UPDATE 
            weight = weight + VALUES(weight),
            interaction_count = interaction_count + 1,
            last_updated = CURRENT_TIMESTAMP
        ");
        
        if (!$stmt) {
            throw new Exception("Napaka pri pripravi poizvedbe za hashtage: " . $this->conn->error);
        }
        
        $stmt->bind_param("ssd", $this->username, $hashtag, $weight);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Pridobi personalizirane objave na osnovi hashtagov
     */
    public function getHashtagPersonalizedPosts($limit = 20) {
        // Najprej analiziraj preference
        $this->analyzeUserHashtagPreferences();
        
        $sql = "
            SELECT DISTINCT p.id, p.content, p.author, p.created_at, p.likes,
                   COALESCE(ap.preference_score, 0) as author_score,
                   COALESCE(hashtag_score.total_score, 0) as hashtag_score,
                   CASE 
                       WHEN ul.post_id IS NOT NULL THEN 1 
                       ELSE 0 
                   END as user_liked,
                   (
                       COALESCE(ap.preference_score, 0) * 3 + 
                       COALESCE(hashtag_score.total_score, 0) * 5 +
                       (p.likes * 0.1) +
                       (CASE WHEN p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 3 ELSE 0 END)
                   ) as relevance_score,
                   GROUP_CONCAT(DISTINCT ph.hashtag ORDER BY ph.hashtag SEPARATOR ', ') as post_hashtags
            FROM posts p
            LEFT JOIN user_author_preferences ap ON ap.username = ? AND ap.preferred_author = p.author
            LEFT JOIN user_likes ul ON ul.username = ? AND ul.post_id = p.id
            LEFT JOIN post_hashtags ph ON p.id = ph.post_id
            LEFT JOIN (
                SELECT ph2.post_id, SUM(uhi.weight) as total_score
                FROM post_hashtags ph2
                JOIN user_hashtag_interests uhi ON ph2.hashtag = uhi.hashtag AND uhi.username = ?
                GROUP BY ph2.post_id
            ) hashtag_score ON p.id = hashtag_score.post_id
            WHERE p.id NOT IN (
                SELECT COALESCE(ul2.post_id, 0) 
                FROM user_likes ul2 
                WHERE ul2.username = ?
            )
            GROUP BY p.id, p.content, p.author, p.created_at, p.likes, ap.preference_score, hashtag_score.total_score, ul.post_id
            ORDER BY relevance_score DESC, p.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Napaka pri pripravi poizvedbe za objave: " . $this->conn->error);
        }
        
        $stmt->bind_param("ssssi", $this->username, $this->username, $this->username, $this->username, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            // Dodaj hashtage v post
            $row['hashtags_array'] = $row['post_hashtags'] ? explode(', ', $row['post_hashtags']) : [];
            $posts[] = $row;
        }
        
        $stmt->close();
        return $posts;
    }
    
    /**
     * Pridobi statistike uporabnikovih hashtag preferenc
     */
    public function getUserHashtagStats() {
        $stats = [];
        
        // Top avtorji
        $stmt = $this->conn->prepare("
            SELECT preferred_author, preference_score 
            FROM user_author_preferences 
            WHERE username = ? 
            ORDER BY preference_score DESC 
            LIMIT 5
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['top_authors'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $stats['top_authors'] = [];
        }
        
        // Top hashtagi
        $stmt = $this->conn->prepare("
            SELECT hashtag, weight, interaction_count 
            FROM user_hashtag_interests 
            WHERE username = ? 
            ORDER BY weight DESC 
            LIMIT 10
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $this->username);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['top_hashtags'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $stats['top_hashtags'] = [];
        }
        
        // Trending hashtagi (globalno)
        $stmt = $this->conn->prepare("
            SELECT ph.hashtag, COUNT(*) as usage_count
            FROM post_hashtags ph
            JOIN posts p ON ph.post_id = p.id
            WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY ph.hashtag
            ORDER BY usage_count DESC
            LIMIT 5
        ");
        
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['trending_hashtags'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $stats['trending_hashtags'] = [];
        }
        
        return $stats;
    }
    
    /**
     * Pridobi podobne uporabnike na osnovi hashtagov
     */
    public function getSimilarUsers($limit = 5) {
        $stmt = $this->conn->prepare("
            SELECT other_users.username, COUNT(*) as common_hashtags,
                   AVG(ABS(my_interests.weight - other_users.weight)) as similarity_score
            FROM user_hashtag_interests my_interests
            JOIN user_hashtag_interests other_users ON my_interests.hashtag = other_users.hashtag
            WHERE my_interests.username = ? AND other_users.username != ?
            GROUP BY other_users.username
            HAVING common_hashtags >= 2
            ORDER BY common_hashtags DESC, similarity_score ASC
            LIMIT ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("ssi", $this->username, $this->username, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        
        return [];
    }
}

// Inicializacija sistema z error handling
try {
    $hashtagFeed = new HashtagPersonalizedFeed($conn, $_SESSION['username']);
    $hashtagFeed->createPreferenceTables();
    
    // Pridobi personalizirane objave
    $posts = $hashtagFeed->getHashtagPersonalizedPosts(20);
    $userStats = $hashtagFeed->getUserHashtagStats();
    $similarUsers = $hashtagFeed->getSimilarUsers(3);
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
    echo "<strong>Napaka:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    // Fallback - prikaži vse objave brez personalizacije
    $posts = [];
    $userStats = ['top_authors' => [], 'top_hashtags' => [], 'trending_hashtags' => []];
    $similarUsers = [];
    
    // Poskusi pridobiti osnovne objave
    try {
        $stmt = $conn->prepare("SELECT id, content, author, created_at, likes FROM posts ORDER BY created_at DESC LIMIT 20");
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['relevance_score'] = 0;
                $row['user_liked'] = 0;
                $row['hashtags_array'] = [];
                $posts[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $fallbackError) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
        echo "<strong>Dodatna napaka:</strong> " . htmlspecialchars($fallbackError->getMessage());
        echo "</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hashtag Personalizirane objave - Prilagojeno vašim interesom</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .background-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.1;
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255,255,255,0.3) 2px, transparent 0),
                radial-gradient(circle at 75px 75px, rgba(255,255,255,0.3) 2px, transparent 0);
            background-size: 100px 100px;
            z-index: -1;
        }

        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            font-size: 2rem;
            color: #667eea;
            animation: pulse 2s ease-in-out infinite;
        }

        .title-text {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .personalization-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 30px;
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .preference-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .preference-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .preference-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .preference-item:last-child {
            border-bottom: none;
        }

        .preference-name {
            font-weight: 500;
            color: #555;
        }

        .preference-score {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .hashtag-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 2px;
        }

        .trending-hashtag {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }

        .post {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .post::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .post:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
        }

        .post:hover::before {
            transform: scaleX(1);
        }

        .post-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            text-transform: uppercase;
        }

        .author-details {
            display: flex;
            flex-direction: column;
        }

        .author-name {
            font-weight: 600;
            color: #333;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .verified-badge {
            color: #4CAF50;
            font-size: 1.1rem;
        }

        .post-date {
            font-size: 0.85rem;
            color: #666;
            font-weight: 400;
        }

        .relevance-score {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .post-content {
            font-size: 1rem;
            line-height: 1.7;
            color: #444;
            margin: 20px 0;
            word-wrap: break-word;
        }

        .post-hashtags {
            margin: 15px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .post-hashtag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .post-hashtag:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .post-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }

        .likes-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .likes-display {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            color: #666;
        }

        .heart-icon {
            color: #e91e63;
            font-size: 1.1rem;
        }

        .like-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
        }

        .like-btn:hover {
            background: linear-gradient(135deg, #ff5252, #d32f2f);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
        }

        .no-posts {
            text-align: center;
            padding: 60px 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .no-posts-icon {
            font-size: 4rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .similar-users {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .similar-user {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .similar-user:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .sidebar {
                order: -1;
            }
            
            .header-content {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <header>
        <div class="header-content">
            <div class="header-title">
                <i class="fas fa-hashtag header-icon"></i>
                <span class="title-text">Hashtag Personalizirane objave</span>
            </div>
            <div class="personalization-indicator">
                <i class="fas fa-magic"></i>
                <span>Hashtag algorithem</span>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="main-content">
            <?php if (count($posts) > 0): ?>
                <?php 
                include 'db_post.php'; // Ponovno vključimo povezavo
                foreach ($posts as $post): 
                    // Preverimo, ali je uporabnik verificiran
                    $stmt_ver = $conn->prepare("SELECT verified FROM users WHERE name = ?");
                    if ($stmt_ver) {
                        $stmt_ver->bind_param("s", $post['author']);
                        $stmt_ver->execute();
                        $result_ver = $stmt_ver->get_result();
                        $user_ver = $result_ver->fetch_assoc();
                        $stmt_ver->close();
                    } else {
                        $user_ver = null;
                    }
                    
                    // Ustvarimo iniciale za avatar
                    $authorInitials = strtoupper(substr($post['author'], 0, 1));
                    if (strpos($post['author'], ' ') !== false) {
                        $nameParts = explode(' ', $post['author']);
                        $authorInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                    }
                ?>
                    <div class="post">
                        <div class="post-header">
                            <div class="author-info">
                                <div class="author-avatar">
                                    <?php echo $authorInitials; ?>
                                </div>
                                <div class="author-details">
                                    <div class="author-name">
                                        <?php echo htmlspecialchars($post['author']); ?>
                                        <?php if ($user_ver && $user_ver['verified'] == 1): ?>
                                            <i class="fas fa-check-circle verified-badge" title="Verificiran uporabnik"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="post-date">
                                        <i class="far fa-clock"></i>
                                        <?php echo date("d.m.Y H:i", strtotime($post['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($post['relevance_score']) && $post['relevance_score'] > 0): ?>
                            <div class="relevance-score">
                                <i class="fas fa-star"></i>
                                <?php echo number_format($post['relevance_score'], 1); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>
                        
                        <?php if (!empty($post['hashtags_array'])): ?>
                        <div class="post-hashtags">
                            <?php foreach ($post['hashtags_array'] as $hashtag): ?>
                                <span class="post-hashtag" onclick="searchHashtag('<?php echo htmlspecialchars($hashtag); ?>')">
                                    #<?php echo htmlspecialchars($hashtag); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="post-actions">
                            <div class="likes-container">
                                <div class="likes-display">
                                    <i class="fas fa-heart heart-icon"></i>
                                    <span class="likes-count" id="likes-<?php echo $post['id']; ?>">
                                        <?php echo (int)$post['likes']; ?>
                                    </span>
                                </div>
                                <button class="like-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-heart"></i>
                                    Všeč mi je
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; 
                $conn->close();
                ?>
            <?php else: ?>
                <div class="no-posts">
                    <div class="no-posts-icon">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <div class="no-posts-text">Še nimamo dovolj hashtag podatkov za personalizacijo</div>
                    <div class="no-posts-subtext">Všečkajte objave z hashtagi, da lahko prilagodimo vsebino vašim interesom!</div>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar">
            <?php if (!empty($userStats['top_hashtags'])): ?>
                <div class="preference-card">
                    <h3>
                        <i class="fas fa-hashtag"></i>
                        Vaši priljubljeni hashtagi
                    </h3>
                    <?php foreach ($userStats['top_hashtags'] as $hashtag): ?>
                        <div class="preference-item">
                            <span class="hashtag-item">
                                #<?php echo htmlspecialchars($hashtag['hashtag']); ?>
                            </span>
                            <span class="preference-score"><?php echo number_format($hashtag['weight'], 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($userStats['trending_hashtags'])): ?>
                <div class="preference-card">
                    <h3>
                        <i class="fas fa-fire"></i>
                        Trending hashtagi
                    </h3>
                    <?php foreach ($userStats['trending_hashtags'] as $hashtag): ?>
                        <div class="preference-item">
                            <span class="hashtag-item trending-hashtag">
                                #<?php echo htmlspecialchars($hashtag['hashtag']); ?>
                            </span>
                            <span class="preference-score"><?php echo $hashtag['usage_count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($userStats['top_authors'])): ?>
                <div class="preference-card">
                    <h3>
                        <i class="fas fa-users"></i>
                        Priljubljeni avtorji
                    </h3>
                    <?php foreach ($userStats['top_authors'] as $author): ?>
                        <div class="preference-item">
                            <span class="preference-name"><?php echo htmlspecialchars($author['preferred_author']); ?></span>
                            <span class="preference-score"><?php echo number_format($author['preference_score'], 1); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($similarUsers)): ?>
                <div class="similar-users">
                    <h3>
                        <i class="fas fa-user-friends"></i>
                        Podobni uporabniki
                    </h3>
                    <?php foreach ($similarUsers as $user): ?>
                        <div class="similar-user">
                            <span class="preference-name"><?php echo htmlspecialchars($user['username']); ?></span>
                            <span class="preference-score"><?php echo $user['common_hashtags']; ?> skupnih</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Like funkcionalnost z hashtag analizo
        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.stopPropagation();
                const postId = button.getAttribute('data-post-id');
                const likesElement = document.getElementById(`likes-${postId}`);
                const originalText = button.innerHTML;

                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Všečkam...';
                button.disabled = true;

                try {
                    const response = await fetch('like_post.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ post_id: postId })
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Animiraj spremembo števila všečkov
                        likesElement.style.transform = 'scale(1.3)';
                        likesElement.style.color = '#e91e63';
                        setTimeout(() => {
                            likesElement.textContent = data.likes;
                            likesElement.style.transform = 'scale(1)';
                            likesElement.style.color = '';
                        }, 150);

                        // Success state za gumb
                        button.innerHTML = '<i class="fas fa-heart"></i> Všeč mi je!';
                        button.style.background = 'linear-gradient(135deg, #4CAF50, #45a049)';
                        
                        setTimeout(() => {
                            button.innerHTML = originalText;
                            button.style.background = '';
                        }, 1500);

                        showToast('Všeček dodan! Posodabljamo vaše hashtag preference.', 'success');
                        
                        // Osveži stran po kratkem času za posodobitev hashtag preferenc
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showToast(data.message, 'error');
                    }
                } catch (error) {
                    showToast('Napaka pri všečkanju', 'error');
                } finally {
                    button.disabled = false;
                    if (button.innerHTML.includes('Všečkam...')) {
                        button.innerHTML = originalText;
                    }
                }
            });
        });

        // Hashtag search funkcionalnost
        function searchHashtag(hashtag) {
            showToast(`Iščemo objave z hashtag #${hashtag}`, 'info');
            // Implementiraj iskanje po hashtagu
            window.location.href = `search.php?hashtag=${encodeURIComponent(hashtag)}`;
        }

        // Toast notification system
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
                    <span>${message}</span>
                </div>
            `;

            // Add toast styles
            const toastStyles = `
                .toast {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 10px;
                    color: white;
                    font-weight: 500;
                    z-index: 1000;
                    transform: translateX(100%);
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                    backdrop-filter: blur(20px);
                    max-width: 300px;
                }
                .toast.show {
                    transform: translateX(0);
                }
                .toast-success {
                    background: linear-gradient(135deg, rgba(76, 175, 80, 0.9), rgba(69, 160, 73, 0.9));
                }
                .toast-error {
                    background: linear-gradient(135deg, rgba(244, 67, 54, 0.9), rgba(211, 47, 47, 0.9));
                }
                .toast-info {
                    background: linear-gradient(135deg, rgba(33, 150, 243, 0.9), rgba(25, 118, 210, 0.9));
                }
                .toast-content {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
            `;

            if (!document.getElementById('toast-styles')) {
                const styleElement = document.createElement('style');
                styleElement.id = 'toast-styles';
                styleElement.textContent = toastStyles;
                document.head.appendChild(styleElement);
            }

            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => document.body.removeChild(toast), 300);
            }, 3000);
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>