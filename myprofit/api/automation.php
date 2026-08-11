<?php

function tfe_automation_record_lead(string $site, array $lead, array $signals = []): void {
    try {
        $score = tfe_score_lead($site, $lead, $signals);
        $reason = tfe_attention_reason($site, $lead, $signals, $score);
        $needsAttention = $reason !== '';
        $email = strtolower((string)($lead['email'] ?? ''));
        if ($email === '') return;

        $stmt = db()->prepare(
            'INSERT INTO tfe_leads
             (email, name, company, phone, last_site, last_source, tier, stage, score, needs_attention, attention_reason, last_event, last_event_at)
             VALUES
             (:email, :name, :company, :phone, :site, :source, :tier, :stage, :score, :needs, :reason, :event, NOW())
             ON DUPLICATE KEY UPDATE
               name = IF(VALUES(name) <> "", VALUES(name), name),
               company = IF(VALUES(company) <> "", VALUES(company), company),
               phone = IF(VALUES(phone) <> "", VALUES(phone), phone),
               last_site = VALUES(last_site),
               last_source = VALUES(last_source),
               tier = IF(VALUES(tier) <> "", VALUES(tier), tier),
               score = GREATEST(score, VALUES(score)),
               needs_attention = GREATEST(needs_attention, VALUES(needs_attention)),
               attention_reason = IF(VALUES(attention_reason) <> "", VALUES(attention_reason), attention_reason),
               last_event = VALUES(last_event),
               last_event_at = NOW()'
        );
        $stmt->execute([
            ':email' => $email,
            ':name' => (string)($lead['name'] ?? ''),
            ':company' => (string)($lead['company'] ?? ''),
            ':phone' => (string)($lead['phone'] ?? ''),
            ':site' => $site,
            ':source' => (string)($lead['source'] ?? ''),
            ':tier' => (string)($lead['tier'] ?? ''),
            ':stage' => 'acknowledged',
            ':score' => $score,
            ':needs' => $needsAttention ? 1 : 0,
            ':reason' => $reason,
            ':event' => 'Lead captured and acknowledged',
        ]);
        $leadId = (int)db()->query('SELECT id FROM tfe_leads WHERE email = ' . db()->quote($email))->fetchColumn();
        tfe_activity($leadId, $site, 'lead_captured', (string)($lead['source'] ?? ''), tfe_summary($lead), $lead + $signals);
        tfe_meetingpipeline_record_lead($site, $lead, $signals, $score, $reason);
        tfe_acknowledge_lead($site, $lead);
        if ($needsAttention) {
            tfe_alert_owner('Needs-owner lead: ' . ($lead['company'] ?: $email), $reason . "\n\n" . tfe_summary($lead));
        }
    } catch (Throwable $e) {
        tfe_system_failure('lead_automation', 'Lead automation failed', $e->getMessage());
    }
}

function tfe_automation_record_booking(string $site, array $booking, string $status): void {
    try {
        $email = strtolower((string)($booking['email'] ?? ''));
        if ($email === '') return;
        $reason = $status === 'booked' ? 'Prepared call booked' : 'Prepared call cancelled';
        $stmt = db()->prepare(
            'INSERT INTO tfe_leads
             (email, name, company, phone, last_site, last_source, tier, stage, score, needs_attention, attention_reason, last_event, last_event_at)
             VALUES (:email, :name, :company, :phone, :site, :source, :tier, :stage, :score, :needs, :reason, :event, NOW())
             ON DUPLICATE KEY UPDATE
               name = IF(VALUES(name) <> "", VALUES(name), name),
               company = IF(VALUES(company) <> "", VALUES(company), company),
               phone = IF(VALUES(phone) <> "", VALUES(phone), phone),
               last_site = VALUES(last_site),
               last_source = VALUES(last_source),
               tier = VALUES(tier),
               stage = "engaged",
               score = GREATEST(score, VALUES(score)),
               needs_attention = 1,
               attention_reason = VALUES(attention_reason),
               last_event = VALUES(last_event),
               last_event_at = NOW()'
        );
        $stmt->execute([
            ':email' => $email,
            ':name' => (string)($booking['name'] ?? ''),
            ':company' => (string)($booking['business'] ?? ''),
            ':phone' => (string)($booking['phone'] ?? ''),
            ':site' => $site,
            ':source' => 'booking',
            ':tier' => (string)($booking['service'] ?? ''),
            ':stage' => 'engaged',
            ':score' => 90,
            ':needs' => 1,
            ':reason' => $reason,
            ':event' => $reason,
        ]);
        $leadId = (int)db()->query('SELECT id FROM tfe_leads WHERE email = ' . db()->quote($email))->fetchColumn();
        tfe_activity($leadId, $site, 'booking_' . $status, 'booking', $reason, $booking);
        tfe_meetingpipeline_record_booking($site, $booking, $status);
    } catch (Throwable $e) {
        tfe_system_failure('booking_automation', 'Booking automation failed', $e->getMessage());
    }
}

