<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Disclaimers – Funding4x</title>
    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Reusing the theme configuration for consistency
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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
        </div>
    </header>

    <!-- Main Content: Terms & Disclaimers -->
    <main class="flex-grow flex justify-center p-4 sm:p-8">
        <div class="w-full max-w-4xl">
            <div class="bg-card-white p-8 rounded-2xl shadow-2xl border-t-8 border-primary-purple">
                <h1 class="text-4xl font-extrabold text-header-dark mb-2">Terms & Disclaimers</h1>
                <p class="text-sm text-gray-500 mb-6">Last Updated: 24-11-2025</p>
                
                <p class="mb-8 text-lg text-gray-700 leading-relaxed border-b pb-4">
                    Before creating an account with <span class="font-bold text-primary-purple">Funding4x</span>, please read and agree to the following summary of our Terms and Disclaimers. By ticking the checkbox, you confirm that you understand and accept these points and agree to be bound by our full policies.
                </p>

                <!-- 1. Account Use -->
                <div class="term-section">
                    <h2 class="text-xl">1. Account Use</h2>
                    <ul>
                        <li>Your account is for demo/simulated evaluation purposes only.</li>
                        <li>No real funds are used or traded in the trial phase.</li>
                        <li>Performance results during the trial do not guarantee future funding or profits.</li>
                    </ul>
                    <a href="term-conditions.php" class="text-sm link-text">Read full Terms and Conditions &rarr;</a>
                </div>

                <!-- 2. Risk Acknowledgment -->
                <div class="term-section">
                    <h2 class="text-xl">2. Risk Acknowledgment</h2>
                    <ul>
                        <li>Trading, even simulated, involves risk and is not suitable for everyone.</li>
                        <li>You are responsible for managing your trading, psychological, and emotional risk.</li>
                        <li>Funding4x does not guarantee profits or funding based on trial results.</li>
                    </ul>
                    <a href="risk-disclosure.php" class="text-sm link-text">Read full Risk Disclosure &rarr;</a>
                </div>

                <!-- 3. Privacy and Data Use -->
                <div class="term-section">
                    <h2 class="text-xl">3. Privacy and Data Use</h2>
                    <ul>
                        <li>We collect your personal information (e.g., name, email, IP, trading activity).</li>
                        <li>Your data may be used for platform management, marketing, and analytics.</li>
                        <li>You consent to receive service and marketing emails from Funding4x.</li>
                    </ul>
                    <a href="privacy-policy.php" class="text-sm link-text">Read full Privacy Policy &rarr;</a>
                </div>

                <!-- 6. No Investment Advice -->
                <div class="term-section">
                    <h2 class="text-xl">6. No Investment Advice</h2>
                    <ul>
                        <li>Funding4x does not provide investment, financial, or trading advice.</li>
                        <li>All content is for educational and evaluation purposes only.</li>
                        <li>You are solely responsible for any trading decisions.</li>
                    </ul>
                </div>
                
                <!-- 7. Intellectual Property -->
                <div class="term-section">
                    <h2 class="text-xl">7. Intellectual Property</h2>
                    <ul>
                        <li>All content, branding, tools, and software are the intellectual property of Funding4x.</li>
                        <li>You may not reproduce, copy, or distribute any materials without permission.</li>
                    </ul>
                </div>

                <!-- 8. Governing Law -->
                <div class="term-section">
                    <h2 class="text-xl">8. Governing Law</h2>
                    <ul>
                        <li>These Terms and Disclaimers are governed by the laws of UAE.</li>
                        <li>Any disputes will be subject to the exclusive jurisdiction of the courts of UAE.</li>
                    </ul>
                </div>

                <!-- 9. Acceptance -->
                <div class="term-section border-t-4 border-trophy-gold pt-6">
                    <h2 class="text-xl mb-4">9. Acceptance</h2>
                    <p class="text-gray-600 mb-6">By ticking the checkbox below and creating an account, you:</p>
                    <ul class="mb-8">
                        <li>Confirm you have read and understood this summary</li>
                        <li>Agree to be bound by the full policies</li>
                        <li>Accept all risks and disclaimers outlined above</li>
                    </ul>

                    <!-- Acceptance Checkbox -->
                    <div class="flex items-start">
                        <input id="acceptance-checkbox" type="checkbox" class="h-5 w-5 rounded border-gray-300 text-trophy-gold focus:ring-primary-purple cursor-pointer mt-1">
                        <label for="acceptance-checkbox" class="ml-3 text-lg font-semibold text-header-dark cursor-pointer">
                            I confirm I have read and agree to the Terms & Disclaimers Summary.
                        </label>
                    </div>
                </div>
<!--                 
                <p class="mt-8 text-center text-sm text-gray-500">
                    <a href="" class="link-text">Review Full Policies (PDF)</a>
                </p> -->
                
            </div>
        </div>
    </main>
</body>
</html>