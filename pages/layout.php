<?php
// Common layout for pages
// Variables expected: $title, $border_color, $page_title, $last_updated, $intro, $content
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> – Funding4x</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon-16x16.png">
    <link rel="manifest" href="../assets/site.webmanifest">

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Theme configuration reused for consistency
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        'primary-purple': '#4f009d',
                        'secondary-purple': '#7b2cbf',
                        'trophy-gold': '#b49852',
                        'header-dark': '#240046',
                        'bg-light': '#f3f4f6',
                        'cta-hover': '#9d7c49',
                        'card-white': '#ffffff',
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            color: #240046;
        }
        .header-bg {
            background-color: #240046;
        }
        .term-section {
            padding: 1.5rem;
            margin-bottom: 1rem;
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }
        .term-section h2 {
            font-weight: 700;
            color: #4f009d; /* primary-purple */
            margin-bottom: 0.75rem;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.5rem;
        }
        .term-section p, .term-section li {
             color: #4b5563; /* gray-600 */
        }
        .term-section ul {
            list-style-type: none;
            padding-left: 0;
        }
        .term-section li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
        }
        .term-section li::before {
            content: "•";
            color: #b49852; /* trophy-gold */
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
        .sub-heading {
            font-weight: 600;
            color: #240046;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .sublist {
            margin-top: 0.5rem;
            margin-left: 1rem;
        }
        .sublist li::before {
             content: "—";
        }
        .link-text {
            color: #7b2cbf; /* secondary-purple */
            font-weight: 500;
            transition: color 0.2s;
        }
        .link-text:hover {
            color: #4f009d; /* primary-purple */
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="header-bg text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo Section -->
                <div class="flex items-center">
                    <img src="../assets/logo.png" alt="Funding4X Logo" class="h-10 w-10 mr-3 rounded-lg">
                    <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow flex justify-center p-4 sm:p-8">
        <div class="w-full max-w-4xl">
            <div class="bg-card-white p-8 rounded-2xl shadow-2xl border-t-8 border-<?php echo $border_color; ?>">
                <h1 class="text-4xl font-extrabold text-header-dark mb-2"><?php echo $page_title; ?></h1>
                <p class="text-sm text-gray-500 mb-6"><?php echo $last_updated; ?></p>

                <p class="mb-8 text-lg text-gray-700 leading-relaxed border-b pb-4">
                    <?php echo $intro; ?>
                </p>

                <?php echo $content; ?>

            </div>
        </div>
    </main>
</body>
</html>