CREATE TABLE IF NOT EXISTS product_reviews (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    source_id bigint unsigned NULL UNIQUE,
    product_id bigint unsigned NOT NULL,
    user_id bigint unsigned NULL,
    reviewer_key char(64) NOT NULL,
    author_name varchar(190) NOT NULL,
    email varchar(190) NULL,
    rating tinyint unsigned NOT NULL,
    body text NOT NULL,
    status enum('published','hidden') NOT NULL DEFAULT 'published',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY product_reviewer (product_id,reviewer_key),
    INDEX product_status (product_id,status,created_at),
    FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    CHECK(rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS site_reviews (
    id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
    user_id bigint unsigned NULL,
    reviewer_key char(64) NOT NULL UNIQUE,
    author_name varchar(190) NOT NULL,
    email varchar(190) NULL,
    rating tinyint unsigned NOT NULL,
    body text NOT NULL,
    status enum('pending','published','hidden') NOT NULL DEFAULT 'pending',
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX status_created (status,created_at),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
    CHECK(rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
