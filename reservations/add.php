<?php
/**
 * reservations/add.php – Create a new reservation
 *
 * Discount tiers (based on TOTAL items = sum of qty × quantity_by_order):
 *   CASH  : ≥100 → 10%  |  50–99 → 8%  |  25–49 → 5%  |  <25 → 0%
 *   CREDIT: ≥100 →  8%  |  50–99 → 5%  |  25–49 → 3%  |  <25 → 0%
 *
 * Discount applies only to items flagged as discountable (discounted = 1).
 */
session_start();
require_once __DIR__ . '/../db.php';
$pageTitle = 'New Reservation';
$rootPath  = '../';

$db = getDB();

// Load all items for JS and the dropdown
$allItems = $db->query('SELECT * FROM items ORDER BY item_description ASC')->fetchAll();

$errors = [];
$formData = [
    'customer_number'       => '',
    'expected_payment_date' => '',
    'payment_type'          => 'CASH',
];

// -------------------------------------------------------
// POST handler
// -------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['customer_number']       = trim($_POST['customer_number'] ?? '');
    $formData['expected_payment_date'] = trim($_POST['expected_payment_date'] ?? '');
    $formData['payment_type']          = in_array($_POST['payment_type'] ?? '', ['CASH', 'CREDIT'])
                                         ? $_POST['payment_type']
                                         : 'CASH';

    $orderItems = $_POST['order_items'] ?? [];  // array of ['item_code' => …, 'quantity' => …]

    // Validation
    if ($formData['customer_number'] === '') {
        $errors[] = 'Customer number is required.';
    }
    if ($formData['expected_payment_date'] === '') {
        $errors[] = 'Expected payment date is required.';
    }
    if (empty($orderItems)) {
        $errors[] = 'At least one item must be added to the reservation.';
    }

    // Validate individual order lines
    $cleanLines = [];
    foreach ($orderItems as $line) {
        $itemCode = (int) ($line['item_code'] ?? 0);
        $qty      = (int) ($line['quantity']  ?? 0);
        if ($itemCode < 1 || $qty < 1) continue;   // skip empty rows
        $cleanLines[] = ['item_code' => $itemCode, 'quantity' => $qty];
    }
    if (empty($cleanLines)) {
        $errors[] = 'Please add at least one valid item (item + quantity ≥ 1).';
    }

    if (empty($errors)) {
        // Build item lookup by item_code
        $itemMap = [];
        foreach ($allItems as $it) {
            $itemMap[$it['item_code']] = $it;
        }

        // Compute totals
        $totalItems     = 0;
        $subtotal       = 0.0;
        foreach ($cleanLines as &$line) {
            $it = $itemMap[$line['item_code']] ?? null;
            if (!$it) continue;
            $line['unit_price']      = (float) $it['price'];
            $line['qty_by_order']    = (int)   $it['quantity_by_order'];
            $line['discounted']      = (int)   $it['discounted'];
            $totalItems             += $line['quantity'] * $line['qty_by_order'];
            $subtotal               += $line['quantity'] * $line['unit_price'];
        }
        unset($line);

        // Discount rate
        $payType      = $formData['payment_type'];
        $discountRate = calculateDiscountRate($totalItems, $payType);

        // Apply discount only to discountable items
        $discountedSubtotal    = 0.0;
        $nonDiscountedSubtotal = 0.0;
        foreach ($cleanLines as $line) {
            $lineTotal = $line['quantity'] * $line['unit_price'];
            if ($line['discounted']) {
                $discountedSubtotal += $lineTotal;
            } else {
                $nonDiscountedSubtotal += $lineTotal;
            }
        }
        $discountAmount = $discountedSubtotal * ($discountRate / 100);
        $amountDue      = $subtotal - $discountAmount;

        // Persist
        $db->beginTransaction();
        try {
            $stmtR = $db->prepare(
                'INSERT INTO reservations
                    (customer_number, expected_payment_date, payment_type,
                     total_items, discount_rate, subtotal, discount_amount, amount_due)
                 VALUES (:cn, :epd, :pt, :ti, :dr, :sub, :da, :ad)'
            );
            $stmtR->execute([
                ':cn'  => $formData['customer_number'],
                ':epd' => $formData['expected_payment_date'],
                ':pt'  => $payType,
                ':ti'  => $totalItems,
                ':dr'  => $discountRate,
                ':sub' => $subtotal,
                ':da'  => $discountAmount,
                ':ad'  => $amountDue,
            ]);
            $reservationId = (int) $db->lastInsertId();

            $stmtI = $db->prepare(
                'INSERT INTO reservation_items (reservation_id, item_code, quantity, unit_price, item_total)
                 VALUES (:rid, :ic, :qty, :up, :it)'
            );
            foreach ($cleanLines as $line) {
                $stmtI->execute([
                    ':rid' => $reservationId,
                    ':ic'  => $line['item_code'],
                    ':qty' => $line['quantity'],
                    ':up'  => $line['unit_price'],
                    ':it'  => $line['quantity'] * $line['unit_price'],
                ]);
            }
            $db->commit();
            $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Reservation #' . $reservationId . ' created successfully.'];
            header('Location: view.php?id=' . $reservationId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// -------------------------------------------------------
function calculateDiscountRate(int $totalItems, string $paymentType): float {
    if ($paymentType === 'CASH') {
        if ($totalItems >= 100) return 10.0;
        if ($totalItems >= 50)  return 8.0;
        if ($totalItems >= 25)  return 5.0;
        return 0.0;
    } else { // CREDIT
        if ($totalItems >= 100) return 8.0;
        if ($totalItems >= 50)  return 5.0;
        if ($totalItems >= 25)  return 3.0;
        return 0.0;
    }
}

include __DIR__ . '/../header.php';
// Encode items for JavaScript
$itemsJson = json_encode(array_values($allItems));
?>

<div class="page-header">
    <h2><i class="bi bi-plus-circle-fill me-2"></i>New Reservation</h2>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <strong>Please fix the following errors:</strong>
    <ul class="mb-0 ps-3 mt-1">
        <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="post" id="reservationForm" novalidate>
<div class="row g-4">

    <!-- LEFT: Reservation Details -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-person-fill me-1"></i> Reservation Details
            </div>
            <div class="card-body">

                <div class="mb-3">
                    <label for="customer_number" class="form-label">
                        Customer Number <span class="text-danger">*</span>
                    </label>
                    <input type="text" id="customer_number" name="customer_number"
                           class="form-control" required maxlength="100"
                           placeholder="e.g. RNZ"
                           value="<?= htmlspecialchars($formData['customer_number']) ?>">
                </div>

                <div class="mb-3">
                    <label for="expected_payment_date" class="form-label">
                        Expected Payment Date <span class="text-danger">*</span>
                    </label>
                    <input type="date" id="expected_payment_date" name="expected_payment_date"
                           class="form-control" required
                           value="<?= htmlspecialchars($formData['expected_payment_date']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label">Mode of Payment <span class="text-danger">*</span></label>
                    <div class="d-flex gap-4">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_type"
                                   id="pt_cash" value="CASH"
                                   <?= $formData['payment_type'] === 'CASH' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pt_cash">CASH</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_type"
                                   id="pt_credit" value="CREDIT"
                                   <?= $formData['payment_type'] === 'CREDIT' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="pt_credit">CREDIT</label>
                        </div>
                    </div>
                </div>

                <!-- Live Summary Panel -->
                <div id="summary-panel" class="mt-4">
                    <div class="summary-row">
                        <span>Total Items (units):</span>
                        <span id="sum-total-items">0</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>&#8369;<span id="sum-subtotal">0.00</span></span>
                    </div>
                    <div class="summary-row">
                        <span>Discount Rate:</span>
                        <span id="sum-discount-rate">0%</span>
                    </div>
                    <div class="summary-row">
                        <span>Discount Amount:</span>
                        <span>&#8369;<span id="sum-discount-amt">0.00</span></span>
                    </div>
                    <div class="summary-row total">
                        <span>Amount Due:</span>
                        <span>&#8369;<span id="sum-amount-due">0.00</span></span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- RIGHT: Items -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart-fill me-1"></i> Order Items</span>
                <button type="button" class="btn btn-sm btn-light" id="addItemBtn">
                    <i class="bi bi-plus-circle me-1"></i> Add Item
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" id="items-table">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:200px">Item</th>
                                <th style="width:90px" class="text-center">Qty&nbsp;Ordered</th>
                                <th style="width:80px" class="text-center">Qty/Order</th>
                                <th style="width:110px" class="text-end">Unit Price</th>
                                <th style="width:120px" class="text-end">Line Total</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="items-body">
                            <!-- rows injected by JS -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="4" class="text-end pe-3">Grand Total:</td>
                                <td class="text-end fw-bold" id="foot-total">&#8369;0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="text-muted small px-3 py-2 mb-0">
                    <i class="bi bi-info-circle"></i>
                    The same item can be added multiple times (multiple reservations of the same item).
                </p>
            </div>
        </div>
    </div>

</div><!-- /.row -->

<div class="d-flex gap-2 mt-4">
    <button type="submit" class="btn btn-success px-4">
        <i class="bi bi-check-circle-fill me-1"></i> Save Reservation
    </button>
    <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
</div>

</form><!-- /#reservationForm -->

<!-- ============================================================
     JavaScript: dynamic item rows + live discount calculation
     ============================================================ -->
<script>
(function () {
    'use strict';

    // All items from PHP
    const ITEMS = <?= $itemsJson ?>;
    const itemMap = {};
    ITEMS.forEach(it => { itemMap[it.item_code] = it; });

    const tbody    = document.getElementById('items-body');
    const addBtn   = document.getElementById('addItemBtn');
    const footTotal= document.getElementById('foot-total');

    // Payment type radios
    const ptRadios = document.querySelectorAll('input[name="payment_type"]');

    let rowIndex = 0;

    // Build option HTML once
    const optionsHtml = ITEMS.map(it =>
        `<option value="${it.item_code}">${escHtml(it.item_description)} — ₱${fmt(it.price)}</option>`
    ).join('');

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function fmt(n) {
        return parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    // ── Add a new row ──────────────────────────────────────────
    function addRow(preItemCode, preQty) {
        const idx = rowIndex++;
        const tr  = document.createElement('tr');
        tr.dataset.idx = idx;

        tr.innerHTML = `
            <td>
                <select name="order_items[${idx}][item_code]"
                        class="form-select form-select-sm item-select" required>
                    <option value="">-- Select item --</option>
                    ${optionsHtml}
                </select>
            </td>
            <td class="text-center">
                <input type="number" name="order_items[${idx}][quantity]"
                       class="form-control form-control-sm qty-input text-center"
                       min="1" value="${preQty || 1}" required style="width:70px">
            </td>
            <td class="text-center qty-by-order-cell text-muted small">—</td>
            <td class="text-end unit-price-cell text-muted small">—</td>
            <td class="text-end line-total-cell fw-semibold">—</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger remove-btn" title="Remove">
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>`;

        tbody.appendChild(tr);

        const sel = tr.querySelector('.item-select');
        const qty = tr.querySelector('.qty-input');

        if (preItemCode) {
            sel.value = preItemCode;
        }

        sel.addEventListener('change', () => { updateRow(tr); recalc(); });
        qty.addEventListener('input',  () => { updateRow(tr); recalc(); });
        tr.querySelector('.remove-btn').addEventListener('click', () => {
            tr.remove();
            recalc();
        });

        updateRow(tr);
        recalc();
    }

    // ── Update a single row's display ─────────────────────────
    function updateRow(tr) {
        const sel  = tr.querySelector('.item-select');
        const qty  = parseInt(tr.querySelector('.qty-input').value) || 0;
        const it   = itemMap[sel.value];

        const qboCell   = tr.querySelector('.qty-by-order-cell');
        const priceCell = tr.querySelector('.unit-price-cell');
        const totalCell = tr.querySelector('.line-total-cell');

        if (it) {
            qboCell.textContent   = it.quantity_by_order;
            priceCell.textContent = '₱' + fmt(it.price);
            const lineTotal = qty * parseFloat(it.price);
            totalCell.textContent = '₱' + fmt(lineTotal);
            totalCell.style.color = '';
        } else {
            qboCell.textContent   = '—';
            priceCell.textContent = '—';
            totalCell.textContent = '—';
        }
    }

    // ── Recalculate discount & totals ─────────────────────────
    function getPaymentType() {
        for (const r of ptRadios) {
            if (r.checked) return r.value;
        }
        return 'CASH';
    }

    function discountRate(totalItems, payType) {
        if (payType === 'CASH') {
            if (totalItems >= 100) return 10;
            if (totalItems >= 50)  return 8;
            if (totalItems >= 25)  return 5;
            return 0;
        } else {
            if (totalItems >= 100) return 8;
            if (totalItems >= 50)  return 5;
            if (totalItems >= 25)  return 3;
            return 0;
        }
    }

    function recalc() {
        const rows = tbody.querySelectorAll('tr');
        let totalUnits   = 0;
        let subtotal     = 0;
        let discSubtotal = 0;
        let grandTotal   = 0;

        rows.forEach(tr => {
            const sel = tr.querySelector('.item-select');
            const qty = parseInt(tr.querySelector('.qty-input').value) || 0;
            const it  = itemMap[sel.value];
            if (!it) return;

            const lineTotal  = qty * parseFloat(it.price);
            totalUnits      += qty * parseInt(it.quantity_by_order);
            subtotal        += lineTotal;
            if (parseInt(it.discounted)) {
                discSubtotal += lineTotal;
            }
            grandTotal += lineTotal;
        });

        const payType  = getPaymentType();
        const rate     = discountRate(totalUnits, payType);
        const discAmt  = discSubtotal * (rate / 100);
        const amtDue   = subtotal - discAmt;

        // Update footer row
        footTotal.innerHTML = '&#8369;' + fmt(grandTotal);

        // Update summary panel
        document.getElementById('sum-total-items').textContent  = totalUnits.toLocaleString();
        document.getElementById('sum-subtotal').textContent     = fmt(subtotal);
        document.getElementById('sum-discount-rate').textContent= rate.toFixed(1) + '%';
        document.getElementById('sum-discount-amt').textContent = fmt(discAmt);
        document.getElementById('sum-amount-due').textContent   = fmt(amtDue);
    }

    // ── Event wiring ──────────────────────────────────────────
    addBtn.addEventListener('click', () => addRow());
    ptRadios.forEach(r => r.addEventListener('change', recalc));

    // Add one blank row by default
    addRow();
})();
</script>

<?php include __DIR__ . '/../footer.php'; ?>
