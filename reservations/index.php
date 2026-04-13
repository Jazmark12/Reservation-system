<?php
/**
 * reservations/index.php – List all reservations
 */
session_start();
require_once __DIR__ . '/../db.php';
$pageTitle = 'Reservations';
$rootPath  = '../';
include __DIR__ . '/../header.php';

$db = getDB();

$search = trim($_GET['search'] ?? '');
$params = [];
$sql    = 'SELECT * FROM reservations';
if ($search !== '') {
    $sql   .= ' WHERE customer_number LIKE :s OR reservation_id = :id';
    $params = [':s' => '%' . $search . '%', ':id' => (int) $search];
}
$sql .= ' ORDER BY created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="page-header">
    <h2><i class="bi bi-journal-bookmark-fill me-2"></i>Reservations</h2>
    <a href="add.php" class="btn btn-success">
        <i class="bi bi-plus-circle me-1"></i> New Reservation
    </a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Search -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-sm-8 col-md-6">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by reservation ID or customer number…"
                       value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-1"></i> Search
                </button>
                <?php if ($search !== ''): ?>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-1"></i> Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Res. #</th>
                        <th>Customer No.</th>
                        <th>Payment Type</th>
                        <th class="text-center">Total Items</th>
                        <th class="text-center">Discount</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Amount Due</th>
                        <th>Expected Payment</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <?= $search !== '' ? 'No reservations match your search.' : 'No reservations yet. <a href="add.php">Create one</a>.' ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                    <tr>
                        <td><?= $r['reservation_id'] ?></td>
                        <td><?= htmlspecialchars($r['customer_number']) ?></td>
                        <td>
                            <span class="badge <?= $r['payment_type'] === 'CASH' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                <?= $r['payment_type'] ?>
                            </span>
                        </td>
                        <td class="text-center"><?= number_format($r['total_items']) ?></td>
                        <td class="text-center"><?= number_format($r['discount_rate'], 1) ?>%</td>
                        <td class="text-end">&#8369;<?= number_format($r['subtotal'], 2) ?></td>
                        <td class="text-end fw-bold text-primary">&#8369;<?= number_format($r['amount_due'], 2) ?></td>
                        <td><?= date('M d, Y', strtotime($r['expected_payment_date'])) ?></td>
                        <td class="text-center">
                            <a href="view.php?id=<?= $r['reservation_id'] ?>"
                               class="btn btn-sm btn-outline-primary btn-action" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="delete.php?id=<?= $r['reservation_id'] ?>"
                               class="btn btn-sm btn-outline-danger btn-action ms-1"
                               title="Delete"
                               onclick="return confirm('Delete reservation #<?= $r['reservation_id'] ?>?');">
                                <i class="bi bi-trash-fill"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
