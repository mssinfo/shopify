<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting...</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
            font-family: Arial, sans-serif;
        }
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .redirect-btn {
            padding: 10px 20px;
            background-color: #007ace;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .redirect-btn:hover {
            background-color: #005ea6;
        }
    </style>
</head>
<body>
    <div class="loader"></div>
    <p>Redirecting... If not redirected automatically, click the button below.</p>
    <button class="redirect-btn" onclick="redirectToTop()">Go to App</button>

    <script>
        function redirectToTop() {
            try {
                if (window.top !== window.self) {
                    window.top.location.href = "{{ $url }}";
                } else {
                    window.location.href = "{{ $url }}";
                }
            } catch (error) {
                console.log('An error occurred', error);
            }
        }
        redirectToTop();
    </script>
</body>
</html>
