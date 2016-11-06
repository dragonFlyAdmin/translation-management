<?php

namespace DragonFly\TranslationManager\Managers\Template;


use DragonFly\TranslationManager\Models\TranslationExternal;
use Illuminate\Support\Collection;

abstract class Actions
{
    
    public $manager;
    
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Remove all translations that don't have a value assigned for the current manager
     */
    public function clean()
    {
        TranslationExternal::whereNull('value')->where('manager', $this->managerName)->delete();
    }
    
    /**
     * Clean out the translations on record for the current manager.
     */
    public function truncate()
    {
        TranslationExternal::where('manager', $this->manager->managerName)->delete();
    }
    
    /**
     * Import a specific or all groups into the manager's DB.
     *
     * @param string $group   Import a specific or all ('*') groups.
     * @param bool   $replace Replace existing records in the DB or append them?
     *
     * @return int
     */
    public function import($group, $replace = false)
    {
        $counter = 0;
        
        $groups = $this->loadLocalGroups($group);
        
        // If no groups are importable
        if ($groups === false || $groups->count() == 0)
        {
            return $counter;
        }
        
        // Loop over each group, import it and return how many records were handled
        return $groups->map(
            function ($group) use ($replace)
            {
                return $this->importGroup($group, $replace);
            }
        )->sum();
    }
    
    /**
     * Load the manager's local translation group(s).
     *
     * @param string $group
     *
     * @return false | Collection
     */
    abstract protected function loadLocalGroups($group);
    
    /**
     * Parse a group definition and import it into the manager's DB.
     *
     * @param string $group
     * @param bool   $replace
     *
     * @return integer
     */
    abstract protected function importGroup($group, $replace);
    
    /**
     * Export the specified group(s).
     *
     * @param string $group
     */
    public function export($group = '*')
    {
        $groups = Collection::make($this->loadTranslatedGroups($group));
        
        $groups->each(function ($group)
        {
            $this->exportGroup($group);
        });
    }
    
    /**
     * Export a specific group.
     *
     * @param $group
     */
    abstract protected function exportGroup($group);
    
    /**
     * @param string $loadGroup
     *
     * @return array
     */
    protected function loadTranslatedGroups($loadGroup)
    {
        // If we're loading all groups, let's query for them
        if ($loadGroup == '*')
        {
            $select = ( DB::getDriverName() == 'mysql' ) ? 'DISTINCT `model`' : 'DISTINCT "model"';
            
            return TranslationExternal::where('manager', $this->manager)
                                      ->whereNotNull('value')
                                      ->select(DB::raw($select))
                                      ->pluck('model');
        }
        
        return [$loadGroup];
    }
    
    /**
     * Creates a new locale,
     * if successful it will return the amount of keys that had the new locale assigned.
     *
     * By default it's not supported.
     *
     * @param string $newLocale
     *
     * @return bool
     */
    public function createLocale($newLocale)
    {
        return false;
    }
    
    /**
     * Remove a key from the database.
     *
     * @param $model
     * @param $modelId
     *
     * @return boolean\int
     */
    public function removeRecord($model, $modelId)
    {
        $keys = TranslationExternal::where('manager', $this->manager->managerName)
                                   ->where('model', $model)
                                   ->where('model_id', $modelId);
        
        $keyCount = $keys->count();
        
        // remove locales for key in DB.
        if ($keyCount > 0)
        {
            $keys->delete();
            
            return $keyCount;
        }
        
        return false;
    }
    
    /**
     * Update a translation key's value in the specified locales.
     *
     * @todo in the third party managers the value will probably be an array
     *
     * @param string $group
     * @param string $key
     * @param array  $locales
     * @param int    $status
     */
    public function updateRecord($group, $key, $locales, $status = TranslationExternal::STATUS_CHANGED)
    {
        // Loop over the locales
        foreach ($locales as $locale => $translation)
        {
            // Update or create the value for this key/locale/group
            TranslationExternal::updateOrCreate([
                'locale' => $locale,
                'manager' => $this->manager,
                'model' => $group,
                'model_id' => $key,
            ], [
                'status' => $status,
                // @todo in the third party managers this will probably be an array
                'value' => ( $translation['value'] == '' ) ? null : $translation['value'],
            ]);
        }
    }
    
    /**
     * Create a new record for the provided group in the specified locale.
     *
     * By default it's not supported.
     *
     * @param string $group
     * @param array  $newKeys
     * @param string $locale
     *
     * @return bool|array
     */
    public function createRecords($group, $newKeys, $locale)
    {
        return false;
    }
    
    /**
     * Replace the provided record with the local one.
     *
     * @param string $group
     * @param string $key
     *
     * @return mixed
     */
    abstract public function replaceRecordWithLocal($group, $key);
}