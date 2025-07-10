<?php
echo "Starting username check...\n";

try {
    require_once 'config/database.php';
    echo "Database config loaded.\n";
    
    $db = new Database();
    echo "Database object created.\n";
    
    $conn = $db->getConnection();
    echo "Database connection established.\n";
    
    echo "=== Checking for empty usernames ===\n";
    
    // Check for empty usernames
    $stmt = $conn->query("SELECT id, username, email FROM users WHERE username = '' OR username IS NULL");
    $empty_usernames = $stmt->fetchAll();
    
    if (empty($empty_usernames)) {
        echo "No empty usernames found.\n";
    } else {
        echo "Found " . count($empty_usernames) . " records with empty usernames:\n";
        foreach ($empty_usernames as $user) {
            echo "ID: {$user['id']}, Username: '{$user['username']}', Email: {$user['email']}\n";
        }
    }
    
    echo "\n=== Checking for duplicate usernames ===\n";
    
    // Check for duplicate usernames
    $stmt = $conn->query("SELECT username, COUNT(*) as count FROM users WHERE username != '' AND username IS NOT NULL GROUP BY username HAVING COUNT(*) > 1");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "No duplicate usernames found.\n";
    } else {
        echo "Found duplicate usernames:\n";
        foreach ($duplicates as $dup) {
            echo "Username: '{$dup['username']}' appears {$dup['count']} times\n";
        }
    }
    
    echo "\n=== Checking table structure ===\n";
    
    // Check table structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'username') {
            echo "Username column: {$column['Type']}, Null: {$column['Null']}, Key: {$column['Key']}, Default: {$column['Default']}\n";
        }
    }
    
    echo "\n=== Total users count ===\n";
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $total = $stmt->fetch();
    echo "Total users: {$total['total']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "Script completed.\n";
?> 