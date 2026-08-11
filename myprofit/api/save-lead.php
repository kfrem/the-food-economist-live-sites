<?php
// api/save-lead.php — endpoint per ARCHITECTURE.md §5. Entity: Lead. Table: myprofit_leads.
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

$est_revenue    = is_numeric($d['est_revenue'] ?? null)    ? round((float)$d['est_revenue'], 2)    : null;
$est_margin_pp = is_numeric($d['est_margin_pp'] ?? null) ? round((float)$d['est_margin_pp'], 2) : null;

try {
    $stmt = db()->prepare(
        'INSERT INTO myprofit_leads (source, name, email, company, phone, est_revenue, est_margin_pp, message)
         VALUES (:source, :name, :email, :company, :phone, :t, :l, :msg)'
    );
    $stmt->execute([
        ':source' => $source, ':name' => $name, ':email' => $email,
        ':company' => $company, ':phone' => $phone,
        ':t' => $est_revenue, ':l' => $est_margin_pp, ':msg' => $message,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save. Please email us directly.']);
    exit;
}

tfe_automation_record_lead('myprofit', [
    'source' => $source,
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'phone' => $phone,
    'message' => $message,
    'tier' => $source === 'contact' ? 'Scoped MyProfit enquiry' : 'MyProfit Restaurant Profit Check',
], [
    'est_revenue' => $est_revenue,
    'est_margin_pp' => $est_margin_pp,
]);

notify(
    'MYPROFIT LEAD (' . $source . '): ' . ($company !== '' ? $company : $email),
    "New MyProfit lead\n"
    . "Source: {$source}\nName: {$name}\nEmail: {$email}\nCompany: {$company}\nPhone: {$phone}\n"
    . "Est monthly sales: " . ($est_revenue ?? 'n/a') . "\n"
    // est_margin_pp carries the percentage of sales left after the costs the
    // visitor entered. Negative means the costs entered exceeded sales.
    . "Left after entered costs (% of sales): " . ($est_margin_pp !== null ? number_format($est_margin_pp, 2) : 'n/a') . "\n"
    . ($message !== '' ? "Message: {$message}\n" : '')
);

echo json_encode(['ok' => true]);
