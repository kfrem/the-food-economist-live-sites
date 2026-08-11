<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/automation.php';
date_default_timezone_set('Europe/London');

const BOOKING_SITE = 'MyProfit desk';
const BOOKING_SERVICE_DEFAULT = 'MyProfit prepared call';
const OWNER_WHATSAPP = '447939823988';
const SLOT_DAYS_AHEAD = 21;
const SLOT_LENGTH_MINUTES = 15;
const SLOT_BUFFER_MINUTES = 15;
const MAX_BOOKINGS_PER_DAY = 4;
const WORKING_DAYS = [1, 2, 3, 4, 5];
const TIME_WINDOWS = [
    ['09:30', '11:30'],
    ['14:00', '16:00'],
];
const BLOCKED_DATES = [];

booking_dispatch();

function booking_dispatch(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET' && ($_GET['action'] ?? '') === 'slots') {
        booking_json(['ok' => true, 'slots' => booking_available_slots()]);
    }
    if ($method === 'GET' && ($_GET['action'] ?? '') === 'cancel') {
        booking_cancel_page((string)($_GET['token'] ?? ''), false);
        return;
    }
    if ($method === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
        booking_cancel_page((string)($_POST['token'] ?? ''), true);
        return;
    }
    if ($method === 'POST') {
        booking_create();
        return;
    }
    http_response_code(405);
    booking_json(['ok' => false, 'error' => 'Unsupported booking request.']);
}

function booking_create(): void {
    $d = read_json_or_fail();
    $email = clean((string)($d['email'] ?? ''), 190);
    if (!valid_email($email)) {
        http_response_code(422);
        booking_json(['ok' => false, 'error' => 'Enter a valid email address.']);
    }
    $slot = clean((string)($d['slot'] ?? ''), 40);
    $slotTime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $slot);
    if (!$slotTime || !booking_slot_allowed($slotTime)) {
        http_response_code(422);
        booking_json(['ok' => false, 'error' => 'Choose an available slot.']);
    }

    $booking = [
        'id' => 'bk_' . bin2hex(random_bytes(5)),
        'token' => bin2hex(random_bytes(16)),
        'site' => BOOKING_SITE,
        'service' => clean((string)($d['service'] ?? BOOKING_SERVICE_DEFAULT), 120),
        'name' => clean((string)($d['name'] ?? ''), 120),
        'business' => clean((string)($d['business'] ?? ''), 160),
        'email' => $email,
        'phone' => clean((string)($d['phone'] ?? ''), 40),
        'situation' => clean((string)($d['situation'] ?? ''), 1400),
        'slot' => $slotTime->format(DateTimeInterface::ATOM),
        'created_at' => gmdate(DateTimeInterface::ATOM),
        'status' => 'booked',
    ];

    $stored = booking_with_store(function (array $store) use ($booking, $slotTime) {
        foreach ($store['bookings'] as $existing) {
            if (($existing['status'] ?? '') === 'booked' && ($existing['slot'] ?? '') === $slotTime->format(DateTimeInterface::ATOM)) {
                http_response_code(409);
                booking_json(['ok' => false, 'error' => 'That slot has just been taken. Choose another.']);
            }
        }
        if (booking_count_for_day($store['bookings'], $slotTime->format('Y-m-d')) >= MAX_BOOKINGS_PER_DAY) {
            http_response_code(409);
            booking_json(['ok' => false, 'error' => 'That day is now full. Choose another day.']);
        }
        $store['bookings'][] = $booking;
        return $store;
    });

    booking_send_notifications($booking);
    tfe_automation_record_booking('myprofit', $booking, 'booked');
    booking_json([
        'ok' => true,
        'reference' => $booking['id'],
        'slotLabel' => booking_slot_label($slotTime),
        'cancelUrl' => booking_cancel_url($booking['token']),
        'whatsappUrl' => booking_whatsapp_url($booking),
        'stored' => $stored !== null,
    ]);
}

function booking_available_slots(): array {
    $now = new DateTimeImmutable('now');
    $slots = [];
    $store = booking_read_store();
    for ($i = 0; $i <= SLOT_DAYS_AHEAD; $i++) {
        $day = $now->modify('+' . $i . ' days');
        if (!in_array((int)$day->format('N'), WORKING_DAYS, true)) continue;
        if (in_array($day->format('Y-m-d'), BLOCKED_DATES, true)) continue;
        if (booking_count_for_day($store['bookings'], $day->format('Y-m-d')) >= MAX_BOOKINGS_PER_DAY) continue;
        foreach (TIME_WINDOWS as $window) {
            $start = new DateTimeImmutable($day->format('Y-m-d') . 'T' . $window[0] . ':00');
            $end = new DateTimeImmutable($day->format('Y-m-d') . 'T' . $window[1] . ':00');
            for ($slot = $start; $slot < $end; $slot = $slot->modify('+' . (SLOT_LENGTH_MINUTES + SLOT_BUFFER_MINUTES) . ' minutes')) {
                if ($slot <= $now->modify('+2 hours')) continue;
                if (!booking_slot_allowed($slot)) continue;
                if (booking_slot_taken($store['bookings'], $slot->format(DateTimeInterface::ATOM))) continue;
                $slots[] = [
                    'value' => $slot->format(DateTimeInterface::ATOM),
                    'label' => booking_slot_label($slot),
                ];
            }
        }
    }
    return array_slice($slots, 0, 40);
}

