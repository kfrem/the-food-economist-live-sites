<?php
// api/save-dpa.php — records online acceptance of the Data Protection Agreement.
require __DIR__ . '/config.php';

const DPA_VERSION = '2026-09-08-v1';

$d = read_json_or_fail();

$name     = clean((string)($d['name'] ?? ''), 120);
$role     = clean((string)($d['role'] ?? ''), 120);
$company  = clean((string)($d['company'] ?? ''), 160);
$email    = clean((string)($d['email'] ?? ''), 190);
$signature = clean((string)($d['signature'] ?? ''), 120);
$version  = clean((string)($d['version'] ?? ''), 40);
$agreed   = !empty($d['agreed']);
$authority = !empty($d['authority']);

if (!valid_email($email) || $name === '' || $company === '' || $signature === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name, company, email and a typed signature are required.']);
    exit;
}
if (!$agreed || !$authority) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'You must confirm authority and accept the agreement.']);
    exit;
}
if (strcasecmp($signature, $name) !== 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'The typed signature must match the name above.']);
    exit;
}
if ($version !== DPA_VERSION) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Please reload the page and sign the current agreement.']);
    exit;
}

$ip = clean((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''), 80);
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
$ua = clean((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 250);

try {
    db()->exec(
        'CREATE TABLE IF NOT EXISTS myprofit_dpa_acceptances (
          id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          version       VARCHAR(40) NOT NULL,
          name          VARCHAR(120) NOT NULL,
          role          VARCHAR(120) NOT NULL DEFAULT "",
          company       VARCHAR(160) NOT NULL,
          email         VARCHAR(190) NOT NULL,
          signature     VARCHAR(120) NOT NULL,
          ip            VARCHAR(80) NOT NULL DEFAULT "",
          user_agent    VARCHAR(250) NOT NULL DEFAULT "",
          INDEX idx_email (email),
          INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );
} catch (Throwable $e) {
    // Table may already exist, or CREATE may be disallowed; INSERT will surface a real failure.
}

try {
    $stmt = db()->prepare(
        'INSERT INTO myprofit_dpa_acceptances
         (version, name, role, company, email, signature, ip, user_agent)
         VALUES (:v, :n, :r, :c, :e, :s, :ip, :ua)'
    );
    $stmt->execute([
        ':v' => DPA_VERSION,
        ':n' => $name,
        ':r' => $role,
        ':c' => $company,
        ':e' => $email,
        ':s' => $signature,
        ':ip' => $ip,
        ':ua' => $ua,
    ]);
    $id = (int) db()->lastInsertId();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save. Please email contact@thefoodeconomist.co.uk.']);
    exit;
}

$ref = 'DPA-' . date('Ymd') . '-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT);

notify(
    'MYPROFIT DPA SIGNED: ' . $company,
    "Data Protection Agreement accepted online.\n"
    . "Reference: {$ref}\nVersion: " . DPA_VERSION . "\n"
    . "Name: {$name}\nRole: {$role}\nCompany: {$company}\nEmail: {$email}\n"
    . "Signature: {$signature}\nIP: {$ip}\n"
);

echo json_encode(['ok' => true, 'ref' => $ref, 'version' => DPA_VERSION]);
