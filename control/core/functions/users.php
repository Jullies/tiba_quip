<?php
if(session_status() === PHP_SESSION_NONE){
	session_start();
}

	function admin_require_login(){
		if(!isset($_SESSION['programUser']) || !isset($_SESSION['programUserLevel'])){
			header('Location: ../login.php');
			exit;
		}
		if($_SESSION['programUserLevel'] !== 'admin'){
			header('Location: ../access_denied.php');
			exit;
		}
	}

	function is_user_logged_in(){
		return isset($_SESSION['programUser']) && isset($_SESSION['user_id']);
	}

	function current_user_profile($conn){
		if(!is_user_logged_in()){
			return null;
		}
		$userId = (int)($_SESSION['user_id'] ?? 0);
		if($userId <= 0){
			return null;
		}
		$query = mysqli_query($conn, "SELECT full_name, email, phone, user_level FROM webaccounts WHERE user_id = $userId LIMIT 1");
		if($query && mysqli_num_rows($query) > 0){
			return mysqli_fetch_assoc($query);
		}
		return null;
	}
?>
