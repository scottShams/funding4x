<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy – Funding4x</title>
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
        .sublist {
            margin-top: 0.5rem;
            margin-left: 1rem;
        }
        .sublist li::before {
             content: "—";
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

    <!-- Main Content: Privacy Policy -->
    <main class="flex-grow flex justify-center p-4 sm:p-8">
        <div class="w-full max-w-4xl">
            <div class="bg-card-white p-8 rounded-2xl shadow-2xl border-t-8 border-secondary-purple">
                <h1 class="text-4xl font-extrabold text-header-dark mb-2">Privacy Policy</h1>
                <p class="text-sm text-gray-500 mb-6">Last Updated: 24-11-2025</p>
                
                <p class="mb-8 text-lg text-gray-700 leading-relaxed border-b pb-4">
                    Funding4x (“we”, “us”, “our”) respects your privacy and is committed to protecting your personal data. This Privacy Policy explains how we collect, use, store, and protect your information when you use our website and services. By using our website and creating an account, you agree to the collection and use of your data as described in this policy.
                </p>

                <!-- 1. Information We Collect -->
                <div class="term-section">
                    <h2 class="text-xl">1. Information We Collect</h2>
                    <p class="mb-4 text-gray-600">We may collect the following personal information:</p>

                    <h3 class="font-semibold text-gray-700 mt-4 mb-2">1.1 Information You Provide</h3>
                    <ul class="sublist">
                        <li>Full name</li>
                        <li>Email address</li>
                        <li>Username</li>
                        <li>Password (encrypted)</li>
                        <li>Any information submitted via forms or support requests</li>
                    </ul>
                    
                    <h3 class="font-semibold text-gray-700 mt-4 mb-2">1.2 Automatically Collected Information</h3>
                    <ul class="sublist">
                        <li>IP address</li>
                        <li>Browser type and version</li>
                        <li>Device type</li>
                        <li>Operating system</li>
                        <li>Pages visited and time spent</li>
                        <li>Referral source</li>
                    </ul>

                    <h3 class="font-semibold text-gray-700 mt-4 mb-2">1.3 Trading & Platform Data (Demo Only)</h3>
                    <p class="text-sm text-gray-600">Because the platform uses simulated/demo trading, we may also collect:</p>
                    <ul class="sublist">
                        <li>Demo account activity</li>
                        <li>Simulated trades</li>
                        <li>Performance statistics</li>
                        <li>Evaluation results</li>
                        <li><span class="font-semibold text-primary-purple">Note: This data is not real financial data and does not involve real money.</span></li>
                    </ul>
                </div>

                <!-- 2. How We Use Your Information -->
                <div class="term-section">
                    <h2 class="text-xl">2. How We Use Your Information</h2>
                    <p class="mb-4 text-gray-600">We use your data for the following purposes:</p>
                    <ul>
                        <li>To create and manage your account</li>
                        <li>To provide access to our demo and evaluation services</li>
                        <li>To send transactional emails (login, account updates, activity alerts)</li>
                        <li>To send marketing and promotional emails</li>
                        <li>To improve website performance and user experience</li>
                        <li>To detect fraud, abuse, or security risks</li>
                        <li>To generate anonymised statistics and analytical reports</li>
                    </ul>
                </div>

                <!-- 3. Email Marketing & Communications -->
                <div class="term-section">
                    <h2 class="text-xl">3. Email Marketing & Communications</h2>
                    <p class="mb-4 text-gray-600">By signing up and accepting our Terms and Conditions, you consent to receive:</p>
                    <ul>
                        <li>Product updates</li>
                        <li>Promotional offers</li>
                        <li>Educational content</li>
                        <li>Newsletters</li>
                        <li>Platform updates</li>
                        <li>Evaluation results and account activity emails</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-4">You may unsubscribe from marketing emails at any time via the unsubscribe link included in our emails.</p>
                    <p class="text-sm text-gray-600">You will continue to receive essential service-related emails while your account remains active.</p>
                </div>

                <!-- 4. Cookies and Tracking Technologies -->
                <div class="term-section">
                    <h2 class="text-xl">4. Cookies and Tracking Technologies</h2>
                    <p class="mb-4 text-gray-600">We use cookies and similar technologies to:</p>
                    <ul>
                        <li>Remember your session and preferences</li>
                        <li>Analyse website traffic</li>
                        <li>Improve site performance</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-4">You can control or disable cookies through your browser settings. Disabling cookies may affect website functionality.</p>
                </div>
                
                <!-- 5. How We Share Your Data -->
                <div class="term-section bg-primary-purple/5 border-l-4 border-secondary-purple">
                    <h2 class="text-xl text-primary-purple">5. How We Share Your Data</h2>
                    <p class="font-bold text-gray-700 mb-4">We do not sell your personal data.</p>
                    <p class="mb-4 text-gray-600">We may share data only with trusted third-party service providers, such as:</p>
                    <ul class="sublist">
                        <li>Email delivery services</li>
                        <li>Hosting providers</li>
                        <li>Analytics services</li>
                        <li>Security and fraud prevention tools</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-4">These providers are bound by confidentiality and data protection obligations.</p>
                    <p class="text-sm text-gray-600">We may also disclose information if required by law or legal authorities in UAE or other relevant jurisdictions.</p>
                </div>

                <!-- 6. Data Storage and Security -->
                <div class="term-section">
                    <h2 class="text-xl">6. Data Storage and Security</h2>
                    <p class="mb-4 text-gray-600">We take reasonable technical and organisational measures to protect your data, including:</p>
                    <ul>
                        <li>Encryption of sensitive data</li>
                        <li>Secure servers and firewalls</li>
                        <li>Limited access controls</li>
                    </ul>
                    <p class="text-sm text-red-600 mt-4 font-semibold">However, no internet-based system is 100% secure, and we cannot guarantee absolute security.</p>
                </div>

                <!-- 7. International Data Transfers -->
                <div class="term-section">
                    <h2 class="text-xl">7. International Data Transfers</h2>
                    <p class="text-gray-600">Your information may be stored or processed on servers located outside of UAE, depending on our hosting and service providers. By using our website, you consent to international data transfers where necessary.</p>
                </div>

                <!-- 8. Data Retention -->
                <div class="term-section">
                    <h2 class="text-xl">8. Data Retention</h2>
                    <p class="mb-4 text-gray-600">We retain your personal data only for as long as necessary to:</p>
                    <ul>
                        <li>Provide our services</li>
                        <li>Comply with legal obligations</li>
                        <li>Resolve disputes</li>
                        <li>Enforce our agreements</li>
                    </ul>
                    <p class="text-sm text-gray-600 mt-4">You may request deletion of your data as described below.</p>
                </div>

                <!-- 9. Your Rights -->
                <div class="term-section">
                    <h2 class="text-xl">9. Your Rights</h2>
                    <p class="mb-4 text-gray-600">You have the right to:</p>
                    <ul>
                        <li>Request access to your personal data</li>
                        <li>Request correction of inaccurate data</li>
                        <li>Request deletion of your data (subject to legal and operational limits)</li>
                        <li>Withdraw consent for marketing communications</li>
                    </ul>
                    <p class="text-gray-600 mt-4">To exercise these rights, contact us at:</p>
                    <p class="font-semibold text-primary-purple text-lg mt-3">Email: <a href="mailto:support@funding4x.com" class="hover:underline">support@funding4x.com</a></p>
                </div>

            </div>
        </div>
    </main>
</body>
</html>