<?php
    session_start();
    // Include database connection
    require_once 'database.php';

    // Get database connection
    $pdo = getPDO();
    $email = $_SESSION['user_email'];
    if(empty($email)){
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Please Login</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <script>
                tailwind.config = {
                    theme: {
                        extend: {
                            colors: {
                                'primary-purple': '#4f009d',
                                'header-dark': '#240046',
                            }
                        }
                    }
                }
            </script>
        </head>
        <body class="min-h-screen flex items-center justify-center bg-gray-100">
            <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm flex items-center justify-center z-50">
                <div class="bg-white p-10 rounded-3xl shadow-2xl max-w-lg mx-4 border-t-4 border-primary-purple">
                    <div class="text-center">
                        <h2 class="text-3xl font-extrabold text-primary-purple mb-4">Please Login First</h2>
                        <p class="text-gray-700 mb-8 leading-relaxed text-lg">
                            You need to be logged in to access this page.
                        </p>
                        <div class="flex justify-center space-x-4">
                            <a href="login.php" class="px-8 py-4 bg-primary-purple text-white font-bold rounded-xl shadow-lg hover:bg-header-dark transition-all duration-300 transform hover:scale-105">
                                Login Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM waitlist_users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if($user['email_verified'] !== 1){
        header("Location: index.php");
        exit;
    }  
    // Check if user has completed the first quiz
    if(empty($user['quiz_result'])){
        header("Location: quiz.php");
        exit;
    }
    // Check if knowledge test is already completed
    if(!empty($user['knowledge_test_result'])){
        header("Location: referral_dashboard.php");
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forex Knowledge Check – Funding4x Prop Firm</title>
    <meta name="description" content="Test your forex trading knowledge with Funding4x. Prepare for our evaluation and improve your chances of passing the funded trader challenge.">
    <meta name="keywords" content="forex knowledge test, prop firm evaluation, funded trader challenge, forex quiz, trading skills test">

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
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <?php include 'header.php'; ?>

    <!-- Main Content: Quiz Card -->
    <main class="flex-grow flex items-center justify-center p-4 sm:p-8 bg-bg-light">
        <div class="w-full max-w-2xl bg-white p-8 sm:p-12 rounded-2xl shadow-2xl border-t-4 border-trophy-gold">

            <div id="quiz-header" class="mb-6 border-b pb-4">
                <h2 class="text-3xl font-extrabold text-primary-purple mb-2">
                    Forex Knowledge Check
                </h2>
                <p class="text-gray-600">
                    Question <span id="current-q-number">1</span> of <span id="total-q-number">9</span>
                </p>
            </div>

            <div id="quiz-content">
                <!-- Questions will be dynamically injected here -->
            </div>

            <div id="quiz-summary" class="hidden text-center p-6">
                <svg class="w-16 h-16 text-trophy-gold mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-2xl font-bold text-primary-purple mb-3">Knowledge Check Complete!</h3>
                <p class="text-lg text-gray-700 mb-6">You've completed the Knowledge Check. Please click below to access your referral dashboard.</p>
                <a href="referral_dashboard.php" class="px-8 py-3 bg-primary-purple text-white font-bold rounded-lg hover:bg-trophy-gold hover:text-header-dark transition duration-300 shadow-lg">
                    Go To Dashboard
                </a>
            </div>

            <div id="quiz-navigation" class="mt-8 text-right">
                <button id="next-button" disabled class="px-6 py-3 bg-trophy-gold text-header-dark font-bold rounded-lg shadow-md disabled:opacity-50 hover:bg-yellow-700 transition duration-300">
                    Continue to Next Question
                </button>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-header-dark text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center">
            <p class="text-sm">&copy; 2024 Funding4x. All rights reserved. | Powered by Referrals.</p>
        </div>
    </footer>

    <script>
        // Array of questions and answers
        const questions = [
            {
                question: "1. What is a pip?",
                name: "q1_pip",
                options: [
                    { label: "A. A trading fee", value: "incorrect_1" },
                    { label: "B. The smallest price movement in a currency pair", value: "correct" },
                    { label: "C. A type of order", value: "incorrect_2" },
                    { label: "D. A candlestick pattern", value: "incorrect_3" }
                ]
            },
            {
                question: "2. Which platforms are commonly used for forex trading?",
                name: "q2_platforms",
                options: [
                    { label: "A. Netflix and Hulu", value: "incorrect_1" },
                    { label: "B. MT4/MT5 and cTrader", value: "correct" },
                    { label: "C. Photoshop and Illustrator", value: "incorrect_2" },
                    { label: "D. Google Sheets", value: "incorrect_3" }
                ]
            },
            {
                question: "3. How do experienced traders usually determine position size?",
                name: "q3_positionsize",
                options: [
                    { label: "A. Randomly", value: "incorrect_1" },
                    { label: "B. Based on account size and risk percentage", value: "correct" },
                    { label: "C. Using only gut feeling", value: "incorrect_2" },
                    { label: "D. Using the highest leverage available", value: "incorrect_3" }
                ]
            },
            {
                question: "4. What is a typical risk percentage per trade for disciplined traders?",
                name: "q4_risk",
                options: [
                    { label: "A. 20–30%", value: "incorrect_1" },
                    { label: "B. 10–15%", value: "incorrect_2" },
                    { label: "C. 1–2%", value: "correct" },
                    { label: "D. 0%", value: "incorrect_3" }
                ]
            },
            {
                question: "5. What is a stop-loss used for?",
                name: "q5_stoploss",
                options: [
                    { label: "A. To increase leverage", value: "incorrect_1" },
                    { label: "B. To automatically close a losing trade at a set level", value: "correct" },
                    { label: "C. To guarantee profits", value: "incorrect_2" },
                    { label: "D. To open more positions", value: "incorrect_3" }
                ]
            },
            {
                question: "6. What does high drawdown indicate?",
                name: "q6_drawdown",
                options: [
                    { label: "A. Strong risk control", value: "incorrect_1" },
                    { label: "B. High volatility in gains", value: "incorrect_2" },
                    { label: "C. Your making large losses on your open positions", value: "correct" },
                    { label: "D. A highly profitable system", value: "incorrect_3" }
                ]
            },
            {
                question: "7. What type of analysis involves charts and indicators?",
                name: "q7_analysis",
                options: [
                    { label: "A. Technical analysis", value: "correct" },
                    { label: "B. Fundamental analysis", value: "incorrect_1" },
                    { label: "C. Emotional analysis", value: "incorrect_2" },
                    { label: "D. Seasonal analysis", value: "incorrect_3" }
                ]
            },
            {
                question: "8. Which one is a technical indicator in forex trading?",
                name: "q8_indicator",
                options: [
                    { label: "A. Relative Strength Index (RSI)", value: "correct" },
                    { label: "B. Weather forecast", value: "incorrect_1" },
                    { label: "C. Speedometer", value: "incorrect_2" },
                    { label: "D. Compass tool", value: "incorrect_3" }
                ]
            },
            {
                question: "9. Which economic event strongly impacts currencies?",
                name: "q9_event",
                options: [
                    { label: "A. Movie releases", value: "incorrect_1" },
                    { label: "B. Central bank interest rate decisions", value: "correct" },
                    { label: "C. Sporting events", value: "incorrect_2" },
                    { label: "D. Local weather", value: "incorrect_3" }
                ]
            },
        ];

        let currentQuestionIndex = 0;
        const quizContent = document.getElementById('quiz-content');
        const nextButton = document.getElementById('next-button');
        const currentQNumber = document.getElementById('current-q-number');
        const totalQNumber = document.getElementById('total-q-number');
        const quizHeader = document.getElementById('quiz-header');
        const quizNavigation = document.getElementById('quiz-navigation');
        const quizSummary = document.getElementById('quiz-summary');
        let selectedAnswers = {}; // Store user's selections

        totalQNumber.textContent = questions.length; // Set the total count

        function renderQuestion() {
            if (currentQuestionIndex >= questions.length) {
                showSummary();
                return;
            }

            const qData = questions[currentQuestionIndex];
            currentQNumber.textContent = currentQuestionIndex + 1;
            nextButton.disabled = !selectedAnswers[qData.name]; // Disable button if no answer selected

            let html = `<h4 class="text-xl font-semibold text-gray-800 mb-6">${qData.question}</h4>`;
            html += `<div class="space-y-4">`;

            qData.options.forEach(option => {
                const isChecked = selectedAnswers[qData.name] === option.value;
                html += `
                    <div>
                        <input type="radio" id="${qData.name}-${option.value}" name="${qData.name}" value="${option.value}" class="answer-option" ${isChecked ? 'checked' : ''}>
                        <label for="${qData.name}-${option.value}" class="flex items-center p-4 border-2 border-primary-purple rounded-lg cursor-pointer transition duration-200 hover:bg-primary-purple hover:text-white hover:border-trophy-gold">
                            <span class="font-medium">${option.label}</span>
                        </label>
                    </div>
                `;
            });

            html += `</div>`;
            quizContent.innerHTML = html;

            // Add event listeners to radio buttons to enable the next button and store the answer
            document.querySelectorAll(`input[name="${qData.name}"]`).forEach(input => {
                input.addEventListener('change', (e) => {
                    selectedAnswers[qData.name] = e.target.value;
                    nextButton.disabled = false;
                });
            });
            
            // Update the button text for the final question
            if (currentQuestionIndex === questions.length - 1) {
                nextButton.textContent = "Submit Quiz & Finish";
            } else {
                nextButton.textContent = "Continue to Next Question";
            }
        }

        function nextQuestion() {
            // Store the current answer before moving
            const qData = questions[currentQuestionIndex];
            const selected = document.querySelector(`input[name="${qData.name}"]:checked`);
            if (selected) {
                selectedAnswers[qData.name] = selected.value;
            } else {
                // Should not happen if button is disabled, but as a fallback
                return; 
            }

            currentQuestionIndex++;
            renderQuestion();
        }

        function showSummary() {
            const knowledgeTestResult = {
                total_questions: questions.length,
                completed_at: new Date().toISOString(),
                answers: selectedAnswers
            };

            // Save to database
            fetch("store_knowledge_test_result.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ knowledge_test_result: knowledgeTestResult })
            })
            .then(res => res.json())
            .then(data => {
                console.log("Knowledge test result saved:", data);
                // Show success message
                quizContent.classList.add('hidden');
                quizHeader.classList.add('hidden');
                quizNavigation.classList.add('hidden');
                quizSummary.classList.remove('hidden');

                // AUTO REDIRECT after 5 seconds
                startAutoRedirectTimer();
            })
            .catch(err => {
                console.error("Error saving knowledge test result:", err);
                // Still show summary even if save fails
                quizContent.classList.add('hidden');
                quizHeader.classList.add('hidden');
                quizNavigation.classList.add('hidden');
                quizSummary.classList.remove('hidden');
            });

            console.log("Knowledge Test Completed. Answers:", selectedAnswers);
        }

        function startAutoRedirectTimer() {
            let redirectTimer = setTimeout(() => {
                window.location.href = "referral_dashboard.php";
            }, 20000);

            // If user interacts with the modal, cancel auto redirect
            const modal = document.getElementById('modal');

            if (modal) {
                modal.addEventListener('click', () => {
                    clearTimeout(redirectTimer);
                });
            }
        }

        // Initialize quiz
        renderQuestion();
        nextButton.addEventListener('click', nextQuestion);
    </script>
</body>
</html>