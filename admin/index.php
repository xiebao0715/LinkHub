<?php
require_once __DIR__ . '/../includes/bootstrap.php';
if (Auth::check()) {
    redirect('/admin/dashboard.php');
}
redirect('/admin/login.php');
