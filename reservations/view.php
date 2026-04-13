<?php
/**
 * reservations/view.php – View a single reservation (receipt-style)
 */
require_once __DIR__ . '/../db.php';
$pageTitle = 'Reservation Details';
$rootPath  = '../';
include __DIR__ . '/../header.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

// Load reservation
$stmtR = $db->prepare('SELECT * FROM reservations WHERE reservation_id = :id');
$stmtR->execute([':id' => $id]);
$res = $stmtR->fetch();

if (!$res) {
    echo '<div class="alert alert-danger">Reservation not found.</div>';
    include __DIR__ . '/../footer.php';
    exit;
}

// Load items
$stmtI = $db->prepare(
    'SELECT ri.*, i.item_description, i.discounted, i.quantity_by_order
     FROM reservation_items ri
     JOIN items i ON i.item_code = ri.item_code
     WHERE ri.reservation_id = :id
     ORDER BY ri.id ASC'
);
$stmtI->execute([':id' => $id]);
$lines = $stmtI->fetchAll();
?>

<div class="page-header">
    <h2><i class="bi bi-receipt me-2"></i>Reservation #<?= $res['reservation_id'] ?></h2>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <a href="delete.php?id=<?= $res['reservation_id'] ?>"
           class="btn btn-outline-danger"
           onclick="return confirm('Delete this reservation?');">
            <i class="bi bi-trash-fill me-1"></i> Delete
        </a>
    </div>
</div>

<div class="receipt-card">
    <!-- Receipt Header -->
    <div class="receipt-header">
        <h3><i class="bi bi-calendar2-check-fill me-2"></i>Reservation Receipt</h3>
        <p class="mb-0 opacity-75">Reservation &amp; Billing System</p>
    </div>

    <!-- Meta Info -->
    <div class="receipt-meta">
        <div class="meta-item">
            <span class="meta-label">Reservation #</span>
            <span class="meta-value"><?= $res['reservation_id'] ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Customer Number</span>
            <span class="meta-value"><?= htmlspecialchars($res['customer_number']) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Mode of Payment</span>
            <span class="meta-value">
                <span class="badge <?= $res['payment_type'] === 'CASH' ? 'bg-primary' : 'bg-warning text-dark' ?> fs-6">
                    <?= $res['payment_type'] ?>
                </span>
            </span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Expected Payment Date</span>
            <span class="meta-value"><?= date('F d, Y', strtotime($res['expected_payment_date'])) ?></span>
        </div>
        <div class="meta-item">
            <span class="meta-label">Date Created</span>
            <span class="meta-value"><?= date('M d, Y H:i', strtotime($res['created_at'])) ?></span>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card border-0" style="border-radius:0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item Description</th>
                            <th class="text-center">Discountable</th>
                            <th class="text-center">Qty/Order</th>
                            <th class="text-center">Times Ordered</th>
                            <th class="text-center">Total Units</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $lineNum = 1; ?>
                        <?php foreach ($lines as $line): ?>
                        <tr>
                            <td><?= $lineNum++ ?></td>
                            <td><?= htmlspecialchars($line['item_description']) ?></td>
                            <td class="text-center">
                                <?php if ($line['discounted']): ?>
                                    <span class="badge badge-discounted">Yes</span>
                                <?php else: ?>
                                    <span class="badge badge-nodiscount">No</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= number_format($line['quantity_by_order']) ?></td>
                            <td class="text-center"><?= number_format($line['quantity']) ?></td>
                            <td class="text-center">
                                <?= number_format($line['quantity'] * $line['quantity_by_order']) ?>
                            </td>
                            <td class="text-end">&#8369;<?= number_format($line['unit_price'], 2) ?></td>
                            <td class="text-end">&#8369;<?= number_format($line['item_total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Totals Summary -->
    <div class="card border-0 rounded-0 rounded-bottom">
        <div class="card-body">
            <div class="row justify-content-end">
                <div class="col-md-6 col-lg-5">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted">Total Item Units:</td>
                                <td class="text-end fw-semibold">
                                    <?= number_format($res['total_items']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Subtotal:</td>
                                <td class="text-end">&#8369;<?= number_format($res['subtotal'], 2) ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted">
                                    Discount (<?= number_format($res['discount_rate'], 1) ?>%):
                                </td>
                                <td class="text-end text-danger">
                                    &minus; &#8369;<?= number_format($res['discount_amount'], 2) ?>
                                </td>
                            </tr>
                            <tr class="table-primary fw-bold fs-5">
                                <td>Amount Due:</td>
                                <td class="text-end">&#8369;<?= number_format($res['amount_due'], 2) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.receipt-card -->

<?php include __DIR__ . '/../footer.php'; ?>
