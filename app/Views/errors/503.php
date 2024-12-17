<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="favicon.ico" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>503 - Service Unavailable</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            animation: fadeIn 1s ease-out;
        }

        .container {
            text-align: center;
            padding: 40px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0px 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 90%;
            margin: 10px;
            transform: translateY(50px);
            animation: slideUp 1s ease-out forwards;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 2.4rem;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 20px;
            animation: bounce 1s infinite alternate;
        }

        .logo img {
            width: 60px;
            height: auto;
            transition: transform 0.3s ease;
        }

        .logo:hover img {
            transform: rotate(360deg);
        }

        .error-title {
            font-size: 3rem;
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 15px;
            opacity: 0;
            animation: fadeInText 1s 0.5s forwards;
        }

        .error-message {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 25px;
            line-height: 1.6;
            opacity: 0;
            animation: fadeInText 1s 1s forwards;
        }

        .footer {
            font-size: 0.8rem;
            color: #777;
            margin-top: 20px;
        }

        a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.3s ease, text-decoration 0.3s ease;
        }

        a:hover {
            text-decoration: underline;
            transform: scale(1.1);
        }

        .button {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 25px;
            background-color: #e74c3c;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s ease-in-out;
            font-size: 1.1rem;
        }

        .button:hover {
            background-color: #c0392b;
            transform: translateY(-5px);
        }

        @media screen and (max-width: 768px) {
            .logo {
                font-size: 2rem;
            }

            .logo img {
                width: 50px;
            }

            .error-title {
                font-size: 2.5rem;
            }

            .error-message {
                font-size: 1rem;
            }

            .button {
                font-size: 1rem;
                padding: 10px 20px;
            }
        }

        @media screen and (max-width: 480px) {
            .logo {
                font-size: 1.8rem;
            }

            .logo img {
                width: 40px;
            }

            .error-title {
                font-size: 2rem;
            }

            .error-message {
                font-size: 0.9rem;
            }

            .button {
                font-size: 0.9rem;
                padding: 8px 15px;
            }

            .footer {
                font-size: 0.7rem;
            }
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            0% {
                transform: translateY(50px);
            }
            100% {
                transform: translateY(0);
            }
        }

        @keyframes bounce {
            0% {
                transform: translateY(0);
            }
            100% {
                transform: translateY(-10px);
            }
        }

        @keyframes fadeInText {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="https://cdn.worldvectorlogo.com/logos/codeigniter.svg" alt="CodeIgniter Logo">
            CodeIgniter 4 Alternative
        </div>
        <div class="error-title">503 - Service Unavailable</div>
        <p class="error-message">
            Oops! We are currently undergoing maintenance or the server is temporarily unavailable. Please try again later.
        </p>
        <a href="/" class="button"> <i class="fas fa-home"> Go to «Home Page» </i></a>

        <div class="footer">
            &copy; <?php echo date("Y"); ?> CodeIgniter 4 Alternative | Developed by <a href="https://inforte.uz" target="_blank">Inforte</a>.
        </div>
    </div>
</body>
</html>