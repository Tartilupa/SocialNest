<?php
session_start();

// Preverimo, ali je uporabnik prijavljen
if (!isset($_SESSION['username'])) {
    echo "<p>Niste prijavljeni. Prosimo, prijavite se.</p>";
    exit();
}

// Povezava na bazo podatkov
include 'db_post.php';

// Pridobivanje vseh objav iz baze v naključnem vrstnem redu
try {
    $stmt = $conn->prepare("SELECT id, content, author, created_at, likes FROM posts ORDER BY RAND()");
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    // Zapiranje povezave
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo "<p>Napaka pri pridobivanju objav: " . $e->getMessage() . "</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Objave uporabnikov</title>
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
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 0 20px;
        }

        .header-icon {
            font-size: 2rem;
            color: #667eea;
            animation: pulse 2s ease-in-out infinite;
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .posts-grid {
            display: flex;
            flex-direction: column;
            gap: 25px;
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
            background: linear-gradient(90deg, #667eea, #764ba2);
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
            filter: drop-shadow(0 2px 4px rgba(76, 175, 80, 0.3));
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

        .like-btn:active {
            transform: translateY(0);
        }

        .report-btn {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .report-btn:hover {
            background: rgba(255, 68, 68, 0.2);
            transform: translateY(-1px);
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

        .no-posts-text {
            font-size: 1.2rem;
            color: #666;
            font-weight: 500;
        }

        .no-posts-subtext {
            font-size: 1rem;
            color: #999;
            margin-top: 10px;
        }

        /* Loading animation */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .loading .like-btn::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive design */
        @media (max-width: 600px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }

            .post {
                padding: 20px;
                border-radius: 15px;
            }

            .header-title {
                font-size: 1.5rem;
            }

            .post-actions {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }

            .likes-container {
                justify-content: center;
            }
        }

        /* Hover effects za celotno objavo */
        .post {
            cursor: pointer;
        }

        .post:hover .author-avatar {
            transform: scale(1.1);
        }

        .post:hover .verified-badge {
            transform: rotate(360deg);
            transition: transform 0.6s ease;
        }

        /* Animacija za novo dodane objave */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .post {
            animation: slideInUp 0.6s ease-out;
        }

        /* Izboljšana tipografija */
        .post-content {
            text-align: justify;
            hyphens: auto;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #5a6fd8, #6a4190);
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    

    <div class="container">
        <?php if (count($posts) > 0): ?>
            <div class="posts-grid">
                <?php 
                include 'db_post.php'; // Ponovno vključimo povezavo, ker smo jo prej zaprli
                foreach ($posts as $post): 
                    // Preverimo, ali je uporabnik verificiran
                    $stmt_ver = $conn->prepare("SELECT verified FROM users WHERE name = ?");
                    $stmt_ver->bind_param("s", $post['author']);
                    $stmt_ver->execute();
                    $result_ver = $stmt_ver->get_result();
                    $user_ver = $result_ver->fetch_assoc();
                    $stmt_ver->close();
                    
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
                            <button class="report-btn" data-post-id="<?php echo $post['id']; ?>">
                                <i class="fas fa-flag"></i>
                                Prijavi
                            </button>
                        </div>
                    </div>
                <?php endforeach; 
                $conn->close();
                ?>
            </div>
        <?php else: ?>
            <div class="no-posts">
                <div class="no-posts-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="no-posts-text">Trenutno ni objav</div>
                <div class="no-posts-subtext">Bodite prvi, ki prispevate objavo!</div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Report funkcionalnost
        document.querySelectorAll('.report-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.stopPropagation();
                const postId = button.getAttribute('data-post-id');
                const reason = prompt("Zakaj želite prijaviti ta prispevek?");

                if (reason) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Prijavljam...';
                    button.disabled = true;

                    try {
                        const response = await fetch('report_post.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ post_id: postId, reason })
                        });

                        const result = await response.json();
                        
                        // Success animation
                        button.innerHTML = '<i class="fas fa-check"></i> Prijavljeno';
                        button.style.background = 'rgba(76, 175, 80, 0.1)';
                        button.style.color = '#4CAF50';
                        button.style.borderColor = 'rgba(76, 175, 80, 0.2)';
                        
                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-flag"></i> Prijavi';
                            button.style.background = '';
                            button.style.color = '';
                            button.style.borderColor = '';
                            button.disabled = false;
                        }, 2000);

                        // Toast notification
                        showToast(result.message, 'success');
                    } catch (error) {
                        button.innerHTML = '<i class="fas fa-flag"></i> Prijavi';
                        button.disabled = false;
                        showToast('Napaka pri prijavljanju objave', 'error');
                    }
                } else {
                    showToast("Prijava preklicana", 'info');
                }
            });
        });

        // Like funkcionalnost
        document.querySelectorAll('.like-btn').forEach(button => {
            button.addEventListener('click', async (e) => {
                e.stopPropagation();
                const postId = button.getAttribute('data-post-id');
                const likesElement = document.getElementById(`likes-${postId}`);
                const originalText = button.innerHTML;

                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Všečkam...';
                button.disabled = true;
                button.closest('.post').classList.add('loading');

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
                    button.closest('.post').classList.remove('loading');
                    if (button.innerHTML.includes('Všečkam...')) {
                        button.innerHTML = originalText;
                    }
                }
            });
        });

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

        // Smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add intersection observer for animation on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = `${Math.random() * 0.3}s`;
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.post').forEach(post => {
            observer.observe(post);
        });
    </script>
</body>
</html>