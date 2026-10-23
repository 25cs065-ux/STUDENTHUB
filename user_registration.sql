```sql
CREATE DATABASE IF NOT EXISTS studenthub;

USE studenthub;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    course VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    gender VARCHAR(20) NOT NULL,
    terms_accepted TINYINT(1) NOT NULL DEFAULT 0,
    role ENUM('Admin', 'Student') NOT NULL DEFAULT 'Student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```