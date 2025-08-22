<?php
session_start(); // Start session

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}

$username = $_SESSION['username']; // Get logged-in username
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AlgoAI</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body {
            background-color: #071c39;
            font-family: 'Roboto', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            position: relative;
        }
        
        .gradient-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0081ff, #ff66b2, #32c5ff);
            background-size: 300% 300%;
            animation: gradientAnimation 15s ease infinite;
            z-index: -1;
        }

        @keyframes gradientAnimation {
            0% {
                background-position: 100% 50%;
            }
            50% {
                background-position: 0% 50%;
            }
            100% {
                background-position: 100% 50%;
            }
        }

        .user-menu {
            background-color: rgba(33, 37, 41, 0.9);
            padding: 8px 0;
            width: 100%;
        }

        .user-menu-content {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 0 20px;
        }

        .navbar {
            background-color: rgba(52, 58, 64, 0.8);
            border-radius: 0 0 10px 10px;
        }
        
        .navbar-brand, .navbar-text {
            color: #fff;
        }

        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            margin-top: 20px;
        }

        #chatBox {
            width: 100%;
            max-width: 800px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 20px;
            height: 0;
            transition: height 0.5s ease-in-out;
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            max-width: 80%;
        }

        .user-message {
            background-color: #dcf8c6;
            align-self: flex-end;
        }

        .bot-message {
            background-color: #f8d7da;
            align-self: flex-start;
        }

        #userInput {
            border-radius: 30px;
            padding: 12px 20px;
            font-size: 1rem;
            width: 85%;
        }

        .input-bar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 800px;
            margin-top: 15px;
        }

        .input-bar-container button {
            border-radius: 50%;
            padding: 12px;
            font-size: 1.3rem;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            width: 55px;
            height: 55px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .input-bar-container button:hover {
            background-color: #218838;
        }

        .user-info {
            color: white;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <!-- Gradient Background -->
    <div class="gradient-background"></div>

    <!-- User Menu -->
    <div class="user-menu">
    
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">MathematicalDB</a>
            <span class="user-info">
                Logged in as: <strong><?php echo htmlspecialchars($username); ?></strong>
            </span>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <h2 class="text-white text-center mb-4">MathematicalDB - AlgoAI</h2>

        <!-- Chat history -->
        <div id="chatBox" class="d-flex flex-column"></div>

        <!-- Input field and send button -->
        <div class="input-bar-container">
            <input type="text" class="form-control" id="userInput" placeholder="Ask something..." onkeypress="checkEnter(event)">
            <button class="btn btn-success" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        function checkEnter(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        async function sendMessage() {
            const input = document.getElementById('userInput').value;
            const chatBox = document.getElementById('chatBox');

            if (!input) {
                alert("Please enter a question.");
                return;
            }

            chatBox.innerHTML += `<div class="message user-message"><strong>You:</strong> ${input}</div>`;
            chatBox.style.height = 'auto';
            chatBox.style.transition = 'height 0.5s ease-in-out';
            document.getElementById('userInput').value = "";
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const response = await fetch("https://openrouter.ai/api/v1/chat/completions", {
                    method: "POST",
                    headers: {
                        "Authorization": "", // Replace with your API key
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        "model": "mistralai/mistral-7b-instruct:free",
                        "messages": [{ "role": "user", "content": input }]
                    })
                });

                const data = await response.json();
                const markdownText = data.choices?.[0]?.message?.content || 'No response received.';
                chatBox.innerHTML += `<div class="message bot-message"><strong>AlgoAI:</strong> ${marked.parse(markdownText)}</div>`;
                chatBox.scrollTop = chatBox.scrollHeight;

            } catch (error) {
                chatBox.innerHTML += `<div class="alert alert-danger">Error: ${error.message}</div>`;
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        }
    </script>
</body>
</html>
