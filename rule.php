<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading Rules – Funding4x Prop Firm</title>
    <meta name="description" content="Learn the trading rules for Funding4x funded accounts. Understand limits, risk management, and evaluation guidelines.">
    <meta name="keywords" content="prop firm trading rules, funded account rules, trading limits, risk management, Funding4x evaluation guidelines">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="assets/site.webmanifest">

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        // PRESTIGIOUS PURPLE PALETTE
                        'primary-purple': '#4f009d', // Deep Royal Purple
                        'secondary-purple': '#7b2cbf', // Lighter accent purple
                        'trophy-gold': '#b49852', // Classic, muted Gold
                        'header-dark': '#240046', // Dark background
                        'bg-light': '#f3f4f6',
                        'cta-hover': '#9d7c49',
                        'success-green': '#10b981', // For highlighting positive differences
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
        /* Custom section titles for detail area */
        .detail-section h3 {
            position: relative;
            padding-bottom: 5px;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            font-size: 1.5rem; /* 2xl */
            font-weight: 700;
            color: #4f009d;
        }
        .detail-section h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: #b49852;
            border-radius: 2px;
        }
        /* Table Styling for Pricing-style look */
        .spec-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .spec-table td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .spec-table tr:last-child td {
            border-bottom: none;
        }
    </style>
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-4F50HDQBDE"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-4F50HDQBDE');
    </script>
    
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1954105902455354"
     crossorigin="anonymous"></script>
     
     
</head>

<body class="min-h-screen flex flex-col">

    <?php include 'header.php'; ?>

     <!-- 1. HERO SECTION: Pricing-Style Spec Sheet (The Table) -->
    <section class="bg-header-dark py-16 md:py-24 relative overflow-hidden">
        <!-- Background decorative elements -->
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-trophy-gold rounded-full blur-3xl"></div>
            <div class="absolute top-1/2 -left-24 w-72 h-72 bg-primary-purple rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="text-center mb-10">
                <h1 class="text-4xl sm:text-5xl font-extrabold mb-4 text-trophy-gold">Official Trading Rules</h1>
                <p class="text-xl text-gray-300"><span class="text-xl text-gray-300 mb-12">No more painful rules, hidden surprises, and tricks to make you fail. <br>
