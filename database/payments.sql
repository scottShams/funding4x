-- Payments table schema for both crypto and credit/debit card payments
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    payment_method ENUM('crypto', 'credit_card') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USD',
    status ENUM('pending', 'completed', 'refund', 'failed', 'cancelled') DEFAULT 'pending',

    -- Crypto payment fields
    crypto_type VARCHAR(20), -- e.g., 'USDC', 'BTC', 'ETH'
    crypto_network VARCHAR(50), -- e.g., 'Tron', 'Arbitrum'
    wallet_address VARCHAR(255),
    transaction_hash VARCHAR(255),

    -- Credit card payment fields (encrypted)
    card_number_encrypted TEXT, -- Encrypted card number
    card_expiry_encrypted TEXT, -- Encrypted expiry
    card_cvc_encrypted TEXT, -- Encrypted CVC
    card_name VARCHAR(255), -- Cardholder name (not encrypted as it's not sensitive)

    -- Common fields
    payment_gateway VARCHAR(100), -- e.g., 'Stripe', 'CoinPayments'
    gateway_transaction_id VARCHAR(255),
    notes TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES waitlist_users(id) ON DELETE CASCADE,

    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_payment_method (payment_method),
    INDEX idx_created_at (created_at)
);

-- Sample insert for crypto payment
-- INSERT INTO payments (user_id, payment_method, amount, crypto_type, crypto_network, wallet_address, transaction_hash, status)
-- VALUES (1, 'crypto', 59.00, 'USDC', 'Tron', 'TRTrqYNy2DwjJiQ15AcmJDMyyh39gqai17', '0xabc123...', 'completed');

-- Sample insert for credit card payment
-- INSERT INTO payments (user_id, payment_method, amount, card_name, gateway_transaction_id, status)
-- VALUES (1, 'credit_card', 59.00, 'John Doe', 'ch_1234567890', 'completed');

-- Payment source tracking
ALTER TABLE payments
ADD COLUMN payment_source ENUM('user', 'admin') NOT NULL DEFAULT 'user'
AFTER notes,
ADD COLUMN created_by_admin_id INT UNSIGNED NULL
AFTER payment_source;