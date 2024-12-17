<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="icon" href="favicon.ico" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Welcome to CodeIgniter 4 Alternative</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 90%;
            margin: 10px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 2.2rem;
            color: #ee4323;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .logo img {
            width: 50px;
            height: auto;
        }

        .version {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .description {
            font-size: 1.1rem;
            color: #444;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .highlight {
            color: #ee4323;
            font-weight: bold;
        }

        .footer {
            font-size: 0.8rem;
            color: #888;
            margin-top: 20px;
        }

        a {
            color: #ee4323;
            text-decoration: none;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        .button {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #ee4323;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 1rem;
        }

        .button:hover {
            background-color: #d63317;
        }

        @media screen and (max-width: 768px) {
            .logo {
                font-size: 1.8rem;
            }

            .logo img {
                width: 40px;
            }

            .description {
                font-size: 1rem;
            }

            .button {
                font-size: 0.9rem;
                padding: 8px 16px;
            }
        }

        @media screen and (max-width: 480px) {
            .logo {
                font-size: 1.5rem;
            }

            .logo img {
                width: 35px;
            }

            .description {
                font-size: 0.9rem;
            }

            .button {
                font-size: 0.8rem;
                padding: 6px 12px;
            }

            .footer {
                font-size: 0.7rem;
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
        <div class="version">Version 1.0.0</div>
        <p class="description">
           <i class="fas fa-handshake"></i>
 Welcome to <span class="highlight">CodeIgniter 4 Alternative</span> – a lightweight, fast, and elegant PHP framework designed by <a href="https://inforte.uz" target="_blank">Inforte</a>!
        </p>
        <a href="https://inforte.uz/codeigniter-alternative/" target="_blank" class="button"> <i class="fas fa-book"> Visit Documentation</i></a>

        <div class="footer">
            &copy; <?php echo date("Y"); ?> CodeIgniter 4 Alternative | Developed by <a href="https://inforte.uz" target="_blank">Inforte</a>.
        </div>
    </div>
</body>
</html>