<?php
/**
 * db.php – Database connection (PDO)
 * Update the constants below to match your MySQL setup.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'reservation_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;color:#dc2626;">
                    <h2>Database Connection Error</h2>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>Please verify your database settings in <strong>db.php</strong> and ensure
                    you have run <code>sql/setup.sql</code>.</p>
                 </div>');
        }
    }
    return $pdo;
}
