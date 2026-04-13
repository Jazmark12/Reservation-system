<?php
/**
 * reservations/delete.php – Delete a reservation (cascade deletes items)
 */
session_start();
require_once __DIR__ . '/../db.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$del = $db->prepare('DELETE FROM reservations WHERE reservation_id = :id');
$del->execute([':id' => $id]);

if ($del->rowCount()) {
    $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Reservation #' . $id . ' deleted.'];
} else {
    $_SESSION['flash'] = ['type' => 'warning', 'msg' => 'Reservation not found.'];
}

header('Location: index.php');
exit;
