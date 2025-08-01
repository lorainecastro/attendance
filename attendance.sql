CREATE TABLE teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR (50) NOT NULL,
    lastname VARCHAR (50) NOT NULL,
    institution VARCHAR (255),
    email VARCHAR (100) NOT NULL UNIQUE,
    username VARCHAR (30) NOT NULL UNIQUE,
    password VARCHAR (255) NOT NULL,
    picture VARCHAR (255) DEFAULT 'no-icon.png',
    isActive TINYINT (1) DEFAULT 0,
    isVerified TINYINT (1) DEFAULT 0,
    otp_code VARCHAR (6),
    otp_purpose VARCHAR (50),
    otp_created_at DATETIME,
    otp_expires_at DATETIME,
    otp_is_used tinyint (1) DEFAULT 0,
    created_at timestamp NOT NULL DEFAULT current_timestamp ()
);
CREATE TABLE teacher_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    session_token VARCHAR (64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers (teacher_id)
);