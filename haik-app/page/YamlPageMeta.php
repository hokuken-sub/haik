<?php
namespace Hokuken\Haik\Page;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\ParseException;

class YamlPageMeta implements PageMetaInterface {

    /** @var string page name */
    protected $page;

    /** @var mixed meta data of the page*/
    protected $data;

    /**
     * Constructor
     *
     * @param string $page page name
     * @param boolean $set_data when true then read and set data of the $page. Default is true
     */
    public function __construct($page, $set_data = true)
    {
        $this->page = $page;
        $this->data = array();

        if ($set_data)
            $this->data = $this->read();
    }

    /**
     * Read meta data of the page
     *
     * @return mixed meta data array of the page
     * @throws FileNotFoundException
     */
    public function read()
    {
        $file_path = $this->getFilePath();
        if ( ! file_exists($file_path))
            return array();

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
        $keys = explode('.', $key, 2);
        if (count($keys) === 1)
        {
            if (isset($this->data[$key]))
                return $this->data[$key];
            else
                return $default_value;
        }

        list($group, $key) = $keys;
        if (isset($this->data[$group][$key]))
        {
            return $this->data[$group][$key];
        }
        return null;
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
        if ( ! isset($this->data[$group]))
            $this->data[$group] = array();
        
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
