<?php
// Incluír al inicio de páginas que requieran autenticación
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /bonos/login.php');
    exit;
}
