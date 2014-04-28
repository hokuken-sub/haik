<?php
namespace Hokuken\Haik\Markdown;

use Hokuken\HaikMarkdown\HaikMarkdown as SuperHaikMarkdown;
class HaikMarkdown extends SuperHaikMarkdown {

    public function __construct()
    {
        parent::__construct();
        $this->nested_url_parenthesis_re = 
            str_repeat('(?>[^()"]+|\(', $this->nested_url_parenthesis_depth).
            str_repeat('(?>\)))*', $this->nested_url_parenthesis_depth);
    }

}
