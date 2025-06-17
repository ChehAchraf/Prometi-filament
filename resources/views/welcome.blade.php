<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Welcome</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
        <style>
            body {
                font-family: 'Figtree', sans-serif;
                margin: 0;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: #f3f4f6;
            }
            .container {
                text-align: center;
                padding: 2rem;
            }
            .button {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                margin: 0.5rem;
                font-size: 1rem;
                font-weight: 600;
                text-decoration: none;
                border-radius: 0.375rem;
                transition: all 0.2s;
            }
            .login {
                background-color: #ef4444;
                color: white;
            }
            .register {
                background-color: white;
                color: #ef4444;
                border: 2px solid #ef4444;
            }
            .button:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <a href="/admin" class="button login">Admin Panel</a>
            <a href="/login" class="button login">Log in</a>
            <a href="/register" class="button register">Register</a>
        </div>
    </body>
</html>