function booking_slot_allowed(DateTimeImmutable $slot): bool {
    if ($slot <= (new DateTimeImmutable('now'))->modify('+2 hours')) return false;
    if (!in_array((int)$slot->format('N'), WORKING_DAYS, true)) return false;
    if (in_array($slot->format('Y-m-d'), BLOCKED_DATES, true)) return false;
    foreach (TIME_WINDOWS as $window) {
        $start = new DateTimeImmutable($slot->format('Y-m-d') . 'T' . $window[0] . ':00');
        $end = new DateTimeImmutable($slot->format('Y-m-d') . 'T' . $window[1] . ':00');
        if ($slot >= $start && $slot < $end) return true;
    }
    return false;
}

function booking_send_notifications(array $b): void {
    $slot = new DateTimeImmutable($b['slot']);
    $subject = BOOKING_SITE . ' booking: ' . ($b['business'] ?: $b['name'] ?: $b['email']);
    $body = "New prepared-call booking\n"
        . "Reference: {$b['id']}\nService: {$b['service']}\nSlot: " . booking_slot_label($slot) . "\n"
        . "Name: {$b['name']}\nBusiness: {$b['business']}\nEmail: {$b['email']}\nPhone: {$b['phone']}\n"
        . "Situation:\n{$b['situation']}\n\nCancel link: " . booking_cancel_url($b['token']) . "\n";
    notify($subject, $body);
    booking_mail_with_ics($b, false);
}

function booking_mail_with_ics(array $b, bool $cancelled): void {
    $slot = new DateTimeImmutable($b['slot']);
    $end = $slot->modify('+' . SLOT_LENGTH_MINUTES . ' minutes');
    $uid = $b['id'] . '@thefoodeconomist.co.uk';
    $status = $cancelled ? 'CANCELLED' : 'CONFIRMED';
    $summary = ($cancelled ? 'Cancelled: ' : '') . $b['service'] . ' - ' . BOOKING_SITE;
    $ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//The Food Economist//Booking//EN\r\nMETHOD:REQUEST\r\nBEGIN:VEVENT\r\n"
        . "UID:{$uid}\r\nDTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n"
        . "DTSTART:" . $slot->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\n"
        . "DTEND:" . $end->setTimezone(new DateTimeZone('UTC'))->format('Ymd\THis\Z') . "\r\n"
        . "SUMMARY:" . booking_ics_escape($summary) . "\r\nSTATUS:{$status}\r\n"
        . "DESCRIPTION:" . booking_ics_escape("Prepared written-first call. Reply to this email if you need to move it. Cancel: " . booking_cancel_url($b['token'])) . "\r\n"
        . "END:VEVENT\r\nEND:VCALENDAR\r\n";
    $boundary = 'tfebooking_' . bin2hex(random_bytes(6));
    $headers = "From: " . FROM_EMAIL . "\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"{$boundary}\"";
    $message = "--{$boundary}\r\nContent-Type: text/plain; charset=utf-8\r\n\r\n"
        . "Booked. I will review what you have sent and prepare before we speak.\n\n"
        . "Reference: {$b['id']}\nService: {$b['service']}\nTime: " . booking_slot_label($slot) . "\n"
        . "Cancel or reschedule request: " . booking_cancel_url($b['token']) . "\n\n"
        . "If you need to move it, reply to this confirmation.\n\r\n"
        . "--{$boundary}\r\nContent-Type: text/calendar; method=REQUEST; name=\"booking.ics\"\r\nContent-Disposition: attachment; filename=\"booking.ics\"\r\n\r\n{$ics}\r\n--{$boundary}--";
    @mail($b['email'], ($cancelled ? 'Cancelled: ' : 'Confirmed: ') . $b['service'], $message, $headers);
}

