<?php
require_once __DIR__ . '/includes/auth.php';

logout_user();
flash('You’ve been signed out.');
redirect('index.php');
