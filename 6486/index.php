<?php
// index.php
// MediToken Homepage
$page_title = "MediToken - Doctor Appointment Booking & Digital Token Generation System";
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-container">
        <div class="hero-pill">
            <span>●</span> Intelligent Healthcare Queue & Token System
        </div>
        <h1 class="hero-title">Your Health, <span>Our Priority.</span></h1>
        <p class="hero-desc">
            Book your doctor appointment, receive your digital token and track your queue without waiting in long lines. Experience seamless healthcare with live token updates.
        </p>
        <div class="hero-cta">
            <a href="<?= $base_url ?>/patient/book_appointment.php" class="btn btn-lg btn-primary">Book Appointment</a>
            <a href="<?= $base_url ?>/doctors.php" class="btn btn-lg btn-secondary">Find a Doctor</a>
            <a href="<?= $base_url ?>/login.php" class="btn btn-lg btn-outline">Login</a>
        </div>
    </div>
</section>

<!-- How MediToken Works -->
<section class="section">
    <div class="section-container">
        <div class="section-header">
            <div class="section-subtitle">Streamlined Process</div>
            <h2 class="section-title">How MediToken Works</h2>
            <p class="section-desc">Experience zero clinic waiting anxiety with our 4-step digital token flow.</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">1</div>
                <h3 class="card-title">Select Doctor & Time</h3>
                <p class="card-text">Browse certified specialists, review available consultation hours, and select an open time slot.</p>
            </div>
            <div class="card">
                <div class="card-icon">2</div>
                <h3 class="card-title">Receive Digital Token</h3>
                <p class="card-text">An automated, unique token number is assigned specifically to your doctor and consultation slot.</p>
            </div>
            <div class="card">
                <div class="card-icon">3</div>
                <h3 class="card-title">Track Real-Time Queue</h3>
                <p class="card-text">Monitor the currently serving token, next in line, and patients ahead directly from your phone.</p>
            </div>
            <div class="card">
                <div class="card-icon">4</div>
                <h3 class="card-title">Consult Without Crowds</h3>
                <p class="card-text">Arrive right when your token is called and walk straight into the consultation room.</p>
            </div>
        </div>
    </div>
</section>

<!-- Live Token Tracking Preview Section -->
<section class="section" style="background-color: #ffffff; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="section-container">
        <div class="section-header">
            <div class="section-subtitle">Real-Time Transparency</div>
            <h2 class="section-title">Live Token Tracking</h2>
            <p class="section-desc">Real-time status updates powered directly by MySQL consultation records.</p>
        </div>

        <div class="queue-board-card" style="max-width: 900px; margin: 0 auto;">
            <div class="queue-board-header">
                <div>
                    <h3 style="font-size: 1.25rem;">Live Clinic Queue Status</h3>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Continuous status synchronization with the doctor's consultation desk</p>
                </div>
                <div>
                    <span class="status-badge badge-serving">
                        <span class="status-pulse"></span> System Active
                    </span>
                </div>
            </div>

            <div class="token-highlight-grid">
                <div class="token-box active-serving">
                    <div class="token-box-label">Currently Serving</div>
                    <div class="token-box-number">LIVE</div>
                    <div class="token-box-sub">Active in Consultation</div>
                </div>
                <div class="token-box">
                    <div class="token-box-label">Estimated Wait</div>
                    <div class="token-box-number" style="font-size: 2rem;">Dynamic</div>
                    <div class="token-box-sub">Calculated per patient</div>
                </div>
                <div class="token-box">
                    <div class="token-box-label">Queue Position</div>
                    <div class="token-box-number" style="font-size: 2rem;">Real-time</div>
                    <div class="token-box-sub">Zero guesswork</div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="<?= $base_url ?>/patient/queue.php" class="btn btn-primary">Track Your Active Token</a>
            </div>
        </div>
    </div>
</section>

<!-- Healthcare Services -->
<section class="section">
    <div class="section-container">
        <div class="section-header">
            <div class="section-subtitle">Clinical Excellence</div>
            <h2 class="section-title">Our Services</h2>
            <p class="section-desc">Connecting patients with trusted doctors across vital medical specializations.</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-icon">01</div>
                <h3 class="card-title">General Medicine</h3>
                <p class="card-text">Comprehensive primary care, routine checkups, illness diagnosis, and preventive healthcare guidance.</p>
            </div>
            <div class="card">
                <div class="card-icon">02</div>
                <h3 class="card-title">Cardiology</h3>
                <p class="card-text">Specialized cardiovascular care, hypertension monitoring, heart health checks, and dietary advice.</p>
            </div>
            <div class="card">
                <div class="card-icon">03</div>
                <h3 class="card-title">Pediatrics</h3>
                <p class="card-text">Dedicated newborn, child, and adolescent healthcare, immunizations, and developmental tracking.</p>
            </div>
            <div class="card">
                <div class="card-icon">04</div>
                <h3 class="card-title">Orthopedics</h3>
                <p class="card-text">Joint and bone health, sports injuries, fracture management, and muscular rehabilitation care.</p>
            </div>
        </div>
    </div>
</section>

<!-- Patient Benefits -->
<section class="section" style="background-color: #ffffff; border-top: 1px solid var(--border-color);">
    <div class="section-container">
        <div class="section-header">
            <div class="section-subtitle">Why Choose MediToken</div>
            <h2 class="section-title">Patient Benefits</h2>
            <p class="section-desc">Designed to respect your time and health.</p>
        </div>

        <div class="cards-grid">
            <div class="card">
                <h3 class="card-title" style="color: var(--primary);">No Crowded Waiting Rooms</h3>
                <p class="card-text">Reduce the risk of cross-infections by waiting comfortably at home or nearby until your turn approaches.</p>
            </div>
            <div class="card">
                <h3 class="card-title" style="color: var(--primary);">100% Digital Tokens</h3>
                <p class="card-text">Eliminate physical token paper slips that get misplaced. All token details remain securely in your account.</p>
            </div>
            <div class="card">
                <h3 class="card-title" style="color: var(--primary);">Verified Relational Security</h3>
                <p class="card-text">Encrypted patient passwords and strict database isolation prevent unauthorized data exposure.</p>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
