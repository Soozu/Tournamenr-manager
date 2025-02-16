<?php
// After successful login verification
$_SESSION['username'] = $user['username'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['avatar'] = $user['avatar']; 