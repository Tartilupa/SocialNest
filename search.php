<?php
session_start();

// Preverimo, ali je uporabnik prijavljen
if (!isset($_SESSION['username'])) {
    echo "<p>Niste prijavljeni. Prosimo, prijavite se.</p>";
    exit();
}

// Povezava na bazo podatkov
include 'db_post.php';

// Pridobi hashtag iz URL parametra
$searchHashtag = isset($_GET['hashtag']) ? trim($_GET['hashtag']) : '';
$searchHashtag = strtolower($searchHashtag); // Normaliziraj na lowercase

if (empty($searchHashtag)) {
    header('Location: all_postsf.php');
    exit();
}

class HashtagSearch {
    private $conn;
    private $username;
    
    public function __construct($connection, $username) {
        $this->conn = $connection;
        $this->username = $username;
    }
    
    /**
     * Poišči objave z določenim hashtagom
     */
    public function searchPostsByHashtag($hashtag, $limit = 50) {
        $sql = "
            SELECT DISTINCT p.id, p.content, p.author, p.created_at, p.likes,
                   CASE 
                       WHEN ul.post_id IS NOT NULL THEN 1 
                       ELSE 0 
                   END as user_liked,
                   GROUP_CONCAT(DISTINCT ph.hashtag ORDER BY ph.hashtag SEPARATOR ', ') as post_hashtags
            FROM posts p
            JOIN post_hashtags ph ON p.id = ph.post_id
            LEFT JOIN user_likes ul ON ul.username = ? AND ul.post_id = p.id
            WHERE ph.hashtag = ?
            GROUP BY p.id, p.content, p.author, p.created_at, p.likes, ul.post_id
            ORDER BY p.created_at DESC
            LIMIT ?
        ";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Napaka pri pripravi poizvedbe: " . $this->conn->error);
        }
        
        $stmt->bind_param("ssi", $this->username, $hashtag, $limit);
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
     * Pridobi statistike za hashtag
     */
    public function getHashtagStats($hashtag) {
        $stats = [];
        
        // Skupno število objav z hashtagom
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as total_posts
            FROM post_hashtags ph
            JOIN posts p ON ph.post_id = p.id
            WHERE ph.hashtag = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $hashtag);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['total_posts'] = $row['total_posts'];
            $stmt->close();
        } else {
            $stats['total_posts'] = 0;
        }
        
        // Skupno število všečkov za objave s tem hashtagom
        $stmt = $this->conn->prepare("
            SELECT SUM(p.likes) as total_likes
            FROM post_hashtags ph
            JOIN posts p ON ph.post_id = p.id
            WHERE ph.hashtag = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $hashtag);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stats['total_likes'] = $row['total_likes'] ?? 0;
            $stmt->close();
        } else {
            $stats['total_likes'] = 0;
        }
        
        // Top avtorji za ta hashtag
        $stmt = $this->conn->prepare("
            SELECT p.author, COUNT(*) as post_count
            FROM post_hashtags ph
            JOIN posts p ON ph.post_id = p.id
            WHERE ph.hashtag = ?
            GROUP BY p.author
            ORDER BY post_count DESC
            LIMIT 5
        ");
        
        if ($stmt) {
            $stmt->bind_param("s", $hashtag);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['top_authors'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $stats['top_authors'] = [];
        }
        
        // Sorodne hashtage
        $stmt = $this->conn->prepare("
            SELECT ph2.hashtag, COUNT(*) as co_occurrence
            FROM post_hashtags ph1
            JOIN post_hashtags ph2 ON ph1.post_id = ph2.post_id
            WHERE ph1.hashtag = ? AND ph2.hashtag != ?
            GROUP BY ph2.hashtag
            ORDER BY co_occurrence DESC
            LIMIT 10
        ");
        
        if ($stmt) {
            $stmt->bind_param("ss", $hashtag, $hashtag);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats['related_hashtags'] = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $stats['related_hashtags'] = [];
        }
        
        return $stats;
    }
    
    /**
     * Posodobi uporabnikove hashtag preference na osnovi iskanja
     */
    public function updateSearchPreference($hashtag) {
        $stmt = $this->conn->prepare("
            INSERT INTO user_hashtag_interests (username, hashtag, weight, interaction_count) 
            VALUES (?, ?, 0.5, 1) 
            ON DUPLICATE KEY UPDATE 
            weight = weight + 0.5,
            interaction_count = interaction_count + 1,
            last_updated = CURRENT_TIMESTAMP
        ");
        
        if ($stmt) {
            $stmt->bind_param("ss", $this->username, $hashtag);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Inicializacija sistema
try {
    $hashtagSearch = new HashtagSearch($conn, $_SESSION['username']);
    
    // Posodobi preference (uporabnik je iskal ta hashtag)
    $hashtagSearch->updateSearchPreference($searchHashtag);
    
    // Pridobi objave in statistike
    $posts = $hashtagSearch->searchPostsByHashtag($searchHashtag, 50);
    $hashtagStats = $hashtagSearch->getHashtagStats($searchHashtag);
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px;'>";
    echo "<strong>Napaka:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
    
    // Fallback
    $posts = [];
    $hashtagStats = ['total_posts' => 0, 'total_likes' => 0, 'top_authors' => [], 'related_hashtags' => []];
}

?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hashtag #<?php echo htmlspecialchars($searchHashtag); ?> - Rezultati iskanja</title>
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

        .back-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .search-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hashtag-highlight {
            color: #ff6b6b;
            font-weight: 800;
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

        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
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
            background: linear-gradient(90deg, #ff6b6b, #ee5a52);
            transform: scaleX(1);
        }

        .post:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.15);
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

        .post-hashtag.current-hashtag {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
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

        .related-hashtags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .related-hashtag {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .related-hashtag:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
        }

        .section-title {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .author-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .author-item:last-child {
            border-bottom: none;
        }

        .author-name-small {
            font-weight: 500;
            color: #555;
        }

        .post-count {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 500;
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
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <header>
        <div class="header-content">
            <div class="header-title">
                <a href="all_postsf.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Nazaj
                </a>
                <span class="search-title">
                    Hashtag <span class="hashtag-highlight">#<?php echo htmlspecialchars($searchHashtag); ?></span>
                </span>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="main-content">
            <?php if (count($posts) > 0): ?>
                <?php 
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
                        </div>
                        
                        <div class="post-content">
                            <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                        </div>
                        
                        <?php if (!empty($post['hashtags_array'])): ?>
                        <div class="post-hashtags">
                            <?php foreach ($post['hashtags_array'] as $hashtag): ?>
                                <span class="post-hashtag <?php echo ($hashtag === $searchHashtag) ? 'current-hashtag' : ''; ?>" 
                                      onclick="searchHashtag('<?php echo htmlspecialchars($hashtag); ?>')">
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
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-posts">
                    <div class="no-posts-icon">
                        <i class="fas fa-hashtag"></i>
                    </div>
                    <h3>Ni objav z hashtag #<?php echo htmlspecialchars($searchHashtag); ?></h3>
                    <p>Poskusite z drugim hashtagom ali ustvarite prvo objavo s tem hashtagom!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar">
            <div class="stats-card">
                <h3 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistike hashtaga
                </h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $hashtagStats['total_posts']; ?></div>
                        <div class="stat-label">Objave</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $hashtagStats['total_likes']; ?></div>
                        <div class="stat-label">Všečki</div>
                    </div>
                </div>
            </div>

            <?php if (!empty($hashtagStats['top_authors'])): ?>
                <div class="stats-card">
                    <h3 class="section-title">
                        <i class="fas fa-users"></i>
                        Top avtorji
                    </h3>
                    <?php foreach ($hashtagStats['top_authors'] as $author): ?>
                        <div class="author-item">
                            <span class="author-name-small"><?php echo htmlspecialchars($author['author']); ?></span>
                            <span class="post-count"><?php echo $author['post_count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($hashtagStats['related_hashtags'])): ?>
                <div class="stats-card">
                    <h3 class="section-title">
                        <i class="fas fa-tags"></i>
                        Sorodne hashtage
                    </h3>
                    <div class="related-hashtags">
                        <?php foreach ($hashtagStats['related_hashtags'] as $related): ?>
                            <a href="search.php?hashtag=<?php echo urlencode($related['hashtag']); ?>" class="related-hashtag">
                                #<?php echo htmlspecialchars($related['hashtag']); ?>
                                <small>(<?php echo $related['co_occurrence']; ?>)</small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Like funkcionalnost
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

                        showToast('Všeček dodan!', 'success');
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
            if (hashtag !== '<?php echo $searchHashtag; ?>') {
                window.location.href = `search.php?hashtag=${encodeURIComponent(hashtag)}`;
            }
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
    </script>
</body>
</html>

<?php
$conn->close();
?>