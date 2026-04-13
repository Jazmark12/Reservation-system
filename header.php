<?php
/**
 * header.php – Common page header / navigation
 * Usage: include at top of every page, set $pageTitle before including.
 */
$pageTitle = $pageTitle ?? 'Reservation &amp; Billing System';
$rootPath  = $rootPath  ?? '';   // e.g. '../' when inside a sub-folder
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> – Reservation &amp; Billing System</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">
    <!-- Custom styles -->
    <link href="<?= $rootPath ?>css/style.css" rel="stylesheet">
</head>
<body>

<!-- Top Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="<?= $rootPath ?>index.php">
            <i class="bi bi-calendar2-check-fill me-2"></i>Reservation &amp; Billing System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?= $rootPath ?>index.php">
                        <i class="bi bi-house-fill me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $rootPath ?>items/index.php">
                        <i class="bi bi-box-seam-fill me-1"></i>Items
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $rootPath ?>reservations/index.php">
                        <i class="bi bi-journal-bookmark-fill me-1"></i>Reservations
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Page Content Wrapper -->
<div class="container-fluid py-4 px-4">