function tfe_automation_record_intake(string $site, array $intake): void {
    try {
        $email = strtolower((string)($intake['email'] ?? ''));
        if ($email === '') return;
        $company = (string)($intake['company'] ?? '');
        $product = (string)($intake['product'] ?? '');
        $summary = 'Paid intake submitted: ' . ($product !== '' ? $product : 'intake');
        $value = tfe_meetingpipeline_value($site, $product, 'intake');

        tfe_meetingpipeline_upsert_lead($site, [
            'email' => $email,
            'name' => (string)($intake['name'] ?? ''),
            'company' => $company,
            'phone' => (string)($intake['phone'] ?? ''),
            'source' => 'paid_intake',
            'tier' => $product,
            'stage' => 'paid',
            'score' => 100,
            'needs_attention' => 1,
            'attention_reason' => 'Paid client intake needs delivery action.',
            'last_event' => $summary,
            'estimated_value' => $value,
        ]);
        $mpLeadId = tfe_meetingpipeline_lead_id($site, $email);
        tfe_meetingpipeline_event($mpLeadId, $site, 'intake_submitted', 'paid_intake', $summary, $intake);
        tfe_meetingpipeline_task($site, 'Prepare delivery for ' . ($company !== '' ? $company : $email), $summary, 'urgent', '+2 days');
    } catch (Throwable $e) {
        tfe_system_failure('meetingpipeline_intake_sync', 'MeetingPipeline intake sync failed', $e->getMessage());
    }
}

function tfe_score_lead(string $site, array $lead, array $signals): int {
    $score = (($lead['source'] ?? '') === 'contact') ? 35 : 15;
    if (($lead['company'] ?? '') !== '') $score += 10;
    if (($lead['phone'] ?? '') !== '') $score += 10;
    if (($lead['message'] ?? '') !== '') $score += 10;
    if ($site === 'epr' && (float)($signals['est_liability'] ?? 0) >= 10000) $score += 30;
    if ($site === 'myprofit' && (float)($signals['est_revenue'] ?? 0) >= 40000) $score += 20;
    if ($site === 'myprofit' && (float)($signals['est_margin_pp'] ?? 0) >= 5) $score += 20;
    return min(100, $score);
}

function tfe_attention_reason(string $site, array $lead, array $signals, int $score): string {
    if (($lead['source'] ?? '') === 'contact') return 'Scoped enquiry needs a written reply or quote.';
    if ($site === 'epr' && (float)($signals['est_liability'] ?? 0) >= 10000) return 'High estimated EPR liability.';
    if ($site === 'myprofit' && (float)($signals['est_margin_pp'] ?? 0) >= 5) return 'High margin pressure signal.';
    if ($score >= 55) return 'High lead score.';
    return '';
}

function tfe_acknowledge_lead(string $site, array $lead): void {
    $email = (string)($lead['email'] ?? '');
    if (!valid_email($email)) return;
    $desk = $site === 'epr' ? 'EPR desk' : 'MyProfit desk';
    $subject = $site === 'epr' ? 'Your EPR enquiry has been received' : 'Your MyProfit enquiry has been received';
    $body = "Thanks for sending this through.\n\n"
        . "This is an automatic confirmation from The Food Economist {$desk}. Your details have been received and logged. "
        . "If your note needs a prepared reply, it is now in the owner review queue.\n\n"
        . "Nothing in this email is accountancy, tax, legal or statutory filing advice. The Food Economist provides independent economic and regulatory-data analysis; you should rely on your own appointed advisers for formal filing, tax or legal decisions.\n\n"
        . "The Food Economist\n";
    @mail($email, $subject, $body, "From: " . FROM_EMAIL . "\r\nContent-Type: text/plain; charset=utf-8");
}

