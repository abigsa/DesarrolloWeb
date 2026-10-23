<?php
// Endpoint JSON del autocompletado: devuelve hasta 15 cursos que comienzan con el texto buscado.
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['id_usuario'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}
$term = $_GET['term'] ?? '';
if (!is_string($term) || trim($term) === '') {
    echo json_encode([]);
    exit;
}
require_once 'db.php';
try {
    $stmt = $pdo->prepare('SELECT DISTINCT nombre FROM cursos WHERE nombre LIKE :texto ORDER BY nombre LIMIT 15');
    $stmt->execute([':texto' => trim($term).'%']);
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([]);
}
