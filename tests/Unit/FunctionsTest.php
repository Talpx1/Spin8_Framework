<?php

namespace Spin8\Tests\Unit;

use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use Spin8\Configs\Enums\Environments;
use Spin8\Container\Interfaces\Spin8ContainerContract;
use Spin8\Exceptions\EnvironmentVariableNotFoundException;
use Spin8\Facades\Config;
use Spin8\Spin8;
use Spin8\Tests\TestCase;
use Spin8\WP\Plugin;
use WP_Mock;


#[CoversFunction("container")]
#[CoversFunction("spin8")]
#[CoversFunction("plugin")]
#[CoversFunction("adminAsset")]
#[CoversFunction("slugify")]
#[CoversFunction("buildSettings")]
#[CoversFunction("config")]
#[CoversFunction("isRunningTest")]
#[CoversFunction("rootPath")]
#[CoversFunction("assetsPath")]
#[CoversFunction("frameworkPath")]
#[CoversFunction("frameworkSrcPath")]
#[CoversFunction("configPath")]
#[CoversFunction("storagePath")]
#[CoversFunction("frameworkTempPath")]
#[CoversFunction("pluginPath")]
#[CoversFunction("vendorPath")]
#[CoversFunction("pluginFilePath")]
#[CoversFunction("environment")]
#[CoversFunction("env")]
#[CoversFunction("envOr")]
#[CoversFunction("wpVersion")]
final class FunctionsTest extends TestCase {

    #[Test]
    public function test_container_helper_returns_container_instance(): void {        
        $this->assertInstanceOf(Spin8ContainerContract::class, container());        
        $this->assertSame($this->spin8->container, container());        
    }

    #[Test]
    public function test_spin8_helper_returns_container_instance(): void {        
        $this->assertInstanceOf(Spin8::class, spin8());        
        $this->assertSame($this->spin8, spin8());        
    }

    #[Test]
    public function test_plugin_helper_returns_plugin_instance(): void {        
        $this->assertInstanceOf(Plugin::class, plugin());        
        $this->assertSame($this->plugin, plugin());
    }

    #[Test]
    public function test_adminAsset_helper_renders_admins_asset(): void { 
        $path = $this->assets_path->getChild('admin');
        // @phpstan-ignore-next-line
        vfsStream::newFile("test_asset.php")->at($path)->setContent("test");

        \Safe\ob_start();
        adminAsset('test_asset');
        $content = ob_get_clean();

        // @phpstan-ignore-next-line
        $this->assertStringContainsString('test', $content);
    }

    #[Test]
    public function test_adminAsset_helper_renders_admins_asset_if_path_is_provided_with_dir_separator_at_the_beginning(): void { 
        $path = $this->assets_path->getChild('admin');
        // @phpstan-ignore-next-line
        vfsStream::newFile("test_asset.php")->at($path)->setContent("test");

        \Safe\ob_start();
        adminAsset(DIRECTORY_SEPARATOR.'test_asset');
        $content = ob_get_clean();

        // @phpstan-ignore-next-line
        $this->assertStringContainsString('test', $content);
    }

    #[Test]
    public function test_adminAsset_helper_throws_InvalidArgumentException_if_empty_string_is_passed(): void { 
        $this->expectException(InvalidArgumentException::class);
        adminAsset('');
    }

    #[Test]
    public function test_slugify_helper_returns_string_in_slug_format(): void { 
        WP_Mock::userFunction('remove_accents')->once()->with("TÈST 1_2 3 !")->andReturn('TEST 1_2 3 !');
        WP_Mock::userFunction('sanitize_title_with_dashes')->once()->with("TEST 1_2 3 !", "", 'save')->andReturn('test-1-2-3');
        $this->assertEquals("test-1-2-3", slugify("TÈST 1_2 3 !"));
    }

    #[Test]
    public function test_slugify_helper_throws_InvalidArgumentException_if_empty_string_is_passed(): void { 
        $this->expectException(InvalidArgumentException::class);
        slugify('');
    }

    #[Test]
    public function test_buildSettings_helper_throws_InvalidArgumentException_if_page_slug_is_an_empty_string(): void {  
        $this->expectException(InvalidArgumentException::class);      
        buildSettings('');
    }

    #[Test]
    public function test_buildSettings_helper_throws_InvalidArgumentException_if_submit_text_is_an_empty_string(): void {  
        $this->expectException(InvalidArgumentException::class);      
        buildSettings('test', '');
    }

