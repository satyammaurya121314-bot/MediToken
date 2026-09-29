<?php
// doctor/queue.php
// Doctor Live Queue Management: Call Next, Mark Completed, Skip, View Patient

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('doctor');

$pdo = get_db_connection();
$doctor_id = $_SESSION['user_id'];
$filter_date = trim($_GET['date'] ?? date('Y-m-d'));

// Fetch doctor details
$doc_stmt = $pdo->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$doc_stmt->execute([$doctor_id]);
$doctor = $doc_stmt->fetch();

// ----------------------------------------------------
// Action Handler
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $apt_id = (int)($_POST['appointment_id'] ?? 0);

    if ($action === 'call_next') {
        // 1. If someone is currently Serving, mark them Completed
        $curr_stmt = $pdo->prepare("
            SELECT appointment_id, patient_id, token_number 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving'
        ");
        $curr_stmt->execute([$doctor_id, $filter_date]);
        $curr_serving = $curr_stmt->fetch();

        if ($curr_serving) {
            $upd_comp = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
            $upd_comp->execute([$curr_serving['appointment_id']]);
            add_notification($pdo, $curr_serving['patient_id'], "Your consultation with Dr. {$doctor['doctor_name']} (Token {$curr_serving['token_number']}) has been completed.", 'completed', $doctor_id, $curr_serving['appointment_id']);
        }

        // 2. Find earliest Waiting appointment
        $next_stmt = $pdo->prepare("
            SELECT appointment_id, patient_id, token_number 
            FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting'
            ORDER BY appointment_id ASC 
            LIMIT 1
        ");
        $next_stmt->execute([$doctor_id, $filter_date]);
        $next_patient = $next_stmt->fetch();

        if ($next_patient) {
            $upd_serv = $pdo->prepare("UPDATE appointments SET status = 'Serving' WHERE appointment_id = ?");
            $upd_serv->execute([$next_patient['appointment_id']]);

            // Notify patient their token is now being served
            $notif = "Your token {$next_patient['token_number']} is now being served! Please enter Dr. {$doctor['doctor_name']}'s room.";
            add_notification($pdo, $next_patient['patient_id'], $notif, 'token_called', $doctor_id, $next_patient['appointment_id']);

            // Notify next in line that they are approaching
            $approaching_stmt = $pdo->prepare("
                SELECT patient_id, token_number 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date = ? AND status = 'Waiting' AND appointment_id > ?
                ORDER BY appointment_id ASC 
                LIMIT 1
            ");
            $approaching_stmt->execute([$doctor_id, $filter_date, $next_patient['appointment_id']]);
            $approaching = $approaching_stmt->fetch();
            if ($approaching) {
                add_notification($pdo, $approaching['patient_id'], "Your token {$approaching['token_number']} is approaching next. Please remain near the consultation room.", 'token_approaching', $doctor_id);
            }

            set_flash('success', "Called Token {$next_patient['token_number']} into consultation.");
        } else {
            set_flash('info', 'No more waiting patients in queue for this date.');
        }

    } elseif ($action === 'call_specific' && $apt_id > 0) {
        // Mark previous serving as Completed
        $upd_prev = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE doctor_id = ? AND appointment_date = ? AND status = 'Serving'");
        $upd_prev->execute([$doctor_id, $filter_date]);

        // Mark target appointment as Serving
        $target_stmt = $pdo->prepare("SELECT patient_id, token_number FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
        $target_stmt->execute([$apt_id, $doctor_id]);
        $target = $target_stmt->fetch();

        if ($target) {
            $upd = $pdo->prepare("UPDATE appointments SET status = 'Serving' WHERE appointment_id = ?");
            $upd->execute([$apt_id]);
            add_notification($pdo, $target['patient_id'], "Your token {$target['token_number']} is now being served!", 'token_called', $doctor_id, $apt_id);
            set_flash('success', "Token {$target['token_number']} is now serving.");
        }

    } elseif ($action === 'complete' && $apt_id > 0) {
        $stmt = $pdo->prepare("SELECT patient_id, token_number FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->execute([$apt_id, $doctor_id]);
        $p = $stmt->fetch();

        if ($p) {
            $upd = $pdo->prepare("UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?");
            $upd->execute([$apt_id]);
            add_notification($pdo, $p['patient_id'], "Your consultation (Token {$p['token_number']}) has been marked as completed.", 'completed', $doctor_id, $apt_id);
            set_flash('success', "Token {$p['token_number']} marked as completed.");
        }

    } elseif ($action === 'skip' && $apt_id > 0) {
        $stmt = $pdo->prepare("SELECT patient_id, token_number FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
        $stmt->execute([$apt_id, $doctor_id]);
        $p = $stmt->fetch();

        if ($p) {
            $upd = $pdo->prepare("UPDATE appointments SET status = 'Skipped' WHERE appointment_id = ?");
            $upd->execute([$apt_id]);
            add_notification($pdo, $p['patient_id'], "Your token {$p['token_number']} was skipped. Please consult clinic staff.", 'skipped', $doctor_id, $apt_id);
            set_flash('warning', "Token {$p['token_number']} skipped.");
        }
    }

    header("Location: " . get_base_url() . "/doctor/queue.php?date=" . urlencode($filter_date));
    exit;
}

// ----------------------------------------------------
// Fetch Queue Data for Filter Date
// ----------------------------------------------------
// 1. Serving Appointment
$serv_stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, p.mobile_no, p.email AS patient_email
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status = 'Serving'
    LIMIT 1
");
$serv_stmt->execute([$doctor_id, $filter_date]);
$currently_serving = $serv_stmt->fetch();

// 2. Next In Line Appointment
$next_stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, p.mobile_no, p.email AS patient_email
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status = 'Waiting'
    ORDER BY a.appointment_id ASC
    LIMIT 1
");
$next_stmt->execute([$doctor_id, $filter_date]);
$next_in_line = $next_stmt->fetch();

// 3. Full Appointment List for Filter Date
$list_stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, p.mobile_no, p.email AS patient_email, p.created_at AS patient_registered_date
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ? AND a.appointment_date = ?
    ORDER BY 
        CASE 
            WHEN a.status = 'Serving' THEN 1
            WHEN a.status = 'Waiting' THEN 2
            WHEN a.status = 'Skipped' THEN 3
            WHEN a.status = 'Completed' THEN 4
            ELSE 5
        END,
        a.appointment_id ASC
");
$list_stmt->execute([$doctor_id, $filter_date]);
$all_queue = $list_stmt->fetchAll();

// Count waiting
$wait_count = 0;
foreach ($all_queue as $q) {
    if ($q['status'] === 'Waiting') $wait_count++;
}

$page_title = "Doctor Live Queue - MediToken";
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/doctor_nav.php'; ?>

    <main class="dashboard-main">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.75rem; margin-bottom: 0.25rem;">
                    Live Patient Queue Control Desk
                    <span style="font-size: 0.95rem; color: var(--primary); font-weight: 700; background: var(--primary-light); padding: 0.25rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--primary-border); vertical-align: middle; margin-left: 0.5rem;">Doctor ID: #<?= (int)$doctor_id ?></span>
                </h1>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    Dr. <?= htmlspecialchars($doctor['doctor_name']) ?> | <?= htmlspecialchars($doctor['specialization']) ?>
                </p>
            </div>

            <!-- Date Filter Form -->
            <form method="GET" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                <label for="date" style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted);">Queue Date:</label>
                <input type="date" id="date" name="date" class="form-control" style="width: auto; padding: 0.4rem 0.75rem;" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit();">
            </form>
        </div>

        <?php display_flash(); ?>

        <!-- Active Desk Calling Banner -->
        <div class="queue-board-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.25rem;">
                <div style="display: flex; gap: 2rem; align-items: center; flex-wrap: wrap;">
                    <div>
                        <div class="token-box-label" style="color: var(--primary);">CURRENTLY SERVING</div>
                        <div style="font-family: 'Space Grotesk', sans-serif; font-size: 2.75rem; font-weight: 800; color: var(--primary); line-height: 1;">
                            <?= $currently_serving ? htmlspecialchars($currently_serving['token_number']) : 'None' ?>
                        </div>
                        <div style="font-size: 0.9rem; font-weight: 600; margin-top: 0.25rem;">
                            <?= $currently_serving ? htmlspecialchars($currently_serving['patient_name']) : 'No patient in room' ?>
                        </div>
                    </div>

                    <div style="border-left: 2px solid var(--border-color); padding-left: 2rem;">
                        <div class="token-box-label">NEXT IN LINE</div>
                        <div style="font-family: 'Space Grotesk', sans-serif; font-size: 2.15rem; font-weight: 800; color: var(--dark); line-height: 1;">
                            <?= $next_in_line ? htmlspecialchars($next_in_line['token_number']) : 'None' ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                            <?= $next_in_line ? htmlspecialchars($next_in_line['patient_name']) : 'Queue clear' ?>
                        </div>
                    </div>

                    <div style="border-left: 2px solid var(--border-color); padding-left: 2rem;">
                        <div class="token-box-label">WAITING PATIENTS</div>
                        <div style="font-family: 'Space Grotesk', sans-serif; font-size: 2.15rem; font-weight: 800; color: #d97706; line-height: 1;">
                            <?= $wait_count ?>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">In line for today</div>
                    </div>
                </div>

                <!-- Call Next Action Button -->
                <div>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="call_next">
                        <button type="submit" class="btn btn-action-call btn-lg" <?= ($wait_count === 0 && !$currently_serving) ? 'disabled style="opacity:0.6; cursor:not-allowed;"' : '' ?>>
                            CALL NEXT TOKEN
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Appointment Table -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <h2 style="font-size: 1.25rem;">Queue Appointments for <?= format_date($filter_date) ?></h2>
                <div style="font-size: 0.85rem; color: var(--text-muted);">
                    Showing <?= count($all_queue) ?> total appointment(s)
                </div>
            </div>

            <?php if (empty($all_queue)): ?>
                <div class="empty-state">
                    
                    <div class="empty-state-title">No appointments found for <?= format_date($filter_date) ?>.</div>
                    <p class="empty-state-desc">There are no patient appointments registered for this doctor on the selected date.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Patient Name</th>
                                <th>Slot Time</th>
                                <th>Mobile Number</th>
                                <th>Status</th>
                                <th style="text-align: right;">Desk Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_queue as $item): ?>
                                <tr style="<?= $item['status'] === 'Serving' ? 'background-color: #ecfdf5;' : '' ?>">
                                    <td>
                                        <span style="font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 800; color: var(--primary);">
                                            <?= htmlspecialchars($item['token_number']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['patient_name']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($item['appointment_time']) ?></td>
                                    <td><?= htmlspecialchars($item['mobile_no']) ?></td>
                                    <td><?= render_status_badge($item['status']) ?></td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <!-- View Patient Button -->
                                        <button type="button" class="btn btn-sm btn-secondary" onclick="viewPatientModal(<?= htmlspecialchars(json_encode([
                                            'name'   => $item['patient_name'],
                                            'mobile' => $item['mobile_no'],
                                            'email'  => $item['patient_email'],
                                            'token'  => $item['token_number'],
                                            'date'   => format_date($item['appointment_date']),
                                            'time'   => $item['appointment_time'],
                                            'status' => $item['status']
                                        ])) ?>)">
                                            View
                                        </button>

                                        <?php if ($item['status'] === 'Serving'): ?>
                                            <!-- Mark Completed -->
                                            <form method="POST" action="" style="display: inline-block; margin-left: 0.35rem;">
                                                <input type="hidden" name="action" value="complete">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$item['appointment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-action-complete">Mark Completed</button>
                                            </form>

                                        <?php elseif ($item['status'] === 'Waiting'): ?>
                                            <!-- Call Specific -->
                                            <form method="POST" action="" style="display: inline-block; margin-left: 0.35rem;">
                                                <input type="hidden" name="action" value="call_specific">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$item['appointment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">Call</button>
                                            </form>

                                            <!-- Skip -->
                                            <form method="POST" action="" style="display: inline-block; margin-left: 0.35rem;" onsubmit="return confirm('Skip this patient?');">
                                                <input type="hidden" name="action" value="skip">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$item['appointment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline">Skip</button>
                                            </form>

                                        <?php elseif ($item['status'] === 'Skipped'): ?>
                                            <!-- Recall Skipped -->
                                            <form method="POST" action="" style="display: inline-block; margin-left: 0.35rem;">
                                                <input type="hidden" name="action" value="call_specific">
                                                <input type="hidden" name="appointment_id" value="<?= (int)$item['appointment_id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline">Recall</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Patient Info Modal Dialog -->
<div id="patientModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; padding: 1.5rem;">
    <div class="card" style="max-width: 480px; width: 100%; box-shadow: var(--shadow-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <h3 style="font-size: 1.25rem;">Patient Consultation Information</h3>
            <button type="button" class="btn-close" onclick="closePatientModal()">×</button>
        </div>

        <div style="display: flex; flex-direction: column; gap: 0.85rem; font-size: 0.95rem;">
            <div>
                <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Patient Name:</span>
                <div id="mPatientName" style="font-weight: 700; font-size: 1.15rem;"></div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Mobile Number:</span>
                    <div id="mPatientMobile" style="font-weight: 600;"></div>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Email Address:</span>
                    <div id="mPatientEmail" style="font-weight: 600; word-break: break-all;"></div>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; background: var(--bg-main); padding: 0.75rem; border-radius: var(--radius-sm);">
                <div>
                    <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Token:</span>
                    <div id="mPatientToken" style="font-weight: 800; color: var(--primary); font-family: 'Space Grotesk', sans-serif; font-size: 1.35rem;"></div>
                </div>
                <div>
                    <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Slot Time:</span>
                    <div id="mPatientTime" style="font-weight: 700;"></div>
                </div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Appointment Date:</span>
                <div id="mPatientDate" style="font-weight: 600;"></div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase;">Current Status:</span>
                <div id="mPatientStatus" style="margin-top: 0.25rem;"></div>
            </div>
        </div>

        <div style="text-align: right; margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 0.75rem;">
            <button type="button" class="btn btn-secondary" onclick="closePatientModal()">Close</button>
        </div>
    </div>
</div>

<script>
function viewPatientModal(data) {
    document.getElementById('mPatientName').textContent = data.name || '-';
    document.getElementById('mPatientMobile').textContent = data.mobile || '-';
    document.getElementById('mPatientEmail').textContent = data.email || '-';
    document.getElementById('mPatientToken').textContent = data.token || '-';
    document.getElementById('mPatientDate').textContent = data.date || '-';
    document.getElementById('mPatientTime').textContent = data.time || '-';
    document.getElementById('mPatientStatus').innerHTML = `<span class="status-badge badge-${data.status.toLowerCase()}">${data.status}</span>`;
    
    const modal = document.getElementById('patientModal');
    modal.style.display = 'flex';
}

function closePatientModal() {
    document.getElementById('patientModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
