<?php
namespace Hokuken\Haik\Page;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\ParseException;

class YamlPageMeta implements PageMetaInterface {

    /** @var string page name */
    protected $page;

    /** @var mixed meta data of the page*/
    protected $data;

    public function __construct($page)
    {
        $this->page = $page;
        $this->data = array();
    }

    /**
     * Read meta data of the page
     *
     * @return mixed meta data array of the page
     */
    public function read()
    {
        $file_path = $this->getFilePath();
        try {
            $this->data = Yaml::parse(file_get_contents($file_path));
        } catch (ParseException $e) {
            $this->data = array();
        }
        return $this->data;
    }

    public function getFilePath()
    {
        return META_DIR . encode($this->page) . '.yml';
    }

    /**
     * Get meta data of specified key e.g group.value
     *
     * @param string $key
     * @param mixed $default_value when $key is not found then return this value
     * @return mixed specified meta data
     */
    public function get($key, $default_value = NULL)
    {
        $data = $this->data;
        $value = $this->recurseKeys(explode('.', $key), $data);

        if ($value === NULL) return $default_value;
        return $value;
    }

    protected function recurseKeys(array $keys, array $array){
        $key = array_shift($keys);
        if(!isset($array[$key])) return null;
        return empty($keys) ?
            $array[$key]:
            $this->recurseKeys($keys,$array[$key]);
    }

    /**
     * Get all meta data of the page
     *
     * @return mixed meta data
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Set meta data of specified key e.g. group.value
     *
     * @param string $key
     * @param mixed $value
     * @return $this for method chain
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key, 2);
        if (count($keys) === 1)
        {
            $this->data[$key] = $value;
            return $this;
        }

        list($group, $key) = $keys;
        if ( ! is_array($this->data[$group]))
        {
            $this->data[$group] = array();
        }
        $this->data[$group][$key] = $value;

        return $this;
    }

    /**
     * Set or merge providing array to $data
     *
     * @param mixed $data meta data to set
     * @param boolean $overwrite flag of overwrite or merge data. Default is false(merge).
     */
    public function setAll($data, $overwrite = false)
    {
        if ($overwrite)
        {
            $this->data = $data;
        }
        else
        {
            $this->data = array_merge_deep($this->data, $data);
        }
        return $this;
    }

    /**
     * Save meta data
     *
     * @return save is successed?
     */
    public function save()
    {
        $file_path = $this->getFilePath();
        $yaml = Yaml::dump($this->data);
        return file_put_contents($file_path, $yaml);
    }

    /**
     * Delete meta data relating the page
     *
     * @return delete is successed?
     */
    public function delete()
    {
        $file_path = $this->getFilePath();
        return unlink($file_path);
    }

}
