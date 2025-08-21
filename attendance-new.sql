CREATE TABLE teachers (
    teacher_id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    institution VARCHAR(255),
    email VARCHAR(100) NOT NULL UNIQUE,
    username VARCHAR(30) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    picture VARCHAR(255) DEFAULT 'no-icon.png',
    isActive TINYINT(1) DEFAULT 0,
    isVerified TINYINT(1) DEFAULT 0,
    otp_code VARCHAR(6),
    otp_purpose VARCHAR(50),
    otp_created_at DATETIME,
    otp_expires_at DATETIME,
    otp_is_used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE teacher_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
);

CREATE TABLE subjects (
    subject_id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
    class_id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(50) NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    room VARCHAR(50),
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(subject_id),
    FOREIGN KEY (teacher_id) REFERENCES teachers(teacher_id)
);

CREATE TABLE students (
    lrn BIGINT NOT NULL PRIMARY KEY,
    last_name VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    gender VARCHAR(20),
    dob DATE,
    grade_level VARCHAR(20),
    address VARCHAR(255),
    parent_name VARCHAR(100),
    emergency_contact VARCHAR(20),
    photo VARCHAR(255) DEFAULT 'no-icon.png',
    qr_code VARCHAR(255) DEFAULT NULL,
    date_added DATE
);

CREATE TABLE schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    day ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
);

CREATE TABLE class_students (
    class_id INT NOT NULL,
    lrn BIGINT NOT NULL,
    is_enrolled TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (class_id, lrn),
    FOREIGN KEY (class_id) REFERENCES classes(class_id),
    FOREIGN KEY (lrn) REFERENCES students(lrn)
);

CREATE TABLE attendance_tracking (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    lrn BIGINT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_status  ENUM('Present', 'Absent', 'Late') NULL,
    reason ENUM('Health Issue', 'Household Income', 'Transportation', 'Family Structure', 'No Reason', 'Other') NULL,
    time_checked DATETIME,
    logged_by ENUM('Teacher', 'QR') NULL,
    FOREIGN KEY (lrn) REFERENCES students(lrn),
    FOREIGN KEY (class_id) REFERENCES classes(class_id)
);