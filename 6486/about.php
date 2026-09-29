<?php
// about.php
$page_title = "About MediToken - Healthcare Innovation";
include __DIR__ . '/includes/header.php';
?>

<div class="section">
    <div class="section-container" style="max-width: 900px;">
        <div class="section-header">
            <div class="section-subtitle">Our Vision & Mission</div>
            <h2 class="section-title">About MediToken</h2>
            <p class="section-desc">Transforming traditional clinic waiting rooms into an efficient, transparent digital healthcare workflow.</p>
        </div>

        <div class="card" style="margin-bottom: 2rem; line-height: 1.8;">
            <h3 style="margin-bottom: 1rem; color: var(--primary);">Solving Healthcare Waiting Challenges</h3>
            <p style="margin-bottom: 1rem;">
                Traditional paper-based token registries and uncoordinated clinic queues result in crowded waiting areas, prolonged patient anxiety, and increased exposure to clinic pathogens. MediToken was conceived to bridge patients, doctors, and clinic administrations through an integrated, live digital appointment and token workflow.
            </p>
            <p>
                Every token generated in MediToken is directly mapped to a doctor and consultation date in our relational MySQL database. When the doctor calls the next token, status updates cascade instantaneously across patient displays, enabling timely visits without guesswork.
            </p>
        </div>

        <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">
            <div class="card">
                <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Database Driven</h4>
                <p class="card-text">Every appointment, doctor schedule, and token state is committed reliably to relational storage.</p>
            </div>
            <div class="card">
                <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Zero Guesswork</h4>
                <p class="card-text">Real-time queue tracking displays exact serving tokens and calculated patient waiting intervals.</p>
            </div>
            <div class="card">
                <h4 style="color: var(--primary); margin-bottom: 0.5rem;">Three Portals</h4>
                <p class="card-text">Tailored dashboards for Patients, Doctors, and Administrators with strict role-based access control.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
