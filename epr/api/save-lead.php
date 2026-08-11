<?php
// api/save-lead.php — endpoint per ARCHITECTURE.md §5. Entity: Lead. Table: epr_leads.
require __DIR__ . '/config.php';
require_once __DIR__ . '/automation.php';

$d = read_json_or_fail();

$source = ($d['source'] ?? '') === 'contact' ? 'contact' : 'estimator';
$email  = clean((string)($d['email'] ?? ''), 190);
if (!valid_email($email)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Enter a valid email address.']);
    exit;
}

$name    = clean((string)($d['name'] ?? ''), 120);
$company = clean((string)($d['company'] ?? ''), 160);
$phone   = clean((string)($d['phone'] ?? ''), 40);
$message = clean((string)($d['message'] ?? ''), 2000);

$est_tonnes    = is_numeric($d['est_tonnes'] ?? null)    ? round((float)$d['est_tonnes'], 2)    : null;
$est_liability = is_numeric($d['est_liability'] ?? null) ? round((float)$d['est_liability'], 2) : null;

try {
    $stmt = db()->prepare(
        'INSERT INTO epr_leads (source, name, email, company, phone, est_tonnes, est_liability, message)
         VALUES (:source, :name, :email, :company, :phone, :t, :l, :msg)'
    );
    $stmt->execute([
        ':source' => $source, ':name' => $name, ':email' => $email,
        ':company' => $company, ':phone' => $phone,
        ':t' => $est_tonnes, ':l' => $est_liability, ':msg' => $message,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save. Please email us directly.']);
    exit;
}

tfe_automation_record_lead('epr', [
    'source' => $source,
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'phone' => $phone,
    'message' => $message,
    'tier' => $source === 'contact' ? 'Scoped EPR enquiry' : 'EPR estimator',
], [
    'est_tonnes' => $est_tonnes,
    'est_liability' => $est_liability,
]);

notify(
    'EPR LEAD (' . $source . '): ' . ($company !== '' ? $company : $email),
    "New EPR lead\n"
    . "Source: {$source}\nName: {$name}\nEmail: {$email}\nCompany: {$company}\nPhone: {$phone}\n"
    . "Estimated tonnes: " . ($est_tonnes ?? 'n/a') . "\n"
    . "Estimated liability: £" . ($est_liability !== null ? number_format($est_liability, 2) : 'n/a') . "\n"
    . ($message !== '' ? "Message: {$message}\n" : '')
);

echo json_encode(['ok' => true]);
