<?php
/**
 * @package     Innosend_Integration
 * @author      Henk Valk (henk@falconmedia.nl)
 * @version     1.0.0
 * @date        2025-11-28
 *
 * Copyright (c) 2025 Falcon Media (www.falconmedia.nl)
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

// Explicitly require interfaces to ensure they're loaded during DI compilation
$interfaceDir = __DIR__ . '/Api';
if (is_dir($interfaceDir)) {
    $interfaces = [
        'CarrierInterface.php',
        'ClientInterface.php',
        'OrderInterface.php',
        'PickupPointInterface.php',
    ];
    
    foreach ($interfaces as $interfaceFile) {
        $interfacePath = $interfaceDir . '/' . $interfaceFile;
        if (file_exists($interfacePath)) {
            require_once $interfacePath;
        }
    }
}

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Innosend_Integration',
    __DIR__
);