function tfe_activity(?int $leadId, string $site, string $eventType, string $source, string $summary, array $payload): void {
    $stmt = db()->prepare(
        'INSERT INTO tfe_activity (lead_id, site, event_type, source, summary, payload)
         VALUES (:lead_id, :site, :event_type, :source, :summary, :payload)'
    );
    $stmt->execute([
        ':lead_id' => $leadId ?: null,
        ':site' => $site,
        ':event_type' => $eventType,
        ':source' => $source,
        ':summary' => substr($summary, 0, 255),
        ':payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
}

function tfe_meetingpipeline_record_lead(string $site, array $lead, array $signals, int $score, string $reason): void {
    $email = strtolower((string)($lead['email'] ?? ''));
    if ($email === '') return;
    $source = (string)($lead['source'] ?? '');
    $tier = (string)($lead['tier'] ?? '');
    $value = tfe_meetingpipeline_value($site, $tier, $source);
    $summary = 'Lead captured: ' . tfe_summary($lead);

    tfe_meetingpipeline_upsert_lead($site, [
        'email' => $email,
        'name' => (string)($lead['name'] ?? ''),
        'company' => (string)($lead['company'] ?? ''),
        'phone' => (string)($lead['phone'] ?? ''),
        'source' => $source,
        'tier' => $tier,
        'stage' => 'enquiry',
        'score' => $score,
        'needs_attention' => $reason !== '' ? 1 : 0,
        'attention_reason' => $reason,
        'last_event' => $summary,
        'estimated_value' => $value,
    ]);
    $mpLeadId = tfe_meetingpipeline_lead_id($site, $email);
    tfe_meetingpipeline_event($mpLeadId, $site, 'lead_captured', $source, $summary, $lead + $signals);
    if ($reason !== '') {
        tfe_meetingpipeline_task($site, 'Follow up ' . (($lead['company'] ?? '') ?: $email), $reason, 'high', '+1 day');
    }
}

function tfe_meetingpipeline_record_booking(string $site, array $booking, string $status): void {
    $email = strtolower((string)($booking['email'] ?? ''));
    if ($email === '') return;
    $reason = $status === 'booked' ? 'Prepared call booked' : 'Prepared call cancelled';
    $stage = $status === 'booked' ? 'quoted' : 'lost';
    $service = (string)($booking['service'] ?? '');

    tfe_meetingpipeline_upsert_lead($site, [
        'email' => $email,
        'name' => (string)($booking['name'] ?? ''),
        'company' => (string)($booking['business'] ?? ''),
        'phone' => (string)($booking['phone'] ?? ''),
        'source' => 'booking',
        'tier' => $service,
        'stage' => $stage,
        'score' => 90,
        'needs_attention' => $status === 'booked' ? 1 : 0,
        'attention_reason' => $reason,
        'last_event' => $reason,
        'estimated_value' => tfe_meetingpipeline_value($site, $service, 'booking'),
    ]);
    $mpLeadId = tfe_meetingpipeline_lead_id($site, $email);
    tfe_meetingpipeline_event($mpLeadId, $site, 'booking_' . $status, 'booking', $reason, $booking);
    if ($status === 'booked' && !empty($booking['slot'])) {
        tfe_meetingpipeline_calendar($site, $mpLeadId, $service ?: 'Prepared call', (string)$booking['slot'], $booking);
        tfe_meetingpipeline_task($site, 'Prepare for call: ' . (($booking['business'] ?? '') ?: $email), $reason, 'high', '+1 day');
    }
}

function tfe_meetingpipeline_upsert_lead(string $site, array $row): void {
    $stmt = db()->prepare(
        'INSERT INTO mp_leads
         (business_code, email, name, company, phone, source, tier, stage, estimated_value, score, needs_attention, attention_reason, last_event, last_event_at)
         VALUES
         (:business, :email, :name, :company, :phone, :source, :tier, :stage, :value, :score, :needs, :reason, :event, NOW())
         ON DUPLICATE KEY UPDATE
           name = IF(VALUES(name) <> "", VALUES(name), name),
           company = IF(VALUES(company) <> "", VALUES(company), company),
           phone = IF(VALUES(phone) <> "", VALUES(phone), phone),
           source = VALUES(source),
           tier = IF(VALUES(tier) <> "", VALUES(tier), tier),
           stage = VALUES(stage),
           estimated_value = GREATEST(estimated_value, VALUES(estimated_value)),
           score = GREATEST(score, VALUES(score)),
           needs_attention = GREATEST(needs_attention, VALUES(needs_attention)),
           attention_reason = IF(VALUES(attention_reason) <> "", VALUES(attention_reason), attention_reason),
           last_event = VALUES(last_event),
           last_event_at = NOW()'
    );
    $stmt->execute([
        ':business' => $site,
        ':email' => (string)$row['email'],
        ':name' => (string)($row['name'] ?? ''),
        ':company' => (string)($row['company'] ?? ''),
        ':phone' => (string)($row['phone'] ?? ''),
        ':source' => (string)($row['source'] ?? ''),
        ':tier' => (string)($row['tier'] ?? ''),
        ':stage' => (string)($row['stage'] ?? 'enquiry'),
        ':value' => (float)($row['estimated_value'] ?? 0),
        ':score' => (int)($row['score'] ?? 0),
        ':needs' => (int)($row['needs_attention'] ?? 0),
        ':reason' => (string)($row['attention_reason'] ?? ''),
        ':event' => (string)($row['last_event'] ?? ''),
    ]);
}

function tfe_meetingpipeline_lead_id(string $site, string $email): ?int {
    $stmt = db()->prepare('SELECT id FROM mp_leads WHERE business_code = :site AND email = :email LIMIT 1');
    $stmt->execute([':site' => $site, ':email' => $email]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function tfe_meetingpipeline_event(?int $leadId, string $site, string $eventType, string $source, string $summary, array $payload): void {
    $stmt = db()->prepare(
        'INSERT INTO mp_lead_events (lead_id, business_code, event_type, source, summary, payload)
         VALUES (:lead_id, :site, :event_type, :source, :summary, :payload)'
    );
    $stmt->execute([
        ':lead_id' => $leadId,
        ':site' => $site,
        ':event_type' => $eventType,
        ':source' => $source,
        ':summary' => substr($summary, 0, 255),
        ':payload' => json_encode($payload, JSON_UNESCAPED_SLASHES),
    ]);
}

function tfe_meetingpipeline_task(string $site, string $title, string $detail, string $priority, string $dueModifier): void {
    $exists = db()->prepare('SELECT id FROM mp_tasks WHERE business_code = :site AND title = :title AND status IN ("open","waiting") LIMIT 1');
    $exists->execute([':site' => $site, ':title' => substr($title, 0, 190)]);
    if ($exists->fetchColumn()) return;
    $dueAt = (new DateTimeImmutable('now'))->modify($dueModifier)->format('Y-m-d H:i:s');
    $stmt = db()->prepare(
        'INSERT INTO mp_tasks (business_code, title, detail, priority, due_at, source)
         VALUES (:site, :title, :detail, :priority, :due_at, "site_automation")'
    );
    $stmt->execute([
        ':site' => $site,
        ':title' => substr($title, 0, 190),
        ':detail' => $detail,
        ':priority' => $priority,
        ':due_at' => $dueAt,
    ]);
}

function tfe_meetingpipeline_calendar(string $site, ?int $leadId, string $title, string $slot, array $booking): void {
    $start = new DateTimeImmutable($slot);
    $end = $start->modify('+15 minutes');
    $stmt = db()->prepare(
        'INSERT INTO mp_calendar_events (business_code, lead_id, title, detail, starts_at, ends_at, location)
         VALUES (:site, :lead_id, :title, :detail, :starts_at, :ends_at, "Prepared call")'
    );
    $stmt->execute([
        ':site' => $site,
        ':lead_id' => $leadId,
        ':title' => substr($title, 0, 190),
        ':detail' => 'Booking reference: ' . (string)($booking['id'] ?? ''),
        ':starts_at' => $start->format('Y-m-d H:i:s'),
        ':ends_at' => $end->format('Y-m-d H:i:s'),
    ]);
}

function tfe_meetingpipeline_value(string $site, string $tier, string $source): float {
    $haystack = strtolower($tier . ' ' . $source);
    if ($site === 'epr') {
        if (str_contains($haystack, 'forecast')) return 1250;
        if (str_contains($haystack, 'discovery')) return 1500;
        if (str_contains($haystack, 'ledger')) return 295;
        return 295;
    }
    if ($site === 'myprofit') {
        if (str_contains($haystack, 'diagnostic') || str_contains($haystack, 'menu')) return 395;
        return 95;
    }
    return 0;
}

function tfe_summary(array $lead): string {
    $parts = [];
    foreach (['company', 'name', 'email', 'phone'] as $key) {
        if (!empty($lead[$key])) $parts[] = $lead[$key];
    }
    return implode(' | ', $parts);
}

function tfe_alert_owner(string $subject, string $body): void {
    notify('[NEEDS OWNER] ' . $subject, $body);
}

function tfe_system_failure(string $component, string $message, string $detail): void {
    try {
        $stmt = db()->prepare('INSERT INTO tfe_system_events (severity, component, message, detail) VALUES ("failure", :component, :message, :detail)');
        $stmt->execute([':component' => $component, ':message' => $message, ':detail' => $detail]);
    } catch (Throwable $ignored) {
        // Keep public forms working even if the automation tables are missing.
    }
    notify('[SYSTEM FAILURE] ' . $message, "Component: {$component}\n\n{$detail}");
}
