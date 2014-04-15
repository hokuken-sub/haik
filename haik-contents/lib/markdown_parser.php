<?php

use Toiee\HaikMarkdown\HaikMarkdown;
use Toiee\HaikMarkdown\Plugin\Repositories\BasicPluginRepository;
use Toiee\HaikMarkdown\Plugin\Bootstrap\PluginRepository as BootstrapPluginRepository;

if ( ! function_exists('convert_html'))
{

    function convert_html($text)
    {
        static $parser = null;
        
        if ($parser === null)
        {
            $parser = new HaikMarkdown();
            $basic_repo = new BasicPluginRepository($parser);
            $bootstrap_repo = new BootstrapPluginRepository($parser);
            $parser->registerPluginRepository($basic_repo)
                   ->registerPluginRepository($bootstrap_repo);
        }

        if (is_array($text))
        {
            $text = join('', $text);
        }
        return $parser->transform($text);
    }

}
