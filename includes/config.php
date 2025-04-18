<?php
// Application settings
define("APP_NAME", "ሕማማት");
define("APP_FULL_NAME", "የሰሙነ ሕማማት የመንፈሳዊ ምግባራት መከታተያ ፕሮግራም ");
define("APP_VERSION", "1.0.0");

// Session configuration
session_start();

// Error reporting (turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Time zone setting
date_default_timezone_set('Europe/London');

// Site URLs
define("BASE_URL", "http://".$_SERVER['HTTP_HOST']);
define("USER_URL", BASE_URL."/user");
define("ADMIN_URL", BASE_URL."/admin");

// Security settings
define("HASH_COST", 10); // For password bcrypt
?> 