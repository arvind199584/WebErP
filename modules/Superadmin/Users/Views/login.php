<?php
$error = $_SESSION['error'] ?? ($error ?? null);
unset($_SESSION['error']);
$notices = $notices ?? [];
$gitApkUrl = 'https://raw.githubusercontent.com/arvind199584/WebErP/main/EnterpriseERP.apk';
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=6&data=' . urlencode($gitApkUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enterprise ERP - Official Portal & Download Hub</title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #0d6efd;
            --primary-dark: #0a58ca;
            --navy-900: #0f172a;
            --navy-800: #1e293b;
            --navy-700: #334155;
            --slate-100: #f1f5f9;
            --slate-50: #f8fafc;
            --border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Top Bar */
        .portal-topbar {
            background: linear-gradient(135deg, var(--navy-900) 0%, var(--navy-800) 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 0.85rem 1.5rem;
        }

        .portal-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: -0.02em;
        }

        .portal-brand-badge {
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            padding: 0.2rem 0.6rem;
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Left Hero Showcase */
        .hero-section {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            padding: 2.25rem;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        }

        .feature-card {
            border: 1px solid #edf2f7;
            border-radius: 12px;
            padding: 1.15rem;
            background: #ffffff;
            transition: all 0.2s ease-in-out;
            height: 100%;
        }

        .feature-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }

        .feature-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 0.85rem;
        }

        /* Notices Section */
        .notice-card {
            border-left: 4px solid var(--primary);
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem 1.25rem;
            margin-bottom: 0.85rem;
            transition: all 0.15s ease;
        }

        .notice-card:hover {
            background: #f1f5f9;
        }

        /* APK Download Card */
        .download-box {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 1rem;
            color: #ffffff;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .download-box::after {
            content: '';
            position: absolute;
            top: -30px;
            right: -30px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(13, 110, 253, 0.25) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Auth Card */
        .auth-card {
            background: #ffffff;
            border-radius: 1.25rem;
            border: 1px solid var(--border);
            padding: 2.25rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            position: sticky;
            top: 2rem;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .btn-guest-demo {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            color: #78350f;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .btn-guest-demo:hover {
            background: #fde68a;
            color: #451a03;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }

        .qr-frame {
            background: #ffffff;
            padding: 8px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="portal-topbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <a href="/" class="portal-brand">
                <i class="bi bi-buildings-fill text-primary fs-4"></i>
                <span>Enterprise ERP</span>
            </a>
            <span class="portal-brand-badge d-none d-sm-inline-block">Operations & Asset Portal</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="#downloadSection" class="btn btn-outline-light btn-sm py-1 px-3 d-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-android2 text-success"></i>
                <span class="d-none d-md-inline">Download Android APK</span>
                <span class="d-md-none">App</span>
            </a>
            <a href="?action=guest_login" class="btn btn-warning btn-sm py-1 px-3 d-flex align-items-center gap-1 rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-lightning-charge-fill"></i>
                <span>Guest Demo</span>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <div class="container-fluid px-3 px-lg-5 py-4">
        <div class="row g-4 justify-content-center">

            <!-- LEFT COLUMN: Showcase, About, Notices, APK Download -->
            <div class="col-xl-7 col-lg-7">
                
                <!-- Hero & About Section -->
                <div class="hero-section mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">Enterprise Management Platform</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Cloud & Local Synchronized</span>
                    </div>
                    <h1 class="display-6 fw-bold text-navy-900 mb-3" style="letter-spacing: -0.03em;">
                        Integrated Operations & Field Resource ERP
                    </h1>
                    <p class="text-secondary lead fs-6 mb-4">
                        A unified digital backbone connecting <strong>Administrative Approvals & Finance</strong>, <strong>Human Resource Attendance & Wages</strong>, <strong>Heavy Machinery Workshop & Job Cards</strong>, and <strong>Central Store Inventory</strong>.
                    </p>

                    <!-- Feature Grid Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4 col-sm-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-primary-subtle text-primary">
                                    <i class="bi bi-bank"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Finance & Works</h6>
                                <p class="text-muted small mb-0">AA/ES Sanctions, Contractor Agreements, RA Bills, and Budget Head tracking.</p>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-success-subtle text-success">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Human Resource</h6>
                                <p class="text-muted small mb-0">Daily muster roll, automatic monthly wages, EPF & ESIC ledger statutory compliance.</p>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-warning-subtle text-warning">
                                    <i class="bi bi-gear-wide-connected"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Fleet & Workshop</h6>
                                <p class="text-muted small mb-0">Machinery run duration, repair job cards, spare parts issues & preventive servicing.</p>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-info-subtle text-info">
                                    <i class="bi bi-boxes"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Store Inventory</h6>
                                <p class="text-muted small mb-0">Fuel, lubricants, fertilizers & consumables with dual Inward/Outward stock ledgers.</p>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-purple-subtle text-primary">
                                    <i class="bi bi-robot"></i>
                                </div>
                                <h6 class="fw-bold mb-1">AI Office Assistant</h6>
                                <p class="text-muted small mb-0">Automated official letter drafter, NLP search, and smart executive report printing.</p>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="feature-card">
                                <div class="feature-icon-box bg-danger-subtle text-danger">
                                    <i class="bi bi-shield-check"></i>
                                </div>
                                <h6 class="fw-bold mb-1">Role Security</h6>
                                <p class="text-muted small mb-0">Row-level division isolation, multi-office permissions, and tamper-evident audit logs.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Noticeboard Section -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-megaphone-fill text-primary"></i>
                            <h6 class="m-0 fw-bold text-dark">Official Noticeboard & Circulars</h6>
                        </div>
                        <span class="badge bg-light text-secondary border"><?= count($notices) ?> Active</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (empty($notices)): ?>
                            <div class="text-center py-3 text-muted">
                                <i class="bi bi-bell-slash fs-3 d-block mb-1"></i>
                                No active public notices currently posted.
                            </div>
                        <?php else: ?>
                            <?php foreach ($notices as $n): ?>
                                <?php 
                                    $borderColor = '#0d6efd';
                                    $badgeBg = 'bg-primary';
                                    if ($n['badge_type'] === 'Circular') { $borderColor = '#0dcaf0'; $badgeBg = 'bg-info text-dark'; }
                                    if ($n['badge_type'] === 'Maintenance') { $borderColor = '#ffc107'; $badgeBg = 'bg-warning text-dark'; }
                                    if ($n['badge_type'] === 'Urgent') { $borderColor = '#dc3545'; $badgeBg = 'bg-danger'; }
                                ?>
                                <div class="notice-card" style="border-left-color: <?= $borderColor ?>;">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                            <span class="badge <?= $badgeBg ?>"><?= htmlspecialchars($n['badge_type']) ?></span>
                                            <span><?= htmlspecialchars($n['title']) ?></span>
                                        </div>
                                        <small class="text-muted text-nowrap ms-2"><?= date('M d, Y', strtotime($n['created_at'])) ?></small>
                                    </div>
                                    <p class="text-secondary small mb-0"><?= nl2br(htmlspecialchars($n['content'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Mobile App Download Hub -->
                <div id="downloadSection" class="download-box shadow-sm mb-4">
                    <div class="row align-items-center g-4">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-success px-2 py-1"><i class="bi bi-android2 me-1"></i> Official Android App</span>
                                <span class="badge bg-primary-subtle text-info px-2 py-1">Native Jetpack Compose</span>
                            </div>
                            <h4 class="fw-bold mb-2">Enterprise ERP Mobile App</h4>
                            <p class="text-white-50 small mb-3">
                                Carry the full operations dashboard in your pocket. Access machinery run logs, verify attendance muster rolls, view store inventory, and receive alerts in real time.
                            </p>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <a href="<?php echo htmlspecialchars($gitApkUrl); ?>" download="EnterpriseERP.apk" target="_blank" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2 shadow-sm rounded-pill fw-semibold">
                                    <i class="bi bi-download fs-5"></i>
                                    <span>Download APK (v1.0)</span>
                                </a>
                                <span class="text-white-50 small">
                                    <i class="bi bi-file-earmark-check me-1"></i> ~9.5 MB &bull; Android 8.0+
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="qr-frame">
                                <!-- Clean QR Code pointing directly to GitHub repository APK download -->
                                <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="Scan QR Code to Download APK" width="130" height="130" class="img-fluid rounded">
                            </div>
                            <small class="d-block text-white-50 mt-2" style="font-size: 0.725rem;">
                                <i class="bi bi-camera me-1"></i> Scan with mobile camera to install directly
                            </small>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN: Authentication & Guest Demo Sandbox -->
            <div class="col-xl-4 col-lg-5">
                <div class="auth-card">
                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-2" style="width: 56px; height: 56px;">
                            <i class="bi bi-shield-lock-fill text-primary fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">System Login</h4>
                        <p class="text-muted small mb-0">Enter your authorized divisional credentials</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger d-flex align-items-center small py-2 px-3 rounded-3 mb-3" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2 fs-6"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Standard Login Form -->
                    <form action="?action=login" method="POST" class="mb-4">
                        <?php echo \App\Core\CSRFManager::getTokenInput(); ?>

                        <div class="mb-3">
                            <label for="usrname" class="form-label small fw-semibold text-secondary">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 ps-0" id="usrname" name="usrname" required placeholder="e.g. daredevil" autocomplete="username">
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-key"></i>
                                </span>
                                <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                                <button class="btn btn-outline-secondary border-start-0 bg-light text-muted" type="button" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i>
                            <span>Sign In to Portal</span>
                        </button>
                    </form>

                    <!-- Divider -->
                    <div class="position-relative text-center my-4">
                        <hr class="text-secondary opacity-25">
                        <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 small text-muted text-uppercase fw-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                            OR EVALUATE AS GUEST
                        </span>
                    </div>

                    <!-- Interactive Guest Demo Sandbox Box -->
                    <div class="card border border-warning bg-warning bg-opacity-10 rounded-3 p-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-warning text-dark"><i class="bi bi-lightning-charge-fill me-1"></i> Interactive Sandbox</span>
                            <span class="badge bg-white text-secondary border">Isolated Database</span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Full Guest Tour (No Credentials Required)</h6>
                        <p class="text-secondary small mb-3">
                            Explore pre-loaded dummy records for <strong>Offices, Employees, Budgets, Store Items, and Workshop Fleet</strong>. You can freely create and edit data.
                        </p>
                        <a href="?action=guest_login" class="btn btn-guest-demo w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm text-decoration-none">
                            <i class="bi bi-arrow-right-circle-fill"></i>
                            <span>Launch Live Guest Demo</span>
                        </a>
                        <small class="d-block text-muted text-center mt-2" style="font-size: 0.725rem;">
                            <i class="bi bi-shield-check text-success me-1"></i> Runs safely on <code>modpyphp_demo</code> with 1-click restore.
                        </small>
                    </div>

                    <div class="text-center mt-4 pt-2 border-top">
                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                            Enterprise ERP Operations Portal &bull; Release 2026.1
                        </small>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Script for password toggle -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('togglePassword');
        const passInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (toggleBtn && passInput && eyeIcon) {
            toggleBtn.addEventListener('click', function() {
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    eyeIcon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    passInput.type = 'password';
                    eyeIcon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        }
    });
    </script>
</body>
</html>
