CREATE TABLE users (
    ->     id INT AUTO_INCREMENT PRIMARY KEY,
    ->     username VARCHAR(100) NOT NULL,
    ->     password VARCHAR(255) NOT NULL,
    ->     role ENUM('admin','user','manager') NOT NULL
    -> );