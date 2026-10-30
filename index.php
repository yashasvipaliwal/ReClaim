<?php
/**
 * index.php
 * ReClaim landing page — publicly accessible.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

$pageTitle = 'Home — Smart Lost & Found Platform';

// Fetch latest open reports for the homepage showcase
$latestLost = $pdo->query(
    "SELECT r.id, r.title, r.category, r.location, r.incident_date, r.image_path
     FROM reports r WHERE r.type='lost' AND r.is_deleted=0 AND r.status='open'
     ORDER BY r.created_at DESC LIMIT 3"
)->fetchAll();

$latestFound = $pdo->query(
    "SELECT r.id, r.title, r.category, r.location, r.incident_date, r.image_path
     FROM reports r WHERE r.type='found' AND r.is_deleted=0 AND r.status='open'
     ORDER BY r.created_at DESC LIMIT 3"
)->fetchAll();

// Quick stats for hero section
$totalReports  = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE is_deleted=0")->fetchColumn();
$totalResolved = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status='resolved'")->fetchColumn();
$totalUsers    = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<!-- ══ HERO SECTION ═══════════════════════════════════════════ -->
<section class="hero" id="hero">
    <div class="hero-bg"></div>
    <div class="container hero-content">
        <div class="hero-text">
            <span class="hero-eyebrow">🔍 Smart Lost &amp; Found Platform</span>
            <h1 class="hero-title">
                Lost something?<br>
                <span class="hero-accent">ReClaim</span> it.
            </h1>
            <p class="hero-subtitle">
                Report lost or found items, get smart match suggestions,
                and connect with the people who can help you recover what matters.
            </p>
            <div class="hero-actions">
                <a href="report-create.php?type=lost" class="btn btn-hero-primary">
                    😟 I Lost Something
                </a>
                <a href="report-create.php?type=found" class="btn btn-hero-secondary">
                    🎉 I Found Something
                </a>
            </div>
        </div>

        <!-- Hero stats -->
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-value"><?= number_format($totalReports) ?>+</span>
                <span class="hero-stat-label">Reports</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value"><?= number_format($totalResolved) ?>+</span>
                <span class="hero-stat-label">Items Returned</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value"><?= number_format($totalUsers) ?>+</span>
                <span class="hero-stat-label">Users</span>
            </div>
        </div>
    </div>

    <!-- Scroll indicator -->
    <div class="hero-scroll">
        <span>↓ Explore</span>
    </div>
</section>

<!-- ══ LIVE SEARCH BAR ═══════════════════════════════════════ -->
<section class="search-bar-section">
    <div class="container">
        <div class="hero-search-wrap">
            <input type="text" id="heroSearch" class="hero-search-input"
                   placeholder="🔍 Search for any item: phone, wallet, keys…"
                   autocomplete="off" aria-label="Search reports">
            <div class="hero-search-results" id="heroSearchResults"></div>
        </div>
    </div>
</section>

<!-- ══ HOW IT WORKS ══════════════════════════════════════════ -->
<section class="section how-it-works">
    <div class="container">
        <div class="section-header">
            <h2 class="section-heading">How ReClaim Works</h2>
            <p class="section-desc">A simple 4-step process to recover your belongings.</p>
        </div>

        <div class="steps-grid">
            <div class="how-step">
                <div class="how-step-icon">📝</div>
                <div class="how-step-num">Step 1</div>
                <h3>Submit a Report</h3>
                <p>Describe the lost or found item with photos, location, and date.</p>
            </div>
            <div class="how-step">
                <div class="how-step-icon">🤖</div>
                <div class="how-step-num">Step 2</div>
                <h3>Smart Matching</h3>
                <p>Our rule-based algorithm scores and suggests matching reports automatically.</p>
            </div>
            <div class="how-step">
                <div class="how-step-icon">📋</div>
                <div class="how-step-num">Step 3</div>
                <h3>Submit a Claim</h3>
                <p>Found a match? Submit a private ownership claim with proof details.</p>
            </div>
            <div class="how-step">
                <div class="how-step-icon">✅</div>
                <div class="how-step-num">Step 4</div>
                <h3>Get It Back!</h3>
                <p>Admins review claims and facilitate the safe return of items.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ RECENT LOST ITEMS ═════════════════════════════════════ -->
<section class="section recent-section">
    <div class="container">
        <div class="section-header-row">
            <div>
                <h2 class="section-heading">😟 Recently Lost</h2>
                <p class="section-desc">Can you help find these items?</p>
            </div>
            <a href="report-list.php?type=lost" class="btn btn-outline">View All Lost →</a>
        </div>

        <?php if (empty($latestLost)): ?>
            <div class="empty-state-inline">No lost reports yet. <a href="report-create.php?type=lost">Be the first!</a></div>
        <?php else: ?>
        <div class="report-grid report-grid-3">
            <?php foreach ($latestLost as $r): ?>
            <article class="report-card">
                <div class="report-card-img <?= $r['image_path'] ? '' : 'report-card-img-placeholder' ?>">
                    <?php if ($r['image_path']): ?>
                        <img src="/ReClaim/uploads/<?= h($r['image_path']) ?>" alt="<?= h($r['title']) ?>" loading="lazy">
                    <?php else: ?>😟<?php endif; ?>
                </div>
                <div class="report-card-body">
                    <span class="badge badge-danger">Lost</span>
                    <h3 class="report-card-title">
                        <a href="report-view.php?id=<?= $r['id'] ?>"><?= h($r['title']) ?></a>
                    </h3>
                    <div class="report-card-meta">
                        <span>📂 <?= h($r['category']) ?></span>
                        <span>📍 <?= h($r['location'] ?: 'N/A') ?></span>
                        <span>📅 <?= date('d M Y', strtotime($r['incident_date'])) ?></span>
                    </div>
                </div>
                <div class="report-card-footer">
                    <a href="report-view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">View Details</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══ RECENT FOUND ITEMS ════════════════════════════════════ -->
<section class="section recent-section section-alt">
    <div class="container">
        <div class="section-header-row">
            <div>
                <h2 class="section-heading">🎉 Recently Found</h2>
                <p class="section-desc">Someone might have found your item!</p>
            </div>
            <a href="report-list.php?type=found" class="btn btn-outline">View All Found →</a>
        </div>

        <?php if (empty($latestFound)): ?>
            <div class="empty-state-inline">No found reports yet. <a href="report-create.php?type=found">Report a found item!</a></div>
        <?php else: ?>
        <div class="report-grid report-grid-3">
            <?php foreach ($latestFound as $r): ?>
            <article class="report-card">
                <div class="report-card-img <?= $r['image_path'] ? '' : 'report-card-img-placeholder' ?>">
                    <?php if ($r['image_path']): ?>
                        <img src="/ReClaim/uploads/<?= h($r['image_path']) ?>" alt="<?= h($r['title']) ?>" loading="lazy">
                    <?php else: ?>🎉<?php endif; ?>
                </div>
                <div class="report-card-body">
                    <span class="badge badge-success">Found</span>
                    <h3 class="report-card-title">
                        <a href="report-view.php?id=<?= $r['id'] ?>"><?= h($r['title']) ?></a>
                    </h3>
                    <div class="report-card-meta">
                        <span>📂 <?= h($r['category']) ?></span>
                        <span>📍 <?= h($r['location'] ?: 'N/A') ?></span>
                        <span>📅 <?= date('d M Y', strtotime($r['incident_date'])) ?></span>
                    </div>
                </div>
                <div class="report-card-footer">
                    <a href="report-view.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline">View Details</a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ══ FEATURES ══════════════════════════════════════════════ -->
<section class="section features-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-heading">Why Choose ReClaim?</h2>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure & Private</h3>
                <p>Ownership verification details are private. Passwords are bcrypt-hashed. CSRF protection on all forms.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>Smart Matching</h3>
                <p>Transparent rule-based scoring on category, name, location, and date. No black-box AI.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3>Verified Claims</h3>
                <p>Admin-reviewed ownership process prevents fraud and protects both finders and owners.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>Real-time Updates</h3>
                <p>Get instant notifications when matches are found or your claims are reviewed.</p>
            </div>
        </div>
    </div>
</section>

<!-- ══ CTA ═══════════════════════════════════════════════════ -->
<?php if (!is_logged_in()): ?>
<section class="section cta-section">
    <div class="container">
        <div class="cta-card">
            <h2>Ready to find what you lost?</h2>
            <p>Join thousands of people using ReClaim to recover their belongings.</p>
            <div class="cta-actions">
                <a href="register.php" class="btn btn-cta-primary">Get Started — It's Free</a>
                <a href="report-list.php" class="btn btn-cta-secondary">Browse Reports</a>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
