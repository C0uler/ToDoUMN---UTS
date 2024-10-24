<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit3451fbf81f77de76c99eeed11a6432e9
{
    public static $prefixLengthsPsr4 = array (
        'U' => 
        array (
            'User\\ProjectUtsLab\\' => 19,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'User\\ProjectUtsLab\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit3451fbf81f77de76c99eeed11a6432e9::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit3451fbf81f77de76c99eeed11a6432e9::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit3451fbf81f77de76c99eeed11a6432e9::$classMap;

        }, null, ClassLoader::class);
    }
}