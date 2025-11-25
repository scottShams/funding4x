CREATE TABLE blocked_ips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(50) NOT NULL UNIQUE,
    blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- Add index for faster lookups
CREATE INDEX idx_ip_address ON blocked_ips(ip_address);