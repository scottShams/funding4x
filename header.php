<?php
    session_start();
    $email = $_SESSION['user_email'];
?>
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
                <?php if(isset($_SESSION['user_email'])): ?>
                    <a href="referral_dashboard.php" class="text-gray-300 hover:text-trophy-gold transition duration-150 font-medium">Dashboard</a>
                <?php else: ?>
                    <a href="referral_dashboard.php" class="text-gray-300 hover:text-trophy-gold transition duration-150 font-medium">Login</a>
                <?php endif; ?>
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
        <?php if(isset($_SESSION['user_email'])): ?>
            <a href="referral_dashboard.php" class="block px-3 py-2 rounded-md text-base font-medium bg-primary-purple text-white mt-2">Dashboard</a>
        <?php else: ?>
            <a href="pricing.php" class="block px-3 py-2 rounded-md text-base font-medium bg-primary-purple text-white mt-2">Login</a>
        <?php endif; ?>
    </div>
</header>