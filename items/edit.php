<?php
/**
 * items/edit.php – Edit an existing item
 */
session_start();
require_once __DIR__ . '/../db.php';
$pageTitle = 'Edit Item';
$rootPath  = '../';

$db     = getDB();
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

// Load item
$item = $db->prepare('SELECT * FROM items WHERE item_code = :id');
$item->execute([':id' => $id]);
$item = $item->fetch();

if (!$item) {
    $_SESSION['flash'] = ['type' => 'danger', 'msg' => 'Item not found.'];
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item['item_description']  = trim($_POST['item_description'] ?? '');
    $item['discounted']        = isset($_POST['discounted']) ? 1 : 0;
    $item['quantity_by_order'] = (int) ($_POST['quantity_by_order'] ?? 1);
    $item['price']             = $_POST['price'] ?? '';

    if ($item['item_description'] === '') {
        $errors[] = 'Item description is required.';
    }
    if ($item['quantity_by_order'] < 1) {
        $errors[] = 'Quantity by order must be at least 1.';
    }
    if (!is_numeric($item['price']) || (float) $item['price'] < 0) {
        $errors[] = 'Price must be a valid non-negative number.';
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            'UPDATE items
             SET item_description = :desc,
                 discounted       = :disc,
                 quantity_by_order = :qty,
                 price            = :price
             WHERE item_code = :id'
        );
        $stmt->execute([
            ':desc'  => $item['item_description'],
            ':disc'  => $item['discounted'],
            ':qty'   => $item['quantity_by_order'],
            ':price' => (float) $item['price'],
            ':id'    => $id,
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Item updated successfully.'];
        header('Location: index.php');
        exit;
    }
}

include __DIR__ . '/../header.php';
?>

<div class="page-header">
    <h2><i class="bi bi-pencil-square me-2"></i>Edit Item</h2>
    <a href="index.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Items
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                Item Information &mdash; Code: <?= str_pad($id, 4, '0', STR_PAD_LEFT) ?>
            </div>
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
                    <!-- Item Code (read-only) -->
                    <div class="mb-3">
                        <label class="form-label">Item Code</label>
                        <input type="text" class="form-control"
                               value="<?= str_pad($id, 4, '0', STR_PAD_LEFT) ?>" disabled>
                    </div>

                    <!-- Item Description -->
                    <div class="mb-3">
                        <label for="item_description" class="form-label">
                            Item Description <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="item_description" name="item_description"
                               class="form-control" required maxlength="255"
                               value="<?= htmlspecialchars($item['item_description']) ?>">
                    </div>

                    <!-- Discounted -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="discounted"
                                   name="discounted" role="switch"
                                   <?= $item['discounted'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="discounted">
                                Discountable Item
                            </label>
                        </div>
                    </div>

                    <!-- Quantity by Order -->
                    <div class="mb-3">
                        <label for="quantity_by_order" class="form-label">
                            Quantity by Order <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="quantity_by_order" name="quantity_by_order"
                               class="form-control" required min="1"
                               value="<?= (int) $item['quantity_by_order'] ?>">
                    </div>

                    <!-- Price -->
                    <div class="mb-4">
                        <label for="price" class="form-label">
                            Price (&#8369;) <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="price" name="price"
                               class="form-control" required min="0" step="0.01"
                               value="<?= htmlspecialchars($item['price']) ?>">
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save-fill me-1"></i> Update Item
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../footer.php'; ?>
