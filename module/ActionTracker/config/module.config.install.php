<?php

return [
    'compatable' => '2.2.3',
    'version' => '1.0.0',
    'vendor' => 'eSASe',
    'vendor_email' => 'alexermashev@gmail.com',
    'description' => 'Module allows to track all site changes',
    'system_requirements' => [
        'php_extensions' => [
        ],
        'php_settings' => [
        ],
        'php_enabled_functions' => [
        ],
        'php_version' => null
    ],
    'module_depends' => [
    ],
    'clear_caches' => [
        'setting'       => true,
        'time_zone'     => false,
        'admin_menu'    => true,
        'js_cache'      => false,
        'css_cache'     => false,
        'layout'        => false,
        'localization'  => false,
        'page'          => false,
        'user'          => false,
        'xmlrpc'        => false
    ],
    'resources' => [
    ],
    'install_sql' => null,
    'install_intro' => null,
    'uninstall_sql' => null,
    'uninstall_intro' => null,
    'layout_path' => 'actiontracker'
];