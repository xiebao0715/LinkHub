<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::logout();
redirect('/admin/login.php');
