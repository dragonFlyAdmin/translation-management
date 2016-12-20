<?php

namespace DragonFly\TranslationManager\Managers;

abstract class Repository
{
    /**
     * @var \DragonFly\TranslationManager\Managers\Manager
     */
    protected $manager;
    
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Load the list of groups this managers has.
     *
     * @return array
     */
    abstract public function groups();
    
    /**
     * Load the manager's local translations.
     *
     * @param string $group
     *
     * @return array|false
     */
    abstract public function translations($group);
    
    /**
     * Export a specific group (with the provided keys)
     *
     * @param string $group
     * @param array $keys
     */
    abstract public function export($group, $keys);
    
    /**
     * Retrieve a translation value by group and key.
     *
     * Should return an array with the values grouped in locales.
     * if none are found, false.
     *
     * @param string $group
     * @param string $key
     *
     * @return array | false
     */
    abstract public function value($group, $key);
    
    /**
     * Rejects groups that arn't registered.
     *
     * @param string $group
     *
     * @return bool
     */
    abstract public function rejectGroup($group);
    
    /**
     * Returns the display key of a translation value.
     *
     * @param string $group
     * @param int    $key
     *
     * @return string
     */
    abstract public function displayKey($group, $key);
}