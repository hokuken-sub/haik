<?php

use Hokuken\Haik\Page\YamlPageMeta;
use Symfony\Component\Yaml\Yaml;

class YamlPageMetaTest extends PHPUnit_Framework_TestCase {

    protected $testData;
    public function setUp()
    {
        $page = 'FrontPage';
        $yaml_file = './tests/data/page_meta.yml';
        $this->testData = array(
            'title' => 'TestTitle',
            'template_name' => 'top',
            'description' => "multi\nline\ndescription",
            'group' => array(
                'key' => 'value'
            )
        );
        $yaml = Yaml::dump($this->testData);
        file_put_contents($yaml_file, $yaml);

        $mock = Mockery::mock('Hokuken\Haik\Page\YamlPageMeta[getFilePath]', array($page));
        $mock->shouldReceive('getFilePath')->andReturn($yaml_file);
        $this->pageMeta = $mock;
    }

    public function testRead()
    {
        $data = $this->pageMeta->read();
        $this->assertEquals($this->testData, $data);
    }

    public function testGet()
    {
        $this->pageMeta->setAll($this->pageMeta->read(), true);

        $value = $this->pageMeta->get('title');
        $this->assertEquals('TestTitle', $value);

        $value = $this->pageMeta->get('unknown');
        $this->assertNull($value);

        $value = $this->pageMeta->get('unknown', 'foobar');
        $this->assertEquals('foobar', $value);

        $value = $this->pageMeta->get('group.key');
        $this->assertEquals('value', $value);

        $value = $this->pageMeta->get('group.unknown');
        $this->assertNull($value);
        
        $value = $this->pageMeta->get('unknowngroup.unknown');
        $this->assertNull($value);
    }
}
