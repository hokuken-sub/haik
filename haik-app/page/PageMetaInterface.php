<?php
namespace Hokuken\Haik\Page;

interface PageMetaInterface {

    /**
     * Read meta data of the page
     *
     * @return mixed meta data array of the page
     */
    public function read();

    /**
     * Get meta data of specified key e.g group.value
     *
     * @param string $key
     * @param mixed $default_value when $key is not found then return this value
     * @return mixed specified meta data
     */
    public function get($key, $default_value = NULL);

    /**
     * Get all meta data of the page
     *
     * @return mixed meta data
     */
    public function getAll();

    /**
     * Set meta data of specified key e.g. group.value
     *
     * @param string $key
     * @param mixed $value
     * @return $this for method chain
     */
    public function set($key, $value);

    /**
     * Set or merge providing array to $data
     */
    public function setAll($array);

    /**
     * Save meta data
     *
     * @return save is successed?
     */
    public function save();

    /**
     * Delete meta data relating the page
     *
     * @return delete is successed?
     */
    public function delete();

}