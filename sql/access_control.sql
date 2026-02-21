CREATE TABLE IF NOT EXISTS web_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(100) UNIQUE,
    display_name VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS web_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100),
    menu_id INT,
    INDEX(username),
    FOREIGN KEY (menu_id) REFERENCES web_menus(id) ON DELETE CASCADE
);
