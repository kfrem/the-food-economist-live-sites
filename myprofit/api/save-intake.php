<?php
// api/save-intake.php — endpoint per ARCHITECTURE.md §5. Entity: Intake. Table: myprofit_intakes.
require __DIR__ . '/config.php';
require_once __DIR__ . '/automation.php';

$d = read_json_or_fail();

$product = ($d['product'] ?? '') === 'diagnostic' ? 'diagnostic' : 'triage';
$email   = clean((string)($d['email'] ?? ''), 190);
$name    = clean((string)($d['name'] ?? ''), 120);
$company = clean((string)($d['company'] ?? ''), 160);

if (!valid_email($email) || $name === '' || $company === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Name, company and a valid email are required.']);
    exit;
}

$phone          = clean((string)($d['phone'] ?? ''), 40);
$venue_type  = clean((string)($d['venue_type'] ?? ''), 40);
$figures_notes  = clean((string)($d['figures_notes'] ?? ''), 6000);
$main_concern   = clean((string)($d['main_concern'] ?? ''), 255);


try {
    $stmt = db()->prepare(
        'INSERT INTO myprofit_intakes (product, name, email, company, phone, venue_type, figures_notes, main_concern)
         VALUES (:p, :n, :e, :c, :ph, :tb, :tn, :mc)'
    );
    $stmt->execute([
        ':p' => $product, ':n' => $name, ':e' => $email, ':c' => $company, ':ph' => $phone,
        ':tb' => $venue_type, ':tn' => $figures_notes, ':mc' => $main_concern,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save. Please email your details directly.']);
    exit;
}

tfe_automation_record_intake('myprofit', [
    'product' => $product,
    'name' => $name,
    'email' => $email,
    'company' => $company,
    'phone' => $phone,
    'venue_type' => $venue_type,
    'figures_notes' => $figures_notes,
    'main_concern' => $main_concern,
]);

notify(
    'MYPROFIT INTAKE PAID (' . strtoupper($product) . '): ' . $company,
    "A paying client has submitted intake data.\n"
    . "Product: {$product}\nName: {$name}\nEmail: {$email}\nCompany: {$company}\nPhone: {$phone}\n"
    . "Venue type: {$venue_type}\nMain concern: {$main_concern}\n\n"
    . "Figures and notes:\n{$figures_notes}\n\n"
    . "DEADLINE: triage = 48h, diagnostic = 7 working days."
);

echo json_encode(['ok' => true]);
