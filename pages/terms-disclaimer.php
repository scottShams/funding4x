<?php
$title = "Terms & Disclaimers";
$border_color = "primary-purple";
$page_title = "Terms & Disclaimers";
$last_updated = "Last Updated: 24-11-2025";
$intro = "Before creating an account with <span class=\"font-bold text-primary-purple\">Funding4x</span>, please read and agree to the following summary of our Terms and Disclaimers. By ticking the checkbox, you confirm that you understand and accept these points and agree to be bound by our full policies.";
$content = '
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
                
            ';
include 'layout.php';
?>