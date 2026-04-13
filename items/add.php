<?php
/**
 * items/add.php – Add a new item
 */
session_start();
require_once __DIR__ . '/../db.php';
$pageTitle = 'Add Item';
$rootPath  = '../';

$errors = [];
$data   = ['item_description' => '', 'discounted' => 1, 'quantity_by_order' => 1, 'price' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['item_description']  = trim($_POST['item_description'] ?? '');
    $data['discounted']        = isset($_POST['discounted']) ? 1 : 0;
    $data['quantity_by_order'] = (int) ($_POST['quantity_by_order'] ?? 1);
    $data['price']             = $_POST['price'] ?? '';

    if ($data['item_description'] === '') {
        $errors[] = 'Item description is required.';
    }
    if ($data['quantity_by_order'] < 1) {
        $errors[] = 'Quantity by order must be at least 1.';
    }
    if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
        $errors[] = 'Price must be a valid non-negative number.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $stmt = $db->prepare(
            'INSERT INTO items (item_description, discounted, quantity_by_order, price)
             VALUES (:desc, :disc, :qty, :price)'
        );
        $stmt->execute([
            ':desc'  => $data['item_description'],
            ':disc'  => $data['discounted'],
            ':qty'   => $data['quantity_by_order'],
            ':price' => (float) $data['price'],
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Item added successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-plus-circle-fill me-2"></i>Add Item</h2>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Items
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">Item Information</div>
            <div class="card-body">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <!-- Item Code (auto) -->
                    <div class="mb-3">
                        <label class="form-label">Item Code</label>
                        <input type="text" class="form-control" value="(Auto-generated)" disabled>
                        <div class="form-text">Assigned automatically by the system.</div>
                    </div>

                    <!-- Item Description -->
                    <div class="mb-3">
                        <label for="item_description" class="form-label">
                            Item Description <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="item_description" name="item_description"
                               class="form-control" required maxlength="255"
                               value="<?= htmlspecialchars($data['item_description']) ?>">
                    </div>

                    <!-- Discounted -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="discounted"
                                   name="discounted" role="switch"
                                   <?= $data['discounted'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="discounted">
                                Discountable Item
                            </label>
                        </div>
                        <div class="form-text">Enable if this item qualifies for payment-based discounts.</div>
                    </div>

                    <!-- Quantity by Order -->
                    <div class="mb-3">
                        <label for="quantity_by_order" class="form-label">
                            Quantity by Order <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="quantity_by_order" name="quantity_by_order"
                               class="form-control" required min="1"
                               value="<?= (int) $data['quantity_by_order'] ?>">
                        <div class="form-text">
                            Number of units per single order entry (used for discount tier calculation).
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="mb-4">
                        <label for="price" class="form-label">
                            Price (&#8369;) <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="price" name="price"
                               class="form-control" required min="0" step="0.01"
                               value="<?= htmlspecialchars($data['price']) ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save-fill me-1"></i> Save Item
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
