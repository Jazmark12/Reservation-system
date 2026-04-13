<?php
/**
 * items/index.php – List and search items
 */
require_once __DIR__ . '/../db.php';
$pageTitle = 'Items';
$rootPath  = '../';
include __DIR__ . '/../header.php';

$db = getDB();

// Search
$search = trim($_GET['search'] ?? '');
$params = [];
$sql    = 'SELECT * FROM items';
if ($search !== '') {
    $sql   .= ' WHERE item_description LIKE :s OR item_code = :code';
    $params = [':s' => '%' . $search . '%', ':code' => (int) $search];
}
$sql .= ' ORDER BY item_code ASC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Flash message
session_start();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<div class="page-header">
    <h2><i class="bi bi-box-seam-fill me-2"></i>Items</h2>
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Add Item
    </a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($flash['msg']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Search Form -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-center">
            <div class="col-sm-8 col-md-6">
                <input type="text" name="search" class="form-control"
                       placeholder="Search by item code or description…"
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

<!-- Items Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Description</th>
                        <th class="text-center">Discountable</th>
                        <th class="text-center">Qty by Order</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <?= $search !== '' ? 'No items match your search.' : 'No items found. <a href="add.php">Add the first item</a>.' ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= str_pad($item['item_code'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars($item['item_description']) ?></td>
                        <td class="text-center">
                            <?php if ($item['discounted']): ?>
                                <span class="badge badge-discounted">Yes</span>
                            <?php else: ?>
                                <span class="badge badge-nodiscount">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= number_format($item['quantity_by_order']) ?></td>
                        <td class="text-end">&#8369;<?= number_format($item['price'], 2) ?></td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= $item['item_code'] ?>"
                               class="btn btn-sm btn-outline-primary btn-action" title="Edit">
                                <i class="bi bi-pencil-fill"></i>
                            </a>
                            <a href="delete.php?id=<?= $item['item_code'] ?>"
                               class="btn btn-sm btn-outline-danger btn-action ms-1"
                               title="Delete"
                               onclick="return confirm('Delete this item? This cannot be undone.');">
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
