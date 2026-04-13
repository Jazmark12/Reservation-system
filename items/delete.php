<?php
/**
 * items/delete.php – Delete an item (GET request with confirm dialog)
 */
session_start();
require_once __DIR__ . '/../db.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

// Check if the item is referenced in any reservation
$ref = $db->prepare('SELECT COUNT(*) FROM reservation_items WHERE item_code = :id');
$ref->execute([':id' => $id]);
if ((int) $ref->fetchColumn() > 0) {
    $_SESSION['flash'] = [
        'type' => 'danger',
        'msg'  => 'Cannot delete: this item is used in one or more reservations.',
    ];
} else {
    $del = $db->prepare('DELETE FROM items WHERE item_code = :id');
    $del->execute([':id' => $id]);
    if ($del->rowCount()) {
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Item deleted successfully.'];
    } else {
        $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Item not found.'];
    }
}

header('Location: index.php');
exit;
