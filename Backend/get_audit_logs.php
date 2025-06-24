<?php
// Include the database connection
require_once 'db_connect.php';

// Get pagination and search parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // Number of records per page
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build the query
$query = "
    SELECT 
        al.id,
        al.user_id,
        CONCAT(u.first_name, ' ', u.last_name) AS user_name,
        al.action,
        al.table_name,
        al.record_id,
        al.timestamp
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE :search
       OR al.action LIKE :search
       OR al.table_name LIKE :search
       OR al.record_id LIKE :search
       OR al.timestamp LIKE :search
    ORDER BY al.timestamp DESC
    LIMIT :limit OFFSET :offset
";

$countQuery = "
    SELECT COUNT(*) 
    FROM audit_logs al
    JOIN users u ON al.user_id = u.id
    WHERE CONCAT(u.first_name, ' ', u.last_name) LIKE :search
       OR al.action LIKE :search
       OR al.table_name LIKE :search
       OR al.record_id LIKE :search
       OR al.timestamp LIKE :search
";

// Prepare and execute the main query
$stmt = $pdo->prepare($query);
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Get total count for pagination
$countStmt = $pdo->prepare($countQuery);
$countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Format the response
$response = [
    'logs' => [],
    'totalPages' => $totalPages,
    'currentPage' => $page
];

foreach ($logs as $log) {
    $response['logs'][] = [
        'id' => $log['id'],
        'user_name' => $log['user_name'],
        'action' => $log['action'],
        'table_name' => $log['table_name'],
        'record_id' => $log['record_id'],
        'timestamp' => $log['timestamp']
    ];
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;