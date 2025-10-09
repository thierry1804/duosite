<?php

if (file_exists(dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php')) {
    require dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php';
}

// Preload commonly used classes
if (file_exists(dirname(__DIR__).'/var/cache/prod/srcApp_KernelProdContainer.php')) {
    require dirname(__DIR__).'/var/cache/prod/srcApp_KernelProdContainer.php';
}

// Preload Doctrine entities
$entityDir = dirname(__DIR__).'/src/Entity';
if (is_dir($entityDir)) {
    $files = glob($entityDir . '/*.php');
    foreach ($files as $file) {
        require_once $file;
    }
}

// Preload controllers
$controllerDir = dirname(__DIR__).'/src/Controller';
if (is_dir($controllerDir)) {
    $files = glob($controllerDir . '/*.php');
    foreach ($files as $file) {
        require_once $file;
    }
}
