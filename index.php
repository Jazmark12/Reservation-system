<?php
/**
 * index.php – Dashboard / Home
 */
require_once __DIR__ . '/db.php';
$pageTitle = 'Dashboard';
$rootPath  = '';
include __DIR__ . '/header.php';

$db = getDB();

$totalItems        = (int) $db->query('SELECT COUNT(*) FROM items')->fetchColumn();
$totalReservations = (int) $db->query('SELECT COUNT(*) FROM reservations')->fetchColumn();
$totalRevenue      = (float) $db->query('SELECT COALESCE(SUM(amount_due),0) FROM reservations')->fetchColumn();

// Recent reservations
$recent = $db->query(
    'SELECT r.reservation_id, r.customer_number, r.payment_type,
            r.amount_due, r.created_at
     FROM reservations r
     ORDER BY r.created_at DESC
     LIMIT 5'
)->fetchAll();
?>

<div class="page-header">
    <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <a href="items/index.php" class="text-decoration-none">
            <div class="stat-card stat-blue">
                <div class="stat-icon"><i class="bi bi-box-seam-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $totalItems ?></div>
                    <div class="stat-label">Total Items</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-4">
        <a href="reservations/index.php" class="text-decoration-none">
            <div class="stat-card stat-green">
                <div class="stat-icon"><i class="bi bi-journal-bookmark-fill"></i></div>
                <div>
                    <div class="stat-value"><?= $totalReservations ?></div>
                    <div class="stat-label">Total Reservations</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="stat-card stat-purple">
            <div class="stat-icon"><i class="bi bi-currency-exchange"></i></div>
            <div>
                <div class="stat-value">&#8369;<?= number_format($totalRevenue, 2) ?></div>
                <div class="stat-label">Total Revenue (Amount Due)</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-lightning-fill me-1"></i> Quick Actions
            </div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="items/add.php" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add New Item
                </a>
                <a href="reservations/add.php" class="btn btn-success">
                    <i class="bi bi-plus-circle me-1"></i> New Reservation
                </a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-info-circle-fill me-1"></i> Discount Structure
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <strong class="d-block text-primary mb-1">CASH</strong>
                        <ul class="list-unstyled small mb-0">
                            <li>&ge; 100 items &rarr; <strong>10%</strong></li>
                            <li>50–99 items &rarr; <strong>8%</strong></li>
                            <li>25–49 items &rarr; <strong>5%</strong></li>
                            <li>&lt; 25 items &rarr; <strong>0%</strong></li>
                        </ul>
                    </div>
                    <div class="col-6">
                        <strong class="d-block text-success mb-1">CREDIT</strong>
                        <ul class="list-unstyled small mb-0">
                            <li>&ge; 100 items &rarr; <strong>8%</strong></li>
                            <li>50–99 items &rarr; <strong>5%</strong></li>
                            <li>25–49 items &rarr; <strong>3%</strong></li>
                            <li>&lt; 25 items &rarr; <strong>0%</strong></li>
                        </ul>
                    </div>
                </div>
                <p class="text-muted small mt-2 mb-0">
                    <i class="bi bi-info-circle"></i>
                    Discount applies to items flagged as <em>discountable</em>.
                    Total item count = &Sigma;(quantity&nbsp;&times;&nbsp;qty&#8209;by&#8209;order).
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Reservations -->
<div class="card">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-1"></i> Recent Reservations</span>
        <a href="reservations/index.php" class="btn btn-sm btn-light">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent)): ?>
            <p class="text-muted text-center py-4 mb-0">No reservations yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer No.</th>
                        <th>Payment Type</th>
                        <th class="text-end">Amount Due</th>
                        <th>Date Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= $r['reservation_id'] ?></td>
                        <td><?= htmlspecialchars($r['customer_number']) ?></td>
                        <td>
                            <span class="badge <?= $r['payment_type'] === 'CASH' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                <?= $r['payment_type'] ?>
                            </span>
                        </td>
                        <td class="text-end">&#8369;<?= number_format($r['amount_due'], 2) ?></td>
                        <td><?= date('M d, Y', strtotime($r['created_at'])) ?></td>
                        <td>
                            <a href="reservations/view.php?id=<?= $r['reservation_id'] ?>"
                               class="btn btn-sm btn-outline-primary btn-action">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