function booking_cancel_page(string $token, bool $confirmed): void {
    if (!$confirmed) {
        $booking = booking_find_by_token($token);
        header('Content-Type: text/html; charset=utf-8');
        if (!$booking || ($booking['status'] ?? '') !== 'booked') {
            echo '<!doctype html><title>Booking not found</title><p>This booking was not found or has already been cancelled.</p>';
            return;
        }
        echo '<!doctype html><title>Cancel booking</title><meta name="viewport" content="width=device-width, initial-scale=1"><body style="font-family:Arial,sans-serif;max-width:640px;margin:40px auto;padding:0 18px;line-height:1.5"><h1>Cancel this booking?</h1><p>This will release the slot and notify The Food Economist.</p><p><strong>Reference:</strong> ' . htmlspecialchars($booking['id'], ENT_QUOTES, 'UTF-8') . '<br><strong>Time:</strong> ' . htmlspecialchars(booking_slot_label(new DateTimeImmutable($booking['slot'])), ENT_QUOTES, 'UTF-8') . '</p><form method="post"><input type="hidden" name="action" value="cancel"><input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"><button type="submit" style="background:#11182f;color:#fff;border:0;border-radius:6px;padding:10px 14px">Confirm cancellation</button></form></body>';
        return;
    }

    $found = null;
    booking_with_store(function (array $store) use ($token, &$found) {
        foreach ($store['bookings'] as &$booking) {
            if (($booking['token'] ?? '') === $token && ($booking['status'] ?? '') === 'booked') {
                $booking['status'] = 'cancelled';
                $booking['cancelled_at'] = gmdate(DateTimeInterface::ATOM);
                $found = $booking;
                break;
            }
        }
        unset($booking);
        return $store;
    });
    header('Content-Type: text/html; charset=utf-8');
    if ($found) {
        notify(BOOKING_SITE . ' booking cancelled: ' . $found['id'], "Booking cancelled\nReference: {$found['id']}\nEmail: {$found['email']}\nSlot: {$found['slot']}\n");
        booking_mail_with_ics($found, true);
        tfe_automation_record_booking('myprofit', $found, 'cancelled');
        echo '<!doctype html><title>Booking cancelled</title><p>Booking cancelled. The slot has been released. Reply to your email if you need a new time.</p>';
    } else {
        echo '<!doctype html><title>Booking not found</title><p>This booking was not found or has already been cancelled.</p>';
    }
}

function booking_find_by_token(string $token): ?array {
    foreach (booking_read_store()['bookings'] as $booking) {
        if (($booking['token'] ?? '') === $token) return $booking;
    }
    return null;
}

function booking_read_store(): array {
    $path = booking_store_path();
    if (!is_file($path)) return ['bookings' => []];
    $data = json_decode((string)file_get_contents($path), true);
    return is_array($data) && isset($data['bookings']) && is_array($data['bookings']) ? $data : ['bookings' => []];
}

function booking_with_store(callable $fn): ?array {
    $path = booking_store_path();
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    $fh = fopen($path, 'c+');
    if (!$fh) return null;
    flock($fh, LOCK_EX);
    $raw = stream_get_contents($fh);
    $store = json_decode($raw ?: '{"bookings":[]}', true);
    if (!is_array($store) || !isset($store['bookings'])) $store = ['bookings' => []];
    $store = $fn($store);
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($store, JSON_PRETTY_PRINT));
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    return $store;
}

function booking_store_path(): string {
    return __DIR__ . '/booking-data/' . preg_replace('/[^a-z0-9]+/', '-', strtolower(BOOKING_SITE)) . '.json';
}

function booking_count_for_day(array $bookings, string $day): int {
    $count = 0;
    foreach ($bookings as $booking) {
        if (($booking['status'] ?? '') === 'booked' && str_starts_with((string)($booking['slot'] ?? ''), $day)) $count++;
    }
    return $count;
}

function booking_slot_taken(array $bookings, string $slot): bool {
    foreach ($bookings as $booking) {
        if (($booking['status'] ?? '') === 'booked' && ($booking['slot'] ?? '') === $slot) return true;
    }
    return false;
}

function booking_slot_label(DateTimeImmutable $slot): string {
    return $slot->format('D j M Y, H:i') . ' UK time';
}

function booking_cancel_url(string $token): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'thefoodeconomist.co.uk';
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/api/booking.php'), '/');
    return $scheme . '://' . $host . $base . '/booking.php?action=cancel&token=' . rawurlencode($token);
}

function booking_whatsapp_url(array $b): string {
    $slot = booking_slot_label(new DateTimeImmutable($b['slot']));
    $msg = "Prepared-call booking\nRef: {$b['id']}\nSite: " . BOOKING_SITE . "\nService: {$b['service']}\nTime: {$slot}\nBusiness: {$b['business']}\nName: {$b['name']}\nEmail: {$b['email']}";
    return 'https://wa.me/' . OWNER_WHATSAPP . '?text=' . rawurlencode($msg);
}

function booking_ics_escape(string $v): string {
    return str_replace(["\\", "\n", "\r", ",", ";"], ["\\\\", "\\n", '', "\\,", "\\;"], $v);
}

function booking_json(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}
