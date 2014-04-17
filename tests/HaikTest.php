<?php

class HaikTest extends PHPUnit_Framework_TestCase {

    public function testConvertHtml()
    {
        require('./haik-contents/lib/markdown_parser.php');
        $result = convert_html('test');
        $this->assertInternalType('string', $result);
    }

}
