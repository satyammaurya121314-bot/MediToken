<?php
// services.php
$page_title = "Clinical Services - MediToken";
include __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div class="section-container">
        <div class="section-header">
            <div class="section-subtitle">Comprehensive Healthcare</div>
            <h2 class="section-title">Our Clinical Services</h2>
            <p class="section-desc">Delivering compassionate and digitally coordinated medical care for all patients.</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">01</div>
                <h3 class="card-title">Outpatient Consultations</h3>
                <p class="card-text">Specialist doctor appointments with reserved digital queue slots to minimize clinic exposure and wait times.</p>
            </div>
            <div class="card">
                <div class="card-icon">02</div>
                <h3 class="card-title">Digital Token Queuing</h3>
                <p class="card-text">Automated per-doctor sequential token issuance with dynamic waiting time estimates and real-time live calling.</p>
            </div>
            <div class="card">
                <div class="card-icon">03</div>
                <h3 class="card-title">Appointment History Tracking</h3>
                <p class="card-text">Complete patient consultation history, token logs, doctor recommendations, and attendance records.</p>
            </div>
            <div class="card">
                <div class="card-icon">04</div>
                <h3 class="card-title">Live Queue Status</h3>
                <p class="card-text">On-screen live updates letting you track exactly how many patients are ahead and when to walk in.</p>
            </div>
            <div class="card">
                <div class="card-icon">05</div>
                <h3 class="card-title">Doctor Consultation Portal</h3>
                <p class="card-text">Specialized control room allowing consulting physicians to call next patients, skip, or mark consultations completed.</p>
            </div>
            <div class="card">
                <div class="card-icon">06</div>
                <h3 class="card-title">Admin Queue Governance</h3>
                <p class="card-text">Centralized administration for doctor schedules, daily patient loads, and comprehensive clinic performance reports.</p>
            </div>
        </div>

        <div style="text-align: center; margin-top: 3.5rem;">
            <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-lg btn-primary">Book an Appointment Now</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
