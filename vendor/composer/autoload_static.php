<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf3ff04295b8b721d618b85bc30fb7b5d
{
    public static $classMap = array (
        'IP2Location\\Database' => __DIR__ . '/..' . '/ip2location/ip2location-php/IP2Location.php',
        'IP2Location\\WebService' => __DIR__ . '/..' . '/ip2location/ip2location-php/IP2Location.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInitf3ff04295b8b721d618b85bc30fb7b5d::$classMap;

        }, null, ClassLoader::class);
    }
}
