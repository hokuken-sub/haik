<?php
namespace Hokuken\Haik\Plugin\Repositories;

use Hokuken\HaikMarkdown\Plugin\Repositories\PluginRepositoryInterface;
use Hokuken\Haik\Plugin\PukiwikiPlugin;

class PukiwikiPluginRepository implements PluginRepositoryInterface {


    /**
     * plugin $id is exists?
     * @params string $id plugin id
     * @return boolean
     */
    public function exists($id)
    {
        return exist_plugin($id);
    }

    /**
     * load Plugin by id
     * @params string $id plugin id
     * @return \Hokuken\HaikMarkdown\Plugin\PluginInterface The Plugin
     * @throws InvalidArgumentException when $id was not exist
     */
    public function load($id)
    {
        if ($this->exists($id))
        {
            return new PukiwikiPlugin($id);
        }

        throw new \InvalidArgumentException("A plugin with id=$id was not exist");
    }

    /**
     * get all plugin list
     * @return array of plugin id
     */
    public function getAll()
    {
        $plugin_pathes = glob(PLUGIN_DIR . '*.inc.php');
        $plugins = array_map(function($path)
        {
            return basename($path, '.inc.php');
        }, $plugin_pathes);
        return $plugins;
    }

}
