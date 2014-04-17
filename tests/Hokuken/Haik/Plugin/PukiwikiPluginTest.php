<?php

use Hokuken\Haik\Plugin\PukiwikiPlugin;

class PukiwikiPluginTest extends PHPUnit_Framework_TestCase {

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testThrowsExceptionWhenUnknownPluginCreated()
    {
        $unknown_plugin = 'hoge';
        new PukiwikiPlugin($unknown_plugin);
    }

    public function testInline()
    {
        $plugin = new PukiwikiPlugin('button');
        $params = [
            'http://www.example.com/',
            'primary',
        ];
        $body = 'Body';
        $result = $plugin->inline($params, $body);
        $this->assertInternalType('string', $result);

        $expected = [
            'tag' => 'a',
            'attributes' => [
                'class' => 'btn btn-primary'
            ]
        ];
        $this->assertTag($expected, $result);
    }

    public function testConvert()
    {
        $plugin = new PukiwikiPlugin('clear');
        $params = [];
        $body = '';
        $result = $plugin->convert($params, $body);
        $this->assertInternalType('string', $result);

        $expected = [
            'tag' => 'div',
            'attributes' => [
                'class' => 'clear'
            ]
        ];
        $this->assertTag($expected, $result);
    }

}
