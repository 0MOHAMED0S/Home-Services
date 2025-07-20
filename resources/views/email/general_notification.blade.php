<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        h2 {
            color: #2c3e50;
            font-size: 24px;
            margin-bottom: 20px;
        }

        p {
            color: #555555;
            font-size: 16px;
            line-height: 1.6;
        }

        .footer {
            margin-top: 40px;
            font-size: 14px;
            color: #999999;
            text-align: center;
        }

        @media (max-width: 600px) {
            .container {
                padding: 20px;
                margin: 10px;
            }

            h2 {
                font-size: 20px;
            }

            p {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>{{ $title }}</h2>
        <p>{{ $body }}</p>

        <div class="footer">
            <p>Best regards,<br><strong>Home Services</strong></p>
        </div>
    </div>

</body>
</html>

