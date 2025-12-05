<?php
// auth_check.php
// This file provides authentication functions for the system

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in (either admin or student)
 */
function isLoggedIn() {
    return isset($_SESSION['role']);
}

/**
 * Require that the user is an admin
 * Redirects to login if not authenticated or not admin
 */
function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header('Location: login.php');
        exit;
    }
}

/**
 * Require that the user is a student
 * Redirects to login if not authenticated or not student
 */
function requireStudent() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
        header('Location: login.php');
        exit;
    }
}

/**
 * Get the current user's role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get the current user's email
 */
function getUserEmail() {
    return $_SESSION['email'] ?? null;
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if current user is student
 */
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}
?>