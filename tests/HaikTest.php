<?php

use Hokuken\HaikMarkdown\HaikMarkdown;
use Hokuken\HaikMarkdown\Plugin\Basic\PluginRepository as BasicPluginRepository;
use Hokuken\HaikMarkdown\Plugin\Bootstrap\PluginRepository as BootstrapPluginRepository;
use Hokuken\Haik\Plugin\Repositories\PukiwikiPluginRepository;

class HaikTest extends PHPUnit_Framework_TestCase {

    public function setUp()
    {
        $parser = new HaikMarkdown();
        $pukiwiki_repo = new PukiwikiPluginRepository();
        $basic_repo = new BasicPluginRepository($parser);
        $bootstrap_repo = new BootstrapPluginRepository($parser);
        $parser->registerPluginRepository($pukiwiki_repo)
               ->registerPluginRepository($basic_repo)
               ->registerPluginRepository($bootstrap_repo);
        $this->parser = $parser;
        
    }

    public function testConvertHtml()
    {
        require('./haik-contents/lib/markdown_parser.php');
        $result = convert_html('test');
        $this->assertInternalType('string', $result);
    }

    public function testPriorityOfSameNamePlugin()
    {
        $plugin_name = 'section';
        $expected = 'Hokuken\HaikMarkdown\Plugin\Bootstrap\Section\SectionPlugin';
        $this->assertInstanceOf($expected, $this->parser->loadPlugin($plugin_name));

        $plugin_name = 'deco';
        $expected = 'Hokuken\HaikMarkdown\Plugin\Basic\Deco\DecoPlugin';
        $this->assertInstanceOf($expected, $this->parser->loadPlugin($plugin_name));

        $plugin_name = 'box';
        $expected = 'Hokuken\Haik\Plugin\PukiwikiPlugin';
        $this->assertInstanceOf($expected, $this->parser->loadPlugin($plugin_name));
    }

}
