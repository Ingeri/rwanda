<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fail(string $message, int $status = 400): never
{
    respond(['error' => ['message' => $message]], $status);
}

function idFromQuery(string $name): int
{
    $value = filter_input(INPUT_GET, $name, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($value === false || $value === null) {
        fail("A positive integer {$name} is required.");
    }
    return $value;
}

function database(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('RWANDA_DB_HOST') ?: '127.0.0.1';
    $port = getenv('RWANDA_DB_PORT') ?: '3306';
    $database = getenv('RWANDA_DB_NAME') ?: 'rwanda';
    $username = getenv('RWANDA_DB_USER') ?: 'admin';
    $password = getenv('RWANDA_DB_PASSWORD') ?: 'Ingeri@49276';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $exception) {
        fail('Database connection failed. Check the API environment variables.', 503);
    }

    return $pdo;
}

function rows(string $table, ?string $foreignKey = null, ?int $parentId = null): array
{
    $allowedTables = ['provinces', 'districts', 'sectors', 'cells', 'villages'];
    if (!in_array($table, $allowedTables, true)) {
        fail('Unknown administrative level.', 404);
    }

    $sql = "SELECT id, name";
    if ($foreignKey !== null) {
        $sql .= ", {$foreignKey} AS parent_id";
    }
    $sql .= " FROM {$table}";
    if ($foreignKey !== null) {
        $sql .= " WHERE {$foreignKey} = :parent_id";
    }
    $sql .= ' ORDER BY name';

    $statement = database()->prepare($sql);
    if ($parentId !== null) {
        $statement->bindValue(':parent_id', $parentId, PDO::PARAM_INT);
    }
    $statement->execute();
    return $statement->fetchAll();
}

function contact(): never
{
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        fail('Request body must be valid JSON.');
    }

    $name = trim((string)($input['name'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $message = trim((string)($input['message'] ?? ''));
    if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fail('Name, a valid email address, and message are required.');
    }
    if (mb_strlen($name) > 100 || mb_strlen($email) > 190 || mb_strlen($message) > 5000) {
        fail('One or more fields exceed the allowed length.');
    }

    try {
        $statement = database()->prepare(
            'INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)'
        );
        $statement->execute([':name' => $name, ':email' => $email, ':message' => $message]);
    } catch (PDOException $exception) {
        fail('Contact messages are not configured. Run api/schema.sql first.', 503);
    }

    respond(['message' => 'Thank you. Your message has been received.'], 201);
}

$path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
$path = preg_replace('#^api/?#', '', $path);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && $path === 'contact') {
    contact();
}
if ($method !== 'GET') {
    fail('Method not allowed.', 405);
}

try {
    if ($path === '' || $path === 'health') {
        respond(['name' => 'Rwanda Administrative API', 'status' => 'ok', 'version' => '1.0.0']);
    }
    if ($path === 'provinces') {
        $data = rows('provinces');
        respond(['data' => $data, 'count' => count($data)]);
    }
    if ($path === 'districts') {
        $parentId = idFromQuery('province_id');
        $data = rows('districts', 'province_id', $parentId);
        respond(['data' => $data, 'count' => count($data), 'province_id' => $parentId]);
    }
    if ($path === 'sectors') {
        $parentId = idFromQuery('district_id');
        $data = rows('sectors', 'district_id', $parentId);
        respond(['data' => $data, 'count' => count($data), 'district_id' => $parentId]);
    }
    if ($path === 'cells') {
        $parentId = idFromQuery('sector_id');
        $data = rows('cells', 'sector_id', $parentId);
        respond(['data' => $data, 'count' => count($data), 'sector_id' => $parentId]);
    }
    if ($path === 'villages') {
        $parentId = idFromQuery('cell_id');
        $data = rows('villages', 'cell_id', $parentId);
        respond(['data' => $data, 'count' => count($data), 'cell_id' => $parentId]);
    }
    fail('Endpoint not found.', 404);
} catch (PDOException $exception) {
    fail('A database query failed.', 500);
}
