<?php

use Hokuken\Haik\Plugin\Repositories\PukiwikiPluginRepository;

class PukiwikiPluginRepositoryTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $this->repository = new PukiwikiPluginRepository();
    }

    public function testExist()
    {
        $result = $this->repository->exists('deco');
        $this->assertTrue($result);

        $result = $this->repository->exists('unknown');
        $this->assertFalse($result);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionWhenUnknownPluginLoaded()
    {
        $this->repository->load('unknown');
    }

    public function testLoadPluginInterface()
    {
        $plugin = $this->repository->load('deco');
        $this->assertInstanceOf('Toiee\HaikMarkdown\Plugin\PluginInterface', $plugin);
    }

    public function testGetAll()
    {
        $plugins = $this->repository->getAll();

        $plugin_pathes = glob(PLUGIN_DIR . '*.inc.php');
        $expected_plugins = array_map(function($path)
        {
            return basename($path, '.inc.php');
        }, $plugin_pathes);
        $this->assertEquals($expected_plugins, $plugins);
    }

}
