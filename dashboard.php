<?php
session_start();

include 'db.php'; // tvoj PDO ali MySQLi povezava

// Preveri, ali je uporabnik prijavljen
if (!isset($_SESSION['username'])) {
    header('Location: index.html'); // Preusmeri na prijavno stran
    exit();
}

$username = $_SESSION['username'];

$ip = $_SERVER['REMOTE_ADDR'];
$stmt = $pdo->prepare("UPDATE users SET ip = :ip WHERE username = :u");
$stmt->execute(['ip' => $ip, 'u' => $_SESSION['username']]);

// Funkcija za pridobivanje države uporabnika preko geolokacije

// Naloži ustrezen prevod
$translations = include "translations/en.php"; // Predpostavimo, da imaš prevode v mapah "translations/sl.php" in "translations/en.php"
?>

<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $translations['dashboard_title']; ?></title>
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
            overflow-x: hidden;
        }

        .background-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0.05;
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
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header-icon {
            font-size: 2rem;
            color: #667eea;
            animation: float 3s ease-in-out infinite;
        }

        .header-title {
            font-size: 1.8rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 25px;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-transform: uppercase;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .welcome-text {
            font-size: 0.85rem;
            color: #666;
            font-weight: 400;
        }

        .username {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .dashboard {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        nav {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
            padding: 30px 0;
            display: flex;
            flex-direction: column;
            gap: 8px;
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            overflow-y: auto;
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.1);
        }

        .nav-section {
            padding: 0 25px;
            margin-bottom: 20px;
        }

        .nav-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        nav a {
            color: #555;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            padding: 12px 25px;
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            margin-bottom: 4px;
        }

        nav a i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
        }

        nav a:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: #667eea;
            transform: translateX(5px);
        }

        nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .logout-link {
            margin-top: auto;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            padding-top: 20px;
        }

        .logout-link a {
            color: #ff4757;
            background: rgba(255, 71, 87, 0.1);
        }

        .logout-link a:hover {
            background: rgba(255, 71, 87, 0.2);
            color: #ff3742;
        }

        .content {
            flex: 1;
            padding: 30px;
            background: transparent;
            overflow-y: auto;
        }

        .content-header {
            margin-bottom: 30px;
        }

        .content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .content-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }

        .widget {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .widget:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .widget h2 {
            font-size: 1.4rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-icon {
            font-size: 1.2rem;
            color: #667eea;
        }

        .new-post {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .new-post h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .new-post form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .new-post textarea {
            width: 100%;
            min-height: 120px;
            padding: 18px;
            border: 2px solid rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
        }

        .new-post textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: white;
        }

        .new-post textarea::placeholder {
            color: #999;
            font-style: italic;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            align-self: flex-start;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .submit-btn.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .submit-btn.loading::after {
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

        .frame {
            width: 100%;
            height: 600px;
            border: none;
            border-radius: 15px;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .posts-container {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .posts-container p {
            color: #666;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }

        footer {
            text-align: center;
            padding: 30px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
            background: rgba(0, 0, 0, 0.1);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        footer code {
            background: rgba(255, 255, 255, 0.1);
            padding: 5px 10px;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .dashboard {
                flex-direction: column;
            }

            nav {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                overflow-x: auto;
                overflow-y: hidden;
                padding: 15px;
                gap: 5px;
            }

            .nav-section {
                display: flex;
                gap: 5px;
                padding: 0;
                margin: 0;
                min-width: max-content;
            }

            .nav-title {
                display: none;
            }

            nav a {
                white-space: nowrap;
                padding: 10px 15px;
                margin: 0;
                min-width: max-content;
            }

            .logout-link {
                margin-top: 0;
                border-top: none;
                padding-top: 0;
            }

            .content {
                padding: 20px;
            }

            .content h1 {
                font-size: 2rem;
            }

            header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
                padding: 20px;
            }

            .header-user {
                order: -1;
            }

            .frame {
                height: 400px;
            }
        }

        @media (max-width: 480px) {
            .content {
                padding: 15px;
            }

            .widget, .new-post {
                padding: 20px;
                border-radius: 15px;
            }

            .content h1 {
                font-size: 1.8rem;
            }
        }

        /* Success animation */
        @keyframes success {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); background: linear-gradient(135deg, #4CAF50, #45a049); }
            100% { transform: scale(1); }
        }

        .submit-btn.success {
            animation: success 0.6s ease;
        }

        /* Card hover effects */
        .widget, .new-post {
            position: relative;
            overflow: hidden;
        }

        .widget::before, .new-post::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }

        .widget:hover::before, .new-post:hover::before {
            left: 100%;
        }

        /* Improved focus states */
        nav a:focus, .submit-btn:focus, .new-post textarea:focus {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }

        /* Custom scrollbar for nav */
        nav::-webkit-scrollbar {
            width: 6px;
        }

        nav::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        nav::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
        }

        /* Loading state for entire page */
        .page-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(102, 126, 234, 0.1);
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* Enhanced admin badge */
        .admin-badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Glassmorphism enhancement */
        .glass {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        /* Animation delays for staggered loading */
        .widget:nth-child(1) { animation-delay: 0.1s; }
        .widget:nth-child(2) { animation-delay: 0.2s; }
        .widget:nth-child(3) { animation-delay: 0.3s; }
        .new-post { animation-delay: 0.4s; }

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

        .widget, .new-post {
            animation: slideInUp 0.6s ease-out both;
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <header>
        <div class="header-left">
            <i class="fas fa-tachometer-alt header-icon"></i>
            <h1 class="header-title"><?php echo $translations['site_title']; ?></h1>
        </div>
        <div class="header-user">
            <div class="user-avatar">
                <?php 
                $userInitials = strtoupper(substr($username, 0, 1));
                if (strpos($username, ' ') !== false) {
                    $nameParts = explode(' ', $username);
                    $userInitials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                }
                echo $userInitials; 
                ?>
            </div>
            <div class="user-info">
                <span class="welcome-text"><?php echo $translations['welcome_user']; ?></span>
                <span class="username"><?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
    </header>

    <div class="dashboard">
        <nav>
            <div class="nav-section">
                <div class="nav-title">Glavno</div>
                <a href="dashboard.php" class="active">
                    <i class="fas fa-home"></i> 
                    <?php echo $translations['home']; ?>
                </a>
                <a href="get_post.php">
                    <i class="fas fa-user-edit"></i> 
                    <?php echo $translations['your_posts']; ?>
                </a>
                <a href="profile.php">
                    <i class="fa-solid fa-user"></i>
                    Profile
            </div>

            <div class="nav-section">
                <div class="nav-title">Orodja</div>
                <a href="base.php">
                    <i class="fas fa-database"></i> 
                    Base
                </a>
                <?php if ($username === 'test' || $username === 'q'): ?>
                    <a href="panel.php" style="position: relative;">
                        <i class="fas fa-user-shield"></i> 
                        Admin Panel
                        <span class="admin-badge">ADMIN</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="logout-link">
                <a href="/logout.php">
                    <i class="fas fa-sign-out-alt"></i> 
                    <?php echo $translations['logout']; ?>
                </a>
            </div>
        </nav>

        <div class="content">
            <div class="content-header">
                <h1><?php echo $translations['dashboard_title']; ?></h1>
                <p class="content-subtitle">Welcome back. We are happy with you!</p>
            </div>

            <div class="widget">
                <h2>
                    <i class="fas fa-star widget-icon"></i>
                    <?php echo $translations['important_posts']; ?>
                </h2>
                <div id="posts-container" class="posts-container">
                    <p><?php echo $translations['no_important_posts']; ?></p>
                </div>
            </div>

            <div class="new-post">
                <h2>
                    <i class="fas fa-plus-circle widget-icon"></i>
                    <?php echo $translations['add_post']; ?>
                </h2>
                <form id="post-form" action="add_post.php" method="POST">
                    <textarea id="pris" name="post_content" placeholder="<?php echo $translations['enter_post_content']; ?>" required></textarea>
                    <button type="button" class="submit-btn" onclick="obj()">
                        <i class="fas fa-paper-plane"></i>
                        <?php echo $translations['submit']; ?>
                    </button>
                </form>
            </div>

            <div class="widget">
                <h2>
                    <i class="fas fa-feed widget-icon"></i>
                    Vse objave
                </h2>
                <iframe class="frame" src="all_postsf.php"></iframe>
            </div>
        </div>
    </div>

    <footer>
        <code>Link Nest &copy; <span id="currentYear"></span></code>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var currentYear = new Date().getFullYear();
            document.getElementById('currentYear').textContent = currentYear;
            });
    </script>

    <script>
        // Enhanced submit function
        async function obj() {
            const prisElement = document.getElementById('pris');
            const submitBtn = document.querySelector('.submit-btn');
            const pris = prisElement.value.trim();

            if (!pris) {
                showToast('<?php echo $translations['fill_post']; ?>', 'error');
                prisElement.focus();
                return;
            }

            // Loading state
            submitBtn.classList.add('loading');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Objavljam...';

            try {
                const response = await fetch('./add_post.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({pris})
                });

                const result = await response.json();
                
                if (response.ok) {
                    // Success state
                    submitBtn.classList.remove('loading');
                    submitBtn.classList.add('success');
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Objavljeno!';
                    
                    prisElement.value = '';
                    showToast('Objava je bila uspešno dodana!', 'success');
                    
                    // Refresh iframe
                    const iframe = document.querySelector('.frame');
                    iframe.src = iframe.src;
                    
                    setTimeout(() => {
                        submitBtn.classList.remove('success');
                        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <?php echo $translations['submit']; ?>';
                    }, 2000);
                } else {
                    throw new Error(result.message || 'Napaka pri objavljanju');
                }
            } catch (error) {
                submitBtn.classList.remove('loading');
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> <?php echo $translations['submit']; ?>';
                showToast('Napaka pri objavljanju: ' + error.message, 'error');
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

            // Add toast styles if not already added
            if (!document.getElementById('toast-styles')) {
                const toastStyles = `
                    .toast {
                        position: fixed;
                        top: 100px;
                        right: 20px;
                        padding: 15px 20px;
                        border-radius: 12px;
                        color: white;
                        font-weight: 500;
                        z-index: 1000;
                        transform: translateX(100%);
                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
                        backdrop-filter: blur(20px);
                        max-width: 300px;
                        min-width: 250px;
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

                const styleElement = document.createElement('style');
                styleElement.id = 'toast-styles';
                styleElement.textContent = toastStyles;
                document.head.appendChild(styleElement);
            }

            document.body.appendChild(toast);

            setTimeout(() => toast.classList.add('show'), 100);
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, 4000);
        }

        // Auto-resize textarea
        const textarea = document.getElementById('pris');
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(120, this.scrollHeight) + 'px';
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter za objavo
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                obj();
            }
        });

        // Set active nav link based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('nav a');
            
            navLinks.forEach(link => {
                const linkPage = link.getAttribute('href');
                if (linkPage === currentPage) {
                    link.classList.add('active');
                } else {
                    link.classList.remove('active');
                }
            });
        });

        // Smooth scrolling
        document.documentElement.style.scrollBehavior = 'smooth';

        // Add loading animation on page load
        window.addEventListener('load', function() {
            const loader = document.querySelector('.page-loading');
            if (loader) {
                loader.style.opacity = '0';
                setTimeout(() => loader.remove(), 300);
            }
        });
    </script>
</body>
</html>