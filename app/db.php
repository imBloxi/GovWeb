<?php
declare(strict_types=1);

// Thin PDO wrapper. Always pass user input as $params, never inside $sql.

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config('db');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_run($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    return db_run($sql, $params)->fetch() ?: null;
}

function db_value(string $sql, array $params = []): mixed
{
    $value = db_run($sql, $params)->fetchColumn();
    return $value === false ? null : $value;
}

/** Insert a row from an associative array and return the new id. */
function db_insert(string $table, array $data): int
{
    $columns = array_keys($data);
    $sql = sprintf(
        'INSERT INTO `%s` (`%s`) VALUES (%s)',
        $table,
        implode('`, `', $columns),
        implode(', ', array_map(fn($c) => ':' . $c, $columns))
    );
    db_run($sql, $data);
    return (int) db()->lastInsertId();
}

function db_update(string $table, int $id, array $data): void
{
    $sets = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
    db_run("UPDATE `$table` SET $sets WHERE id = :__id", $data + ['__id' => $id]);
}

function db_delete(string $table, int $id): void
{
    db_run("DELETE FROM `$table` WHERE id = ?", [$id]);
}

/** Run $fn inside a transaction; roll back if it throws. */
function db_transaction(callable $fn): mixed
{
    db()->beginTransaction();
    try {
        $result = $fn();
        db()->commit();
        return $result;
    } catch (Throwable $e) {
        db()->rollBack();
        throw $e;
    }
}

/** True when $e is a unique-key violation (MySQL error 1062). */
function is_duplicate(PDOException $e): bool
{
    return ($e->errorInfo[1] ?? null) === 1062;
}

/**
 * Paginate a query. $sql must be a SELECT without LIMIT;
 * $countSql must return a single number.
 */
function paginate(string $sql, string $countSql, array $params = [], int $perPage = 20): array
{
    $total = (int) db_value($countSql, $params);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, input_int('page') ?? 1), $pages);
    $offset = ($page - 1) * $perPage;
    $rows = db_all("$sql LIMIT $perPage OFFSET $offset", $params);
    return compact('rows', 'total', 'page', 'pages');
}

/** Write a line to the activity log. */
function audit(string $action, string $entity, ?int $entityId, string $summary): void
{
    db_insert('audit_log', [
        'user_id' => current_user()['id'] ?? null,
        'action' => $action,
        'entity' => $entity,
        'entity_id' => $entityId,
        'summary' => mb_substr($summary, 0, 255),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