We have clear transparent and easy to follow rules to help you be profitable and Pass the Trading Test</span></p>
            </div>

            <!-- The "Pricing Table" Style Card -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden border-4 border-trophy-gold transform transition hover:scale-[1.01] duration-300">
                <!-- Table Header -->
                <div class="bg-primary-purple p-6 text-center">
                    <h2 class="text-2xl font-bold text-white uppercase tracking-wider"></h2>
                    <p class="text-trophy-gold font-semibold mt-1">Standard Challenge Settings</p>
                </div>
                
                <!-- The Spec Table -->
                <table class="w-full text-left spec-table">
                    <tbody>
                        <!-- Account Size -->
                        <tr>
                            <td class="font-medium text-gray-600">Starting Balance</td>
                            <td class="font-bold text-gray-900 text-right text-lg">$5,000</td>
                        </tr>
                        <!-- Leverage -->
                        <tr>
                            <td class="font-medium text-gray-600">Leverage</td>
                            <td class="font-bold text-gray-900 text-right">1:100</td>
                        </tr>
                        <!-- Duration -->                        <!-- Profit Target -->
                        <tr>
                            <td class="font-medium text-gray-600">Profit Target</td>
                            <td class="font-bold text-success-green text-right">10%</td>
                        </tr>
                        <!-- Drawdown Limits -->
                        <tr>
                            <td class="font-medium text-gray-600">Max Total Drawdown</td>
                            <td class="font-bold text-red-600 text-right">10%</td>
                        </tr>
                        <!-- Trading Days -->
                        <tr>
                            <td class="font-medium text-gray-600">Minimum Trading Days</td>
                            <td class="font-bold text-gray-900 text-right">5 Days</td>
                        </tr>
                        <!-- Restrictions -->
                        <tr>
                            <td class="font-medium text-gray-600">Overnight Holding</td>
                            <td class="font-bold text-red-600 text-right text-sm">❌ Prohibited</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-600">Copy Trading / EAs</td>
                            <td class="font-bold text-red-600 text-right text-sm">❌ Prohibited</td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-600">News Trading</td>
                             <td class="font-bold text-red-600 text-right text-sm">❌ Prohibited</td>
                        </tr>
                         <tr>
                            <td class="font-medium text-gray-600">Instruments</td>
                            <td class="font-bold text-gray-900 text-right text-sm">FX &amp; Metals Only</td>
                        </tr>
                    </tbody>
                </table>

                 
                <div class="p-6 bg-gray-50 text-center border-t border-gray-200">
                    <!--<button class="w-full bg-trophy-gold text-header-dark font-bold py-4 rounded-xl shadow-lg hover:bg-cta-hover transition uppercase tracking-wide text-lg" onclick="alertMessage('Registration', 'Proceeding to account setup...')">
                        Read Detailed Explanations of Rules
                    </button>-->
                    <p class="text-xs text-gray-400 mt-3">By registering, you agree to all terms listed above.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. DETAILED EXPLANATION OF RULES (Context) -->
    <section class="py-16 md:py-24 bg-white detail-section">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">


 <!-- NEW SECTION: The Fairness Commitment (What We DON'T Include) -->
            <div class="mb-12 pt-6">
                <h2 class="text-3xl font-extrabold mb-8 text-success-green">The Fairness Commitment: What We Don't Do</h2>
                <p class="text-gray-600 mb-6">We are committed to providing a transparent and trader-friendly environment. Our rules are designed for clear risk management, not for trapping traders.</p>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Point 1: Drawdown Clarity -->
                    <div class="bg-success-green/10 p-5 rounded-xl border border-success-green shadow-lg">
                        <p class="font-extrabold text-xl text-success-green mb-2">1. No Confusing Loss Rules</p>
                        <p class="text-gray-700">We do **not** use complicated or dual-calculated daily loss rules. Our 5% Max Daily Drawdown is simple: it is based on your previous day's balance. No misleading 'secret' calculations. This means your account can fluctuate during the day without worrying. </p>
                    </div>

                    <!-- Point 2: Consistency -->
                    <div class="bg-success-green/10 p-5 rounded-xl border border-success-green shadow-lg">
                        <p class="font-extrabold text-xl text-success-green mb-2">2. No Consistency Rules or Tricks</p>
                        <p class="text-gray-700">You are **not** required to maintain a daily profit average, minimum lot size, or 'consistent' trading volume. Trade your strategy as you see fit, as long as you respect the core risk limits. Just don't do sudden huge Trades - that shows you are careless which we won't like. </p>
                    </div>

                    <!-- Point 3: Hidden Rules -->
                    <div class="bg-success-green/10 p-5 rounded-xl border border-success-green shadow-lg">
                        <p class="font-extrabold text-xl text-success-green mb-2">3. No Hidden Rules</p>
                        <p class="text-gray-700">Every single rule and restriction is explicitly listed within this document, including the News Trading limitation. There are **no hidden clauses** designed to fail participants unexpectedly.</p>
                    </div>

                    <!-- Point 4: Fake Promises -->
                    <div class="bg-success-green/10 p-5 rounded-xl border border-success-green shadow-lg">
                        <p class="font-extrabold text-xl text-success-green mb-2">4. No Fake Refund Promises</p>
                        <p class="text-gray-700">We offer a transparent competition structure without hidden costs, meaning there is **no refund to promise or deny**.</p>
                    </div>
                </div>
            </div>
            
            
             <div class="mb-12 border-t-4 border-success-green pt-6"></div>
             
            <h2 class="text-3xl font-extrabold mb-10 text-header-dark border-b pb-3 border-trophy-gold">Comprehensive Trading Guidelines</h2>

            <!-- SECTION: Eligibility and Registration -->
            <div class="mb-12">
                <h3>1. Eligibility and Registration</h3>
                <div class="space-y-4">
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">1.1 Age Requirement</p>
                        <p class="text-gray-700">Participants must be 18 years of age or older at the time of registration. Identity verification will be required prior to payout disbursement.</p>
                    </div>
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">1.2 Account Limits</p>
                        <p class="text-gray-700">Each participant is limited to one competition trading account. Multiple registrations will result in the immediate disqualification of all associated accounts.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION: Trading Parameters -->
            <div class="mb-12">
                <h3>2. Core Trading Parameters</h3>
                <div class="space-y-4">
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">2.1 Leverage</p>
                        <p class="text-gray-700">A **fixed maximum leverage of 1:100** is applied to all trading accounts. Margin requirements will be calculated based on this setting, regardless of trade size.</p>
                    </div>
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">2.2 Tradable Instruments</p>
                        <p class="text-gray-700">The competition is limited to Forex pairs (e.g., EUR/USD, GBP/JPY) and Metals only.  Exotics, Cryptocurrencies, and Stocks are strictly prohibited.</p>
                    </div>
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">2.3 No Huge Trades</p>
                        <p class="text-gray-700">You must Trade safely without over exposing your account. We need to see you are a Safe Forex Trader. The maximum lot size you can do is 0.1 lot in any single Trade. The total combined lot size you are allowed is 0.5 lots. Breaching this limit will result in failure. </p>
                    </div>
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">2.4 Minimum Trading Days</p>
                        <p class="text-gray-700">Participants must execute trades on a **minimum of 5 separate days** to qualify. A trading day is defined as any 24-hour period (00:00 to 23:59 GMT) in which at least one trade is opened and closed.</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                        <p class="font-semibold text-yellow-800">2.5 Closing Positions End of Day (No Overnight Holding)</p>
                        <p class="text-gray-700">**All open positions must be closed** before 23:59 GMT each trading day. Holding positions overnight (past midnight GMT) is strictly prohibited and will result in the automatic closure of the position and potential penalty.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION: Risk Management and Strategy Compliance -->
            <div class="mb-12">
                <h3>3. Risk Management and Strategy Compliance</h3>
                <p class="text-gray-600 mb-4">Strict adherence to risk limits and ethical trading practices is mandatory.</p>
                <div class="space-y-4">
                    <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                        <p class="font-semibold text-red-600">3.1 Maximum Daily Drawdown (5%)</p>
                        <p class="text-gray-700">Your account equity, calculated at any point during a trading day, must not fall below 95% of the starting balance ($5,000) or the previous day's closing balance, whichever is higher. Just don't drop by 5% in one day!</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                        <p class="font-semibold text-red-600">3.2 Maximum Total Drawdown (10%)</p>
                        <p class="text-gray-700">Your account equity must never fall below **$4,500** (10% of the initial $5,000 balance) at any point throughout the competition.If it does reach 10% draw down you will be closed out, and the Test will be Failed.</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                        <p class="font-semibold text-red-600">3.3 Copy Trading</p>
                        <p class="text-gray-700">**The use of any external copy trading service, signal provider, or automated replication of trades from another account is strictly prohibited.** All trades must be generated solely by the participant's strategy. The trades cannot be copied from another participant in Funding4x either.</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                        <p class="font-semibold text-yellow-800">3.4 Risk Exposure</p>
                        <p class="text-gray-700">Your Stop Loss should be such that you're maximum risk on a single trade is only 1% of the account balance. Opening trades with large risks will be a violation, and the Test will be Failed.</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                        <p class="font-semibold text-yellow-800">3.5 News Trading</p>
                        <p class="text-gray-700">Trading within a 1 hour window (30 min before to 30 min after) any high-impact economic news release (marked as Red on major economic calendars) is **prohibited**. Trades must be placed outside of this volatility window.</p>
                    </div>
                </div>
            </div>

            <!-- SECTION: Payout and Verification -->
            <div class="mb-12">
                <h3>4. Payout and Verification</h3>
                <div class="space-y-4">
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">4.1 Qualification for Payout</p>
                        <p class="text-gray-700">To qualify for a profit payout, the participant must meet the Minimum Trading Days (5 days) requirement, achieve a minimum 10% profit target, and maintain zero violations of drawdown or strategy compliance rules.</p>
                    </div>
                    <div class="bg-bg-light p-4 rounded-lg">
                        <p class="font-semibold text-primary-purple">4.2 Payout Process</p>
                        <p class="text-gray-700">Payouts will be verified and disbursed as soon as reasonably possible, subject to manual checks, and banking transaction times.  Winners must provide valid ID and a verified bank account or verified crypto wallet for transfer. All Transactions costs and applicable taxes are the sole responsibility of the winner.</p>
                    </div>
                </div>
            </div>
            
           
            <!-- CTA -->
            <!--<div class="mt-12 text-center">
                <p class="text-xl font-semibold text-primary-purple mb-4">Ready to Compete Fairly?</p>
                <button class="bg-trophy-gold text-header-dark font-bold py-3 px-8 rounded-full text-lg shadow-lg hover:bg-cta-hover transition" onclick="alertMessage('Registration', 'Proceeding to fair competition registration.')">
                    Begin Your Challenge
                </button>
            </div>-->

        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-header-dark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col sm:flex-row justify-between items-center text-center sm:text-left">
            <p class="text-sm">Funding4x &copy; 2024</p>
            <div class="flex space-x-4 mt-4 sm:mt-0">
                <a href="pages/privacy-policy.php" class="text-gray-400 hover:text-trophy-gold transition duration-150">Privacy Policy</a>
                <a href="pages/term-conditions.php" class="text-gray-400 hover:text-trophy-gold transition duration-150">Terms of Service</a>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Mobile Menu and Custom Alert -->
    <script>
        document.getElementById('menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            // Toggle visibility using Tailwind class
            menu.classList.toggle('hidden');
        });

        // Custom alert/message function (since we cannot use window.alert)
        function alertMessage(title, message) {
            // Remove any existing modal to ensure only one is present
            const existingModal = document.querySelector('.modal-container');
            if (existingModal) {
                existingModal.remove();
            }

            const container = document.createElement('div');
            container.className = 'modal-container fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 p-4';
            
            // Modal content structure
            const modalContent = document.createElement('div');
            modalContent.className = 'bg-white p-6 rounded-lg shadow-2xl w-full max-w-sm transform transition-all scale-100 duration-300';
            
            modalContent.innerHTML = `
                <h4 class="text-xl font-bold text-primary-purple mb-3">${title}</h4>
                <p class="text-gray-700 mb-6">${message}</p>
                <button id="close-modal" class="w-full py-2 bg-primary-purple text-white rounded-lg font-semibold hover:bg-secondary-purple transition">
                    Close
                </button>
            `;
            
            container.appendChild(modalContent);
            document.body.appendChild(container);

            // Add event listener to close button
            document.getElementById('close-modal').addEventListener('click', () => {
                container.remove();
            });

            // Allow clicking outside the modal to close it
            container.addEventListener('click', (e) => {
                if (e.target === container) {
                    container.remove();
                }
            });
        }
    </script>
</body>
</html>