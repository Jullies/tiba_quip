<?php
function ensure_agronomy_tables($conn){
	$agronomistSql = "
		CREATE TABLE IF NOT EXISTS agronomists (
			agronomist_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
			full_name VARCHAR(155) NOT NULL,
			phone VARCHAR(50) DEFAULT NULL,
			email VARCHAR(155) DEFAULT NULL,
			location VARCHAR(155) DEFAULT NULL,
			specialty VARCHAR(155) DEFAULT NULL,
			experience_years VARCHAR(50) DEFAULT NULL,
			certifications TEXT NULL,
			id_number VARCHAR(80) DEFAULT NULL,
			id_front_path VARCHAR(255) DEFAULT NULL,
			id_back_path VARCHAR(255) DEFAULT NULL,
			resume_path VARCHAR(255) DEFAULT NULL,
			facebook VARCHAR(255) DEFAULT NULL,
			twitter VARCHAR(255) DEFAULT NULL,
			linkedin VARCHAR(255) DEFAULT NULL,
			instagram VARCHAR(255) DEFAULT NULL,
			website VARCHAR(255) DEFAULT NULL,
            about_self TEXT NULL,
            summary TEXT NULL,
            photo_path VARCHAR(255) DEFAULT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            rating DECIMAL(3,1) NOT NULL DEFAULT 0,
            times_booked INT NOT NULL DEFAULT 0,
            approved_at DATETIME NULL,
            approved_by INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
	if(!mysqli_query($conn, $agronomistSql)){
		return false;
	}

	$result = mysqli_query($conn, "SHOW COLUMNS FROM agronomists");
	if($result){
		$columns = [];
		while($row = mysqli_fetch_assoc($result)){
			$columns[$row['Field']] = true;
		}
		$requiredColumns = [
			'id_number' => "ALTER TABLE agronomists ADD COLUMN id_number VARCHAR(80) DEFAULT NULL",
			'id_front_path' => "ALTER TABLE agronomists ADD COLUMN id_front_path VARCHAR(255) DEFAULT NULL",
			'id_back_path' => "ALTER TABLE agronomists ADD COLUMN id_back_path VARCHAR(255) DEFAULT NULL",
			'resume_path' => "ALTER TABLE agronomists ADD COLUMN resume_path VARCHAR(255) DEFAULT NULL",
			'facebook' => "ALTER TABLE agronomists ADD COLUMN facebook VARCHAR(255) DEFAULT NULL",
			'twitter' => "ALTER TABLE agronomists ADD COLUMN twitter VARCHAR(255) DEFAULT NULL",
			'linkedin' => "ALTER TABLE agronomists ADD COLUMN linkedin VARCHAR(255) DEFAULT NULL",
			'instagram' => "ALTER TABLE agronomists ADD COLUMN instagram VARCHAR(255) DEFAULT NULL",
			'website' => "ALTER TABLE agronomists ADD COLUMN website VARCHAR(255) DEFAULT NULL",
			'about_self' => "ALTER TABLE agronomists ADD COLUMN about_self TEXT NULL",
			'summary' => "ALTER TABLE agronomists ADD COLUMN summary TEXT NULL",
			'photo_path' => "ALTER TABLE agronomists ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL",
			'status' => "ALTER TABLE agronomists ADD COLUMN status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'",
			'rating' => "ALTER TABLE agronomists ADD COLUMN rating DECIMAL(3,1) NOT NULL DEFAULT 0",
			'times_booked' => "ALTER TABLE agronomists ADD COLUMN times_booked INT NOT NULL DEFAULT 0",
			'approved_at' => "ALTER TABLE agronomists ADD COLUMN approved_at DATETIME NULL",
			'approved_by' => "ALTER TABLE agronomists ADD COLUMN approved_by INT NULL",
			'created_at' => "ALTER TABLE agronomists ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
			'updated_at' => "ALTER TABLE agronomists ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
		];
		foreach($requiredColumns as $columnName => $alterSql){
			if(!isset($columns[$columnName])){
				@mysqli_query($conn, $alterSql);
			}
		}
	}

	$bookingSql = "
        CREATE TABLE IF NOT EXISTS agronomist_bookings (
            booking_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            agronomist_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            client_name VARCHAR(155) NOT NULL,
            client_phone VARCHAR(50) NOT NULL,
            client_email VARCHAR(155) DEFAULT NULL,
            client_location VARCHAR(155) NOT NULL,
            preferred_date DATE NOT NULL,
            preferred_time TIME NOT NULL,
            notes TEXT NULL,
            status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_agronomist (agronomist_id),
            INDEX idx_status (status),
            INDEX idx_date (preferred_date),
            CONSTRAINT fk_agronomist_booking FOREIGN KEY (agronomist_id)
                REFERENCES agronomists(agronomist_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    if(!mysqli_query($conn, $bookingSql)){
        return false;
    }
    return true;
}

function fetch_approved_agronomists($conn){
	$tableCheck = @mysqli_query($conn, "SHOW TABLES LIKE 'agronomists'");
	if(!$tableCheck || mysqli_num_rows($tableCheck) === 0){
		return [];
	}
	if(!ensure_agronomy_tables($conn)){
		return [];
	}
	$rows = [];
	$query = @mysqli_query($conn, "SELECT * FROM agronomists WHERE status = 'approved' ORDER BY created_at DESC");
	if($query){
		while($row = mysqli_fetch_assoc($query)){
			$rows[] = $row;
		}
	}
	return $rows;
}
?>
