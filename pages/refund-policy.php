<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refund Policy – Funding4x</title>
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
        .term-section ul {
            list-style-type: none;
            padding-left: 0;
        }
        .term-section li {
            margin-bottom: 0.5rem;
            padding-left: 1.5rem;
            position: relative;
            color: #4b5563; /* gray-600 */
        }
        .term-section li::before {
            content: "•";
            color: #b49852; /* trophy-gold */
            font-weight: bold;
            display: inline-block;
            width: 1em;
            margin-left: -1em;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="header-bg text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
        </div>
    </header>

    <!-- Main Content: Refund Policy -->
    <main class="flex-grow flex justify-center p-4 sm:p-8">
        <div class="w-full max-w-4xl">
            <div class="bg-card-white p-8 rounded-2xl shadow-2xl border-t-8 border-trophy-gold">
                <h1 class="text-4xl font-extrabold text-header-dark mb-2">Refund Policy</h1>
                <p class="text-sm text-gray-500 mb-6">Last Updated: 24-11-2025</p>
                
                <p class="mb-8 text-lg text-gray-700 leading-relaxed border-b pb-4">
                    This Refund Policy explains how Funding4x (“we”, “us”, “our”) handles refunds for our paid evaluation programs. By purchasing a paid evaluation, you agree to the terms outlined in this policy.
                </p>

                <!-- 1. Paid Evaluation Programs -->
                <div class="term-section">
                    <h2 class="text-xl">1. Paid Evaluation Programs</h2>
                    <p class="mb-4 text-gray-600">
                        Funding4x may offer paid trial/evaluation programs in the future, designed to assess your trading skills and provide potential access to funded accounts.
                    </p>
                </div>

                <!-- 2. No Refund for Participation Fees -->
                <div class="term-section">
                    <h2 class="text-xl">2. No Refund for Participation Fees</h2>
                    <p class="mb-4 text-gray-600">
                        All fees paid for evaluation programs are non-refundable once the evaluation access is granted.
                    </p>
                    <ul>
                        <li>The fee covers access to our platform, tools, and services for the duration of the evaluation.</li>
                        <li>Refunds will not be issued for missed deadlines, failure to complete the evaluation, or failure to achieve funding.</li>
                    </ul>
                </div>

                <!-- 3. Refund Exceptions -->
                <div class="term-section bg-primary-purple/5 border-l-4 border-secondary-purple">
                    <h2 class="text-xl text-primary-purple">3. Refund Exceptions</h2>
                    <p class="mb-4 text-gray-600">Refunds may only be considered under the following circumstances:</p>

                    <h3 class="font-semibold text-gray-700 mt-4 mb-2">Technical Errors on Our Side</h3>
                    <p class="text-sm text-gray-600 mb-2">If Funding4x fails to provide access to the program due to technical issues outside your control, we may offer:</p>
                    <ul>
                        <li>Full refund</li>
                        <li>Credit for a future evaluation</li>
                    </ul>

                    <h3 class="font-semibold text-gray-700 mt-4 mb-2">Duplicate Payment</h3>
                    <p class="text-sm text-gray-600">If you accidentally pay multiple times for the same evaluation, we will refund the duplicate payment upon verification.</p>

                    <p class="text-sm font-semibold text-red-600 mt-4">All refund requests must be submitted within 7 days of payment.</p>
                </div>

                <!-- 4. How to Request a Refund -->
                <div class="term-section">
                    <h2 class="text-xl">4. How to Request a Refund</h2>
                    <p class="text-gray-600">To request a refund (if eligible), email us at:</p>
                    <p class="font-semibold text-primary-purple text-lg mt-3">Email: <a href="mailto:support@funding4x.com" class="hover:underline">support@funding4x.com</a></p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>