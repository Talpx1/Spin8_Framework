<?php declare(strict_types=1);

namespace Spin8\Configs;
use Spin8\Configs\Exceptions\ConfigFileNotReadableException;
use Spin8\Utils\Guards\GuardAgainstEmptyParameter;

class ConfigRepository{
    /**
     * @var string[]
     */
    protected array $config_files = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $configs = [];

    public static ?self $instance = null;

    public function clear(): void {
        $this->config_files = [];
        $this->configs = [];
    }
    
    public static function instance(): self {
        if(is_null(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    protected function __construct(){}

    public function loadAll(): void {
        $this->discoverFiles();

        foreach($this->config_files as $config_file) {
            $this->loadFile($config_file);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAll(): array {
        return $this->configs;
    }

    public function set(string $file_name, string $config_key, mixed $value): void {
        GuardAgainstEmptyParameter::check($file_name);
        GuardAgainstEmptyParameter::check($config_key);

        $this->configs[$file_name][$config_key] = $value;
    }

    protected function loadFile(string $config_file): void {
        if (!is_readable($config_file)) {
            throw new ConfigFileNotReadableException($config_file);
        }

        $config_file_name = pathinfo($config_file, PATHINFO_FILENAME);
        $this->configs[$config_file_name] = [];

        /**
         * @var array<string, mixed>
         */
        $configs = require $config_file;
        
        /**
         * @var string $config_key
         */
        foreach($configs as $config_key => $config_value) {
            $this->configs[$config_file_name][$config_key] = $config_value;
        }
    }

    protected function discoverFiles(): void {
        $this->config_files = \Safe\glob(configPath()."*.php");
    }

}
