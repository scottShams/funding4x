<?php
    session_start();
    // Include database connection
    require_once 'database.php';

    // Get database connection
    $pdo = getPDO();
    $email = $_SESSION['user_email'];
    if(empty($email)){
        header("Location: index.php");
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!empty($user['quiz_result'])){
        header("Location: referral_dashboard.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trader Knowledge Assessment</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/favicon-16x16.png">
    <link rel="manifest" href="assets/site.webmanifest">

    <!-- Load Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Configure Tailwind for Inter font and prestigious purple colors (Royal Purple/Gold Theme) -->
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
                        'trophy-gold': '#b49852', // Classic, muted Gold for prestige/success
                        'fomo-red': '#ef4444', // Bright Red for high-contrast urgency (CTA/Alerts)
                        'bg-light': '#f9fafb', // Very light background
                        'header-dark': '#240046', // Very Dark Purple/Black for the sticky header/footer
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .header-bg {
            background-color: #240046;
        }
        /* Style for the selected answer */
        .answer-option:checked + label {
            background-color: #4f009d; /* primary-purple */
            color: white;
            border-color: #b49852; /* trophy-gold */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .answer-option {
            display: none; /* Hide the default radio button */
        }
        
        /* Ensure touch targets are large enough on mobile */
        @media (max-width: 640px) {
            label {
                min-height: 48px;
                touch-action: manipulation;
            }
            button {
                min-height: 48px;
                touch-action: manipulation;
            }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <!-- Header & Navigation -->
    <header class="sticky top-0 z-20 bg-header-dark shadow-xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo Section -->
                <div class="flex items-center">
                    <img src="assets/logo.png" alt="Funding4X Logo" class="h-10 w-10 mr-3 rounded-lg">
                    <h1 class="text-2xl font-extrabold tracking-tight text-trophy-gold">Funding4x</h1>
                </div>
                <!-- Desktop Nav -->
                <nav class="hidden md:flex space-x-8">
                    <a href="home.php" class="text-trophy-gold font-bold transition duration-150 border-b-2 border-trophy-gold">Home</a>

                    <a href="rule.php" class="text-gray-300 hover:text-trophy-gold transition duration-150 font-medium">Rules</a>
                    <a href="pricing.php" class="text-gray-300 hover:text-trophy-gold transition duration-150 font-medium">Login</a>
                    <button onclick="window.location.href='pricing.php'" class="bg-primary-indigo hover:bg-indigo-700 text-white font-semibold py-1 px-3 text-sm rounded transition duration-300 shadow-md">
                        Start Trading
                    </button>
                </nav>
                <!-- Mobile Menu Button (Hamburger) -->
                <button id="menu-button" class="md:hidden text-gray-300 hover:text-trophy-gold focus:outline-none">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-header-dark pb-3 px-2 pt-2 space-y-1 sm:px-3">
            <a href="home.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:bg-secondary-purple">Home</a>
            <a href="rule.php" class="block px-3 py-2 rounded-md text-base font-medium text-trophy-gold hover:bg-secondary-purple">Rules</a>
            <a href="pricing.php" class="block px-3 py-2 rounded-md text-base font-medium bg-primary-purple text-white mt-2">Register Now</a>
        </div>
    </header>

    <!-- Main Content: Quiz Card -->
    <main class="flex-grow flex items-center justify-center p-3 sm:p-4 md:p-8 bg-bg-light">
        <div class="w-full max-w-2xl bg-white p-4 sm:p-6 md:p-8 lg:p-12 rounded-2xl shadow-2xl border-t-4 border-trophy-gold">

            <div id="quiz-header" class="mb-4 sm:mb-6 border-b pb-3 sm:pb-4">
                <h2 class="text-xl sm:text-2xl md:text-3xl font-extrabold text-primary-purple mb-2">
                    Trader Skills Check
                </h2>
                <p class="text-sm sm:text-base text-gray-600">
                    Question <span id="current-q-number">1</span> of 7
                </p>
            </div>

            <div id="quiz-content">
                <!-- Questions will be dynamically injected here -->
            </div>

            <div id="quiz-summary" class="hidden text-center p-4 sm:p-6">
                <svg class="w-12 h-12 sm:w-16 sm:h-16 text-trophy-gold mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-xl sm:text-2xl font-bold text-primary-purple mb-3">Assessment Complete!</h3>
                <p class="text-base sm:text-lg text-gray-700 mb-6">You've answered all 5 initial questions. Please click below to proceed to the next section of the competition portal.</p>
                <a href="referral_dashboard.php" class="inline-block w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-4 bg-primary-purple text-white font-bold rounded-lg hover:bg-trophy-gold hover:text-header-dark transition duration-300 shadow-lg text-sm sm:text-base">
                    Continue to Dashboard
                </a>
            </div>

            <div id="quiz-navigation" class="mt-6 sm:mt-8 flex justify-center sm:justify-end">
                <button id="next-button" disabled class="w-full sm:w-auto px-4 sm:px-6 py-3 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-md disabled:opacity-50 hover:bg-yellow-700 transition duration-300 text-sm sm:text-base">
                    Continue to Next Question
                </button>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-header-dark text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved. | Powered by Referrals.</p>
        </div>
    </footer>

    <!-- FAIL MODAL -->
    <div id="fail-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full border-t-4 border-red-500 text-center">
            
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-times text-3xl text-red-600"></i>
            </div>

            <h2 class="text-2xl font-bold text-red-600 mb-4">Quiz Failed</h2>

            <p class="text-gray-700 text-lg leading-relaxed">
                Sorry, it seems you are <span class="text-red-600 font-bold">not a Forex Trader</span>.
                <br>Thank you for trying. <br>Goodbye.
            </p>

            <p class="text-xs text-gray-500 mt-6">
                Redirecting...
            </p>
        </div>
    </div>

    <script>
        // Array of questions and answers (SEQUENTIAL — NO SHUFFLE)
        let questions = [
            {
                question: "1. How many years of experience do you have in Forex trading?",
                name: "q1_experience",
                options: [
                    { label: "A. None", value: "0" },
                    { label: "B. 1–2 years", value: "1-2" },
                    { label: "C. 3–5 years", value: "3-5" },
                    { label: "D. More than 5 years", value: "5+" }
                ]
            },
            {
                question: "2. Approximately how many leveraged trades (FOREX) have you placed in the past 12 months?",
                name: "q2_trades",
                options: [
                    { label: "A. None", value: "0" },
                    { label: "B. 1–10 trades", value: "1-10" },
                    { label: "C. 11–50 trades", value: "11-50" },
                    { label: "D. More than 50 trades", value: "50+" }
                ]
            },
            {
                question: "3. What account size is your Personal Forex Trading Account?",
                name: "q3_trades",
                options: [
                    { label: "A. $50 or less", value: "$50 or less" },
                    { label: "B. $50 - $100", value: "$50 - $100" },
                    { label: "C. $100 - $500", value: "$50 - $100" },
                    { label: "D. $500 - $1000", value: "$500 - $1000" },
                    { label: "E. $1000 - $5000", value: "$1000 - $5000" },
                    { label: "F. $5000 - $10,000", value: "$5000 - $10,000" },
                    { label: "G. $10,000+", value: "$10,000+" },
                ]
            },
            {
                question: "4. What do you understand about leverage in FOREX trading?",
                name: "q4_leverage",
                options: [
                    { label: "A. It allows me to control a larger position with a smaller deposit", value: "correct" },
                    { label: "B. It guarantees higher profits", value: "incorrect_1" },
                    { label: "C. It eliminates the risk of losses", value: "incorrect_2" },
                    { label: "D. It locks my losses at the margin amount", value: "incorrect_3" }
                ]
            },
            {
                question: "5. What is the purpose of a stop-loss?",
                name: "q5_stoploss",
                options: [
                    { label: "A. To guarantee profits at a certain price", value: "incorrect_1" },
                    { label: "B. To limit potential losses on a trade", value: "correct" },
                    { label: "C. To increase leverage automatically", value: "incorrect_2" },
                    { label: "D. To eliminate market volatility", value: "incorrect_3" }
                ]
            },
            {
                question: "6. What is margin?",
                name: "q6_margin",
                options: [
                    { label: "A. A fee paid for each trade", value: "incorrect_1" },
                    { label: "B. A deposit required to open and maintain a leveraged position", value: "correct" },
                    { label: "C. The broker’s commission", value: "incorrect_2" },
                    { label: "D. Guaranteed minimum return", value: "incorrect_3" }
                ]
            }, 
            {
                question: "7. Which statement is correct:",
                name: "q7_statement",
                options: [
                    { label: "A. Funded4x will send $5000 to my personal account", value: "incorrect_1" },
                    { label: "B. Funding4x will give us some Trading Test to see our Forex Trading capability. If we Pass the Test, we will get a real funded account for $5000", value: "correct" },
                    { label: "C. With the 5 Referrals, Funded4x will give me instantly give me $5000 for trading without Testing my skills", value: "incorrect_2" }
                ]
            }
        ];

        let currentQuestionIndex = 0;
        const quizContent = document.getElementById('quiz-content');
        const nextButton = document.getElementById('next-button');
        const currentQNumber = document.getElementById('current-q-number');
        const quizHeader = document.getElementById('quiz-header');
        const quizNavigation = document.getElementById('quiz-navigation');
        const quizSummary = document.getElementById('quiz-summary');
        let selectedAnswers = {}; // Store user's selections

        function renderQuestion() {
            if (currentQuestionIndex >= questions.length) {
                showSummary();
                return;
            }

            const qData = questions[currentQuestionIndex];
            currentQNumber.textContent = currentQuestionIndex + 1;
            nextButton.disabled = !selectedAnswers[qData.name];

            let html = `<h4 class="text-base sm:text-lg md:text-xl font-semibold text-gray-800 mb-4 sm:mb-6">${qData.question}</h4>`;
            html += `<div class="space-y-3 sm:space-y-4">`;

            qData.options.forEach(option => {
                const isChecked = selectedAnswers[qData.name] === option.value;
                html += `
                    <div>
                        <input type="radio" id="${qData.name}-${option.value}" 
                            name="${qData.name}" value="${option.value}" 
                            class="answer-option" ${isChecked ? 'checked' : ''}>
                        <label for="${qData.name}-${option.value}" 
                            class="flex items-center p-3 sm:p-4 border-2 border-primary-purple rounded-lg cursor-pointer transition duration-200 hover:bg-primary-purple hover:text-white hover:border-trophy-gold text-sm sm:text-base">
                            <span class="font-medium">${option.label}</span>
                        </label>
                    </div>
                `;
            });

            html += `</div>`;
            quizContent.innerHTML = html;

            document.querySelectorAll(`input[name="${qData.name}"]`).forEach(input => {
                input.addEventListener('change', (e) => {
                    selectedAnswers[qData.name] = e.target.value;
                    nextButton.disabled = false;
                });
            });
        }

        function nextQuestion() {
            const qData = questions[currentQuestionIndex];
            const selected = document.querySelector(`input[name="${qData.name}"]:checked`);

            if (!selected) return;

            selectedAnswers[qData.name] = selected.value;

            // CHECK FAIL RIGHT AFTER QUESTION 2
            if (currentQuestionIndex === 1) {
                const q1 = selectedAnswers["q1_experience"];
                const q2 = selectedAnswers["q2_trades"];

                if (q1 === "0" && q2 === "0") {
                    showFailModal();
                    return;
                }
            }

            currentQuestionIndex++;
            renderQuestion();
        }

        function showSummary() {
            const q1 = selectedAnswers["q1_experience"];
            const q2 = selectedAnswers["q2_trades"];
            const q3 = selectedAnswers["q3_trades"];

            if (q1 === "0" && q2 === "0") {
                showFailModal();
                return;
            }

            let correctCount = 0;
            ["q4_leverage", "q5_stoploss", "q6_margin", "q7_statement"].forEach(q => {
                if (selectedAnswers[q] === "correct") correctCount++;
            });

            const quizResult = {
                q1: q1,
                q2: q2,
                q3: q3,
                correct_last_four: correctCount
            };

            fetch("store_quiz_result.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ quiz_result: quizResult })
            })
            .then(res => res.json())
            .then(data => console.log("Quiz result saved:", data))
            .catch(err => console.error("Error saving quiz result:", err));

            quizContent.classList.add('hidden');
            quizHeader.classList.add('hidden');
            quizNavigation.classList.add('hidden');
            quizSummary.classList.remove('hidden');

            console.log("Quiz Completed. Answers:", selectedAnswers);
        }

        renderQuestion();
        nextButton.addEventListener('click', nextQuestion);

        function showFailModal() {
            const modal = document.getElementById('fail-modal');
            modal.classList.remove('hidden');

            setTimeout(() => {
                window.location.href = "block_user.php";
            }, 5000);
        }

    </script>

</body>
</html>