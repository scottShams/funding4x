<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions – Funding4x</title>
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
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <header class="header-bg text-white shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
        </div>
    </header>

    <!-- Main Content: Terms and Conditions -->
    <main class="flex-grow flex justify-center p-4 sm:p-8">
        <div class="w-full max-w-4xl">
            <div class="bg-card-white p-8 rounded-2xl shadow-2xl border-t-8 border-primary-purple">
                <h1 class="text-4xl font-extrabold text-header-dark mb-2">Terms and Conditions</h1>
                <p class="text-sm text-gray-500 mb-6">Last Updated: 21-11-2025</p>
                
                <p class="mb-8 text-lg text-gray-700 leading-relaxed border-b pb-4">
                    These Terms and Conditions (“Terms”) govern your use of the Funding4x website and services (“Services”). By accessing our website, creating an account, and ticking the Terms and Conditions checkbox, you agree to be legally bound by these Terms. If you do not agree to these Terms, you must not use the website or Services.
                </p>

                <!-- 1. About Funding4x -->
                <div class="term-section">
                    <h2 class="text-xl">1. About Funding4x</h2>
                    <ul>
                        <li>Funding4x (“we”, “us”, “our”) provides simulated trading environments, evaluation-style trial accounts, and educational trading tools.</li>
                        <li>We are not a broker, not a financial institution, and do not provide regulated investment services.</li>
                        <li>All trading accounts offered during the trial phase are demo (simulated) accounts only.</li>
                    </ul>
                </div>

                <!-- 2. Eligibility -->
                <div class="term-section">
                    <h2 class="text-xl">2. Eligibility</h2>
                    <p class="mb-4">By using Funding4x, you confirm that:</p>
                    <ul>
                        <li>You are at least 18 years old</li>
                        <li>You have the legal capacity to enter into this agreement</li>
                        <li>You are legally permitted to access trading-related platforms in your country</li>
                        <li>It is your responsibility to ensure compliance with local laws in your place of residence.</li>
                    </ul>
                </div>

                <!-- 3. Trial Accounts (Simulated / Demo Only) -->
                <div class="term-section">
                    <h2 class="text-xl">3. Trial Accounts (Simulated / Demo Only)</h2>
                    <p class="mb-4">You acknowledge and agree that:</p>
                    <ul>
                        <li>All trading during the trial phase is simulated</li>
                        <li>No real funds are deposited or traded</li>
                        <li>Results shown are hypothetical and for evaluation purposes only</li>
                        <li>Demo performance does not guarantee live trading results</li>
                        <li>Funding4x may modify, restrict, or terminate trial access at any time without prior notice.</li>
                    </ul>
                </div>

                <!-- 4. Future Paid Products -->
                <div class="term-section">
                    <h2 class="text-xl">4. Future Paid Products</h2>
                    <ul>
                        <li>We reserve the right to introduce paid evaluation plans or services in the future.</li>
                        <li>When introduced, separate pricing, rules, and refund policies will be provided.</li>
                        <li>The current free trial does not guarantee access to future paid programs.</li>
                    </ul>
                </div>

                <!-- 5. No Investment Advice Disclaimer -->
                <div class="term-section">
                    <h2 class="text-xl">5. No Investment Advice Disclaimer</h2>
                    <p class="mb-4">Funding4x does not provide:</p>
                    <ul>
                        <li>Investment advice</li>
                        <li>Financial advice</li>
                        <li>Trading recommendations</li>
                        <li>Signals or asset guarantees</li>
                    </ul>
                    <p class="text-sm mt-4">All information provided on this website is for educational and informational purposes only. You are solely responsible for all trading decisions and any risks you take.</p>
                </div>

                <!-- 6. User Conduct -->
                <div class="term-section bg-secondary-purple/5 border-l-4 border-trophy-gold">
                    <h2 class="text-xl text-primary-purple">6. User Conduct</h2>
                    <p class="mb-4">You agree that you will not:</p>
                    <ul>
                        <li>Use automated bots, scripts, or hacks</li>
                        <li>Exploit platform vulnerabilities</li>
                        <li>Create multiple accounts to abuse free trials</li>
                        <li>Resell, share, or transfer your account</li>
                        <li>Manipulate trading rules or results</li>
                    </ul>
                    <p class="text-sm text-red-700 mt-4 font-semibold">We reserve the right to suspend or permanently ban accounts that violate these rules.</p>
                </div>

                <!-- 7. Email Marketing & Communications -->
                <div class="term-section">
                    <h2 class="text-xl">7. Email Marketing & Communications</h2>
                    <p class="mb-4">By creating an account and accepting these Terms, you explicitly consent to receiving:</p>
                    <ul>
                        <li>Service emails</li>
                        <li>Transactional emails</li>
                        <li>Marketing and promotional emails</li>
                        <li>Newsletters and educational content</li>
                        <li>Product and feature updates</li>
                    </ul>
                    <p class="text-sm mt-4">You may unsubscribe from marketing emails at any time via the unsubscribe link in emails. Service-related emails cannot be fully opted out while your account is active.</p>
                </div>

                <!-- 8. Use of Data & Privacy -->
                <div class="term-section">
                    <h2 class="text-xl">8. Use of Data & Privacy</h2>
                    <p class="mb-4">We collect and process personal data including:</p>
                    <ul>
                        <li>Name</li>
                        <li>Email address</li>
                        <li>IP address</li>
                        <li>Device and browser data</li>
                        <li>Simulated trading activity</li>
                    </ul>
                    <p class="mt-4">We may use anonymised trading data for:</p>
                    <ul class="mt-2">
                        <li>Platform improvement</li>
                        <li>Statistics and analytics</li>
                        <li>Marketing performance summaries (without personal identification)</li>
                    </ul>
                    <p class="text-sm mt-4">Your data is handled according to our Privacy Policy.</p>
                </div>

                <!-- 9. Intellectual Property -->
                <div class="term-section">
                    <h2 class="text-xl">9. Intellectual Property</h2>
                    <p>All content, software, branding, logo designs, and materials on Funding4x are the exclusive property of Funding4x and may not be copied or distributed without written permission.</p>
                </div>

                <!-- 10. Limitation of Liability -->
                <div class="term-section">
                    <h2 class="text-xl">10. Limitation of Liability</h2>
                    <p class="mb-4">To the fullest extent permitted by law in UAE, Funding4x shall not be liable for:</p>
                    <ul>
                        <li>Financial losses</li>
                        <li>Trading losses</li>
                        <li>Business losses</li>
                        <li>Data loss</li>
                        <li>Platform interruptions</li>
                        <li>Technical failures</li>
                    </ul>
                    <p class="text-sm mt-4 font-semibold text-red-700">You use Funding4x entirely at your own risk.</p>
                </div>

                <!-- 11. Account Suspension & Termination -->
                <div class="term-section">
                    <h2 class="text-xl">11. Account Suspension & Termination</h2>
                    <p class="mb-4">We may suspend or permanently terminate your account if:</p>
                    <ul>
                        <li>You breach these Terms</li>
                        <li>We detect fraudulent or abusive behaviour</li>
                        <li>Required by law</li>
                    </ul>
                    <p class="text-sm mt-4">You may request account closure at any time by contacting support.</p>
                </div>

                <!-- 12. Changes to These Terms -->
                <div class="term-section">
                    <h2 class="text-xl">12. Changes to These Terms</h2>
                    <p>We may update these Terms at any time. Changes become effective immediately when published on this page. It is your responsibility to review the Terms regularly.</p>
                </div>

                <!-- 13. Governing Law -->
                <div class="term-section">
                    <h2 class="text-xl">13. Governing Law</h2>
                    <p>These Terms shall be governed by and interpreted in accordance with the laws of UAE. Any disputes shall be subject to the exclusive jurisdiction of the courts of UAE.</p>
                </div>

                <!-- 14. Contact Information -->
                <div class="term-section border-t-4 border-trophy-gold pt-6">
                    <h2 class="text-xl">14. Contact Information</h2>
                    <p class="mb-4">For questions or support, contact:</p>
                    <p class="font-semibold text-primary-purple text-lg">Email: <a href="mailto:support@funding4x.com" class="hover:underline">support@funding4x.com</a></p>
                </div>

            </div>
        </div>
    </main>
</body>
</html>