    #[Test]
    public function test_buildSettings_helper_provide_settings_form(): void { 
        $fake_plugin_name = 'test_plugin';
        $fake_page_slug = 'test_page_slug';

        Config::set('plugin.name', $fake_plugin_name);

        WP_Mock::userFunction('settings_errors')->once()->with("{$fake_plugin_name}_message");
        WP_Mock::userFunction('settings_fields')->once()->with($fake_page_slug);
        WP_Mock::userFunction('do_settings_sections')->once()->with($fake_page_slug);
        WP_Mock::userFunction('__')->once()->with('Save')->andReturn('Save');
        WP_Mock::userFunction('submit_button')->once()->with('Save');

        $this->assertStringContainsString("<form action='options.php' method='post'>", buildSettings($fake_page_slug));
    }

    #[Test]
    public function test_config_helper_returns_specified_configuration(): void {        
        Config::set('test.cfg_test', '123');

        $this->assertEquals('123', config('test.cfg_test'));
    }

    #[Test]
    public function test_config_helper_returns_specified_fallback_if_configuration_key_cant_be_found(): void {        
        Config::set('test.cfg_test', '123');

        $this->assertEquals('fallback', config('test.non_existent', 'fallback'));
    }

    #[Test]
    public function test_config_helper_returns_dot_notation_specified_configuration(): void {        
        Config::set('test.a.b.c', 'd');

        $this->assertEquals('d', config('test.a.b.c', 'fallback'));
    }

    #[Test]
    public function test_config_helper_massively_sets_config_if_first_param_is_Array(): void {        
        config(['a.b.c'=>'d', '1.2.3'=>4]);

        $this->assertEquals('d', config('a.b.c'));
        $this->assertEquals(4, config('1.2.3'));
    }

    #[Test]
    public function test_config_helper_returns_specified_fallback_if_configuration_file_cant_be_found(): void {        
        //no configs exists right now, so every config we try to fetch is going to fallback
        $this->assertEquals('fallback', config('test.cfg_test', 'fallback'));
    }

    #[Test]
    public function test_config_helper_returns_null_if_configuration_file_cant_be_found_and_no_fallback_is_specified(): void {        
        //no configs exists right now, so every config we try to fetch is going to fallback
        $this->assertNull(config('test.cfg_test'));
    }

    #[Test]
    public function test_config_helper_returns_null_if_configuration_key_cant_be_found_and_no_fallback_is_specified(): void {        
        $this->makeConfigFile('test', ['a'=>1]);
        $this->assertNull(config('test.cfg_test'));
    }

    #[Test]
    public function test_config_helper_throws_InvalidArgumentException_if_config_key_is_an_empty_string(): void {  
        $this->expectException(InvalidArgumentException::class);      
        config('');
    }

    #[Test]
    #[BackupGlobals(true)]
    public function test_isRunningTest_helper_returns_false_when_not_running_test(): void {        
        //$_ENV['TESTING'] = '1' gets set by PHPUnit
        //unsetting $_ENV['TESTING'] to simulate a non testing environment
        unset($_ENV['TESTING']);
        $this->assertFalse(isRunningTest());

        //changing $_ENV['TESTING'] to a value different than '1', should not be considered a testing environment
        $_ENV['TESTING'] = 'test';
        $this->assertFalse(isRunningTest());
    }

    #[Test]
    public function test_isRunningTest_helper_returns_true_when_running_test(): void {        
        //$_ENV['TESTING'] = '1' gets set by PHPUnit;
        $this->assertTrue(isRunningTest());
    }

    #[Test]
    public function test_rootPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root"), rootPath());
    }

    #[Test]
    public function test_rootPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/test"), rootPath("test"));
    }

    #[Test]
    public function test_rootPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/test"), rootPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_rootPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/test"), rootPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_assetsPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/assets"), assetsPath());
    }

    #[Test]
    public function test_assetsPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/assets/test"), assetsPath("test"));
    }

    #[Test]
    public function test_assetsPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/assets/test"), assetsPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_assetsPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/assets/test"), assetsPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_frameworkPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework"), frameworkPath());
    }

    #[Test]
    public function test_frameworkPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/test"), frameworkPath("test"));
    }

    #[Test]
    public function test_frameworkPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/test"), frameworkPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_frameworkPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/test"), frameworkPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_frameworkSrcPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/src"), frameworkSrcPath());
    }

    #[Test]
    public function test_frameworkSrcPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/src/test"), frameworkSrcPath("test"));
    }

    #[Test]
    public function test_frameworkSrcPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/src/test"), frameworkSrcPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_frameworkSrcPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/spin8/framework/src/test"), frameworkSrcPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_frameworkTempPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/storage/framework/temp"), frameworkTempPath());
    }

    #[Test]
    public function test_frameworkTempPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/storage/framework/temp/test"), frameworkTempPath("test"));
    }

    #[Test]
    public function test_frameworkTempPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/storage/framework/temp/test"), frameworkTempPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_frameworkTempPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/storage/framework/temp/test"), frameworkTempPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_configPath_helper_points_to_right_directory(): void {
        $this->assertEquals(vfsStream::url("root/configs"), configPath());
    }

    #[Test]
    public function test_configPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/configs/test"), configPath("test"));
    }

    #[Test]
    public function test_configPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/configs/test"), configPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_configPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/configs/test"), configPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_storagePath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/storage"), storagePath());
    }

    #[Test]
    public function test_storagePath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/storage/test"), storagePath("test"));
    }

    #[Test]
    public function test_storagePath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/storage/test"), storagePath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_storagePath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/storage/test"), storagePath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_pluginPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/plugin"), pluginPath());
    }

    #[Test]
    public function test_pluginPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/plugin/test"), pluginPath("test"));
    }

    #[Test]
    public function test_pluginPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/plugin/test"), pluginPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_pluginPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/plugin/test"), pluginPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_vendorPath_helper_points_to_right_directory(): void {        
        $this->assertEquals(vfsStream::url("root/vendor"), vendorPath());
    }

    #[Test]
    public function test_vendorPath_helper_appends_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/test"), vendorPath("test"));
    }

    #[Test]
    public function test_vendorPath_helper_correctly_appends_dir_separator_prefixed_path_passed_in_param(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/test"), vendorPath(DIRECTORY_SEPARATOR."test"));
    }

    #[Test]
    public function test_vendorPath_helper_correctly_formatted_path_when_path_passed_in_param_eds_with_dir_separator(): void {        
        $this->assertEquals(vfsStream::url("root/vendor/test"), vendorPath("test".DIRECTORY_SEPARATOR));
    }

    #[Test]
    public function test_pluginFilePath_returns_the_plugin_main_file_path(): void {   
        Config::set('plugin.slug', 'test_123');
        $this->assertEquals(vfsStream::url("root/test_123.php"), pluginFilePath());
    }

    #[Test]
    #[BackupGlobals(true)]
    public function test_environment_helper_returns_right_environment(): void {
        $this->assertTrue(array_key_exists("TESTING", $_ENV) && $_ENV['TESTING'] === '1');
        $this->assertTrue(isRunningTest());

        $this->assertEquals(Environments::TESTING, environment());

        unset($_ENV['TESTING']);
        $this->assertFalse(isRunningTest());
        $this->assertFalse(array_key_exists("TESTING", $_ENV) && $_ENV['TESTING'] === '1');

        Config::set('environment.environment', Environments::PRODUCTION);
        $this->assertEquals(config('environment.environment'), environment());
        $this->assertEquals(Environments::PRODUCTION, environment());

        Config::set('environment.environment', Environments::LOCAL);
        $this->assertEquals(Environments::LOCAL, environment());
    }
    
    #[Test]
    #[BackupGlobals(true)]
    public function test_env_helper_returns_an_environment_variable_value(): void { 
        $_ENV['TEST'] = 'test_val';

        $this->assertEquals('test_val', env("TEST"));
    }

    #[Test]
    public function test_env_helper_throws_InvalidArgumentException_if_empty_string_is_passed(): void { 
        $this->expectException(InvalidArgumentException::class);
        env('');
    }

    #[Test]
    public function test_env_helper_throws_EnvironmentVariableNotFoundException_if_passed_env_var_name_does_not_exists_in_env(): void { 
        $this->expectException(EnvironmentVariableNotFoundException::class);
        env('TEST');
    }

    #[Test]
    #[BackupGlobals(true)]
    public function test_envOr_helper_returns_an_environment_variable_value(): void { 
        $_ENV['TEST'] = 'test_val';

        $this->assertEquals('test_val', envOr("TEST"));
    }

    #[Test]
    public function test_envOr_helper_throws_InvalidArgumentException_if_empty_string_is_passed(): void { 
        $this->expectException(InvalidArgumentException::class);
        envOr('');
    }

    #[Test]
    public function test_envOr_helper_returns_passed_default_value_if_env_var_name_does_not_exists_in_env(): void { 
        $this->assertEquals("fallback", envOr('TEST', "fallback"));
    }

    #[Test]
    public function test_envOr_helper_returns_null_if_env_var_name_does_not_exists_in_env_and_no_fallback_value_id_provided(): void { 
        $this->assertNull(envOr('TEST'));
    }

    #[Test]
    public function test_wpVersion_helper_returns_wordpress_version(): void { 
        WP_Mock::userFunction('get_bloginfo')->once()->with("version")->andReturn('1.2.3');
        $this->assertEquals("1.2.3", wpVersion());
    }

}
