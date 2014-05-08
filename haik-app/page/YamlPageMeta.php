<?php
namespace Hokuken\Haik\Page;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\ParseException;

class YamlPageMeta implements PageMetaInterface {

    /** @var string page name */
    protected $page;

    /** @var mixed meta data of the page*/
    protected $data;

    protected $isDirty;

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
        $this->isDirty = false;

        if ($set_data)
            $this->data = $this->read();
    }

    public function getPage()
    {
        return $this->page;
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
        $this->isDirty = false;
        return $this->data;
    }

    public function getFilePath()
    {
        return META_DIR . encode($this->page) . '.yml';
    }

    public function has($key)
    {
        $default_value = microtime();
        return $this->get($key, $default_value) !== $default_value;
    }

    protected function parseKeys($keys)
    {
        $group = false;
        $keys = explode('.', $keys, 2);
        $key = array_pop($keys);
        if (count($keys) > 0)
        {
            $group = array_pop($keys);
        }

        return array($group, $key);
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
        list($group, $key) = $this->parseKeys($key);
        if ($group === false)
        {
            if (isset($this->data[$key]))
                return $this->data[$key];
            else
                return $default_value;
        }
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

    public function toYaml()
    {
        return Yaml::dump($this->data, 2);
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
        list($group, $key) = $this->parseKeys($key);
        if ($group === false)
        {
            if ( ! isset($this->data[$key]) OR (isset($this->data[$key]) && $this->data[$key] !== $value))
            {
                $this->data[$key] = $value;
                $this->isDirty = true;
            }
            return $this;
        }

        if ( ! isset($this->data[$group]))
            $this->data[$group] = array();
        
        if ( ! is_array($this->data[$group]))
        {
            $this->data[$group] = array();
        }
        if ( ! isset($this->data[$group][$key]) OR (isset($this->data[$group][$key]) && $this->data[$group][$key] !== $value))
        {
            $this->data[$group][$key] = $value;
            $this->isDirty = true;
        }

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
        if ( ! $overwrite)
        {
            $data = array_merge_deep($this->data, $data);
        }
        if ($data === $this->data) return $this;

        $this->data = $data;
        $this->isDirty = true;
        return $this;
    }

    public function remove($key)
    {
        list($group, $key) = $this->parseKeys($key);
        if ($group === false)
        {
            unset($this->data[$key]);
            $this->isDirty = true;
            return $this;
        }

        if (isset($this->data[$group][$key]))
        {
            unset($this->data[$group][$key]);
            $this->isDirty = true;
        }
        return $this;
    }

    public function isDirty()
    {
        return $this->isDirty;
    }

    /**
     * Save meta data
     *
     * @return save is successed?
     */
    public function save()
    {
        $file_path = $this->getFilePath();
        $yaml = $this->toYaml();
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
