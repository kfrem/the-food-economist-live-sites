<?php
// api/save-intake.php — endpoint per ARCHITECTURE.md §5. Entity: Intake. Table: epr_intakes.
require __DIR__ . '/config.php';
require_once __DIR__ . '/automation.php';

$d = read_json_or_fail();

$product = ($d['product'] ?? '') === 'forecast' ? 'forecast' : 'snapshot';
$email   = clean((string)($d['email'] ?? ''), 190);
$name    = clean((string)($d['name'] ?? ''), 120);
$company = clean((string)($d['company'] ?? ''), 160);

if (!valid_email($email) || $name === '' || $company === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name, company and a valid email are required.']);
    exit;
}

$phone          = clean((string)($d['phone'] ?? ''), 40);
$turnover_band  = clean((string)($d['turnover_band'] ?? ''), 40);
$tonnage_notes  = clean((string)($d['tonnage_notes'] ?? ''), 6000);
$main_concern   = clean((string)($d['main_concern'] ?? ''), 255);

$materials_json = '';
if (isset($d['materials_json'])) {
    $mj = json_encode($d['materials_json']);
    $materials_json = is_string($mj) ? mb_substr($mj, 0, 6000) : '';
}

try {
    $stmt = db()->prepare(
        'INSERT INTO epr_intakes (product, name, email, company, phone, turnover_band, tonnage_notes, materials_json, main_concern)
         VALUES (:p, :n, :e, :c, :ph, :tb, :tn, :mj, :mc)'
    );
    $stmt->execute([
        ':p' => $product, ':n' => $name, ':e' => $email, ':c' => $company, ':ph' => $phone,
        ':tb' => $turnover_band, ':tn' => $tonnage_notes, ':mj' => $materials_json, ':mc' => $main_concern,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save. Please email your details directly.']);
    exit;
}

tfe_automation_record_intake('epr', [
    'product' => $product,
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'phone' => $phone,
    'turnover_band' => $turnover_band,
    'tonnage_notes' => $tonnage_notes,
    'materials_json' => $materials_json,
    'main_concern' => $main_concern,
]);

notify(
    'EPR INTAKE PAID (' . strtoupper($product) . '): ' . $company,
    "A paying client has submitted intake data.\n"
    . "Product: {$product}\nName: {$name}\nEmail: {$email}\nCompany: {$company}\nPhone: {$phone}\n"
    . "Turnover band: {$turnover_band}\nMain concern: {$main_concern}\n\n"
    . "Tonnage notes:\n{$tonnage_notes}\n\n"
    . "Materials JSON:\n{$materials_json}\n\n"
    . "DEADLINE: snapshot = 48h, forecast = 7 working days."
);

echo json_encode(['ok' => true]);
