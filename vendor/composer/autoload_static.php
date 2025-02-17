<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInite30e0abee409f8d4aa1c25da5b134d84
{
    public static $prefixLengthsPsr4 = array (
        'R' => 
        array (
            'RobertDevore\\WPComCheck\\' => 24,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'RobertDevore\\WPComCheck\\' => 
        array (
            0 => __DIR__ . '/..' . '/robertdevore/wpcom-check/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInite30e0abee409f8d4aa1c25da5b134d84::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInite30e0abee409f8d4aa1c25da5b134d84::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInite30e0abee409f8d4aa1c25da5b134d84::$classMap;

        }, null, ClassLoader::class);
    }
}
