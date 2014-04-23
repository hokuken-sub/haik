<?php
namespace Hokuken\Haik\Plugin;

use Hokuken\HaikMarkdown\Plugin\PluginInterface;

class PukiwikiPlugin implements PluginInterface {

    protected $pluginId;

    /**
     * Constructor
     *
     * @param string $plugin_id name of pukiwiki-plugin
     * @throws \InvalidArgumentException when plugin is not exist.
     */
    public function __construct($plugin_id)
    {
        if ( ! \exist_plugin($plugin_id))
        {
            throw new \InvalidArgumentException("A plugin with id=$plugin_id was not exist");
        }
        $this->pluginId = $plugin_id;
    }

    /**
     * convert text to inline element
     * @params array $params
     * @params string $body when {...} was set
     * @return string converted HTML string
     * @throws RuntimeException when unimplement
     */
    public function inline($params = array(), $body = '')
    {
        if (\do_plugin_init($this->pluginId) === FALSE)
        {
            throw new \RuntimeException('plugin id='.$this->pluginId.' init failed.');
        }

        $func = '\plugin_' . $this->pluginId . '_inline';
        if (function_exists($func))
        {
            array_push($params, $body);
            return call_user_func_array($func, $params);
        }
        throw new \RuntimeException('plugin id='.$this->pluginId.' does not have inline function.');
    }

    /**
     * convert text to block element
     * @params array $params
     * @params string $body when :::\n...\n::: was set
     * @return string converted HTML string
     * @throws RuntimeException when unimplement
     */
    public function convert($params = array(), $body = '')
    {
        if (\do_plugin_init($this->pluginId) === FALSE)
        {
            throw new \RuntimeException('plugin id='.$this->pluginId.' init failed.');
        }

        $func = '\plugin_' . $this->pluginId . '_convert';
        if (function_exists($func))
        {
            array_push($params, $body);
            return call_user_func_array($func, $params);
        }
        throw new \RuntimeException('plugin id='.$this->pluginId.' does not have inline function.');
    }

}
