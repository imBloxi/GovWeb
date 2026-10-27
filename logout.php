<?php
require __DIR__ . '/app/bootstrap.php';

// Sign-out is a POST (checked for CSRF in bootstrap) so links can't log people out.
if (is_post()) {
    logout();
    flash('success', 'You signed out.');
}
redirect('login.php');
