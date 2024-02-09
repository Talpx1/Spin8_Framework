<?php declare(strict_types=1);

namespace Spin8\WP;
use RuntimeException;

class Plugin {//TODO: test

    public function registerLifecycleHooks(): void {
        register_activation_hook(pluginFilePath(), [$this, 'onActivation']);
        register_deactivation_hook(pluginFilePath(), [$this, 'onDeactivation']);
    }

    protected function onActivation(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        if (!version_compare(PHP_VERSION, config('enviroment', 'min_php_version'), '>=')) {
            throw new RuntimeException(sprintf(__('In order to run this plugin, PHP version %s (or higher) is required. Your current PHP version is %s. Please update PHP.'), config('environment', 'min_php_version'), PHP_VERSION));
        }

        if (!version_compare(get_bloginfo('version'), config('environment', 'min_wordpress_version'), '>=')) {
            throw new RuntimeException(sprintf(__('In order to run this plugin, WordPress version %s (or higher) is required. Your current WordPress version is %s. Please update WordPress.'), config('environment', 'min_wordpress_version'), wpVersion()));
        }

        $this->requireIfExists(pluginPath().'menus.php');
        $this->requireIfExists(pluginPath().'settings.php');
        $this->requireIfExists(pluginPath().'activation.php');

    }

    protected function onDeactivation(): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }

        $this->requireIfExists(pluginPath().'deactivation.php');
    }

    protected function requireIfExists(string $path, bool $use_require_once = true): void {
        if(file_exists($path)) {
            $use_require_once ? require_once $path : require $path;
        }
    }

}