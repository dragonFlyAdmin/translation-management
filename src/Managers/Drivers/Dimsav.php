<?php

namespace DragonFly\TranslationManager\Managers\Drivers;


use Carbon\Carbon;
use DragonFly\TranslationManager\Managers\Repository;
use DragonFly\TranslationManager\Managers\Contracts\StringHasMultipleKeys;
use DragonFly\TranslationManager\Managers\TranslatableManager;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Database\Eloquent\Model;

class DimSav extends Repository implements StringHasMultipleKeys
{
    /**
     * Load the list of groups this managers has.
     *
     * @return array
     */
    public function groups()
    {
        $groups = [];
        
        // Loop over the model classes
        foreach ($this->manager->managerConfig['models'] as $modelClass)
        {
            // Initialise the model, retrieve the translation slug and register it.
            $model = new $modelClass;
            
            // Only if the model was set up correctly
            if ($model->translationsCanBeManaged())
            {
                $groups[$model->translation_slug] = $modelClass;
            }
        }
        
        return $groups;
    }
    
    /**
     * Load the manager's local translation group(s).
     *
     * @param string $group
     *
     * @return array|false
     */
    public function translations($group)
    {
        $localGroups = $this->groups();
        
        // If it's not just one group and it's not registered
        if ($group == '*' || !array_key_exists($group, $localGroups))
        {
            return false;
        }
        
        $values = [];
        
        $modelClass = $localGroups[$group];
        
        // Initialise the model and retrieve its translated attributes
        $model = new $modelClass();
        $translationKeys = $model->translatedAttributes;
        
        $records = $model->translated()->get();
        
        $records->each(function ($string) use (&$values, $translationKeys)
        {
            // Loop over the record's translations and fill locales with values
            foreach ($string->translations as $translation)
            {
                // Create the locale if it isn't present
                if (!isset( $values[$translation->locale] ))
                {
                    $values[$translation->locale] = [];
                }
                if (!isset( $values[$translation->locale][$string->getKey()] ))
                {
                    $values[$translation->locale][$string->getKey()] = [];
                }
                
                // Loop over and register the translation keys
                foreach ($translationKeys as $key)
                {
                    $value = $translation->{$key};
                    
                    // Never register arrays or objects
                    if (!is_array($value) && !is_object($value))
                    {
                        $values[$translation->locale][$string->getKey()][$key] = ['value' => (string) $value];
                    }
                }
            }
        });
        
        return $values;
    }
    
    /**
     * Export a specific group.
     *
     * @param string $group
     * @params array $records
     *
     * @return bool|null
     */
    public function export($group, $records)
    {
        $localGroups = $this->groups();
        
        if (!isset( $localGroups[$group] ))
        {
            return false;
        }
        
        $modelClass = $localGroups[$group];
        
        $keys = [];
            
        
        // Merge the locales and map them to the model id
        foreach ($records as $translation)
        {
            foreach($translation['locales'] as $locale => $definition)
            {
                if(is_array($definition['string']) && count($definition['string']) > 0)
                {
                    if (!isset( $keys[$definition['key']] ))
                    {
                        $keys[$definition['key']] = [];
                    }
    
                    $keys[$definition['key']][$locale] = $this->normaliseExportValues($definition['string']);
                }
            }
        }
        
        // Save the translations
        foreach ($keys as $modelId => $translations)
        {
            $model = $modelClass::find($modelId);
            
            if ($model)
            {
                // Save the value for the current locale
                $model->fill($translations)->save();
    
                // Update the translation's status
                TranslationString::where('manager', $this->manager->managerName)
                                 ->where('key', $modelId)
                                 ->where('group', $group)
                                 ->update(['status' => TranslationString::STATUS_SAVED]);
            }
        }
    }
    
    protected function normaliseExportValues($values)
    {
        $export = [];
        
        foreach ($values as $key => $value)
        {
            $export[$key] = $value['value'];
        }
        
        return $export;
    }
    
    /**
     * Retrieve a translation value by group and key.
     *
     * Should return an array with the values grouped in locales.
     * if none are found, false.
     *
     * @param string       $group
     * @param string|Model $key
     *
     * @return array | false
     */
    public function value($group, $key)
    {
        $localGroups = $this->groups();
        
        if (!isset( $localGroups[$group] ))
        {
            return false;
        }
        
        // If a model was provided, no need to fetch the record.
        if (is_a($key, Model::class))
        {
            $model = $key;
        }
        else
        {
            $modelClass = $localGroups[$group];
            
            // Load the record in question
            $model = $modelClass::find($key);
        }
        
        $locales = [];
        
        if ($model)
        {
            // Loop over the translations (locales)
            foreach ($model->translations as $translation)
            {
                $locales[$translation->locale] = [];
                
                // Register the attributes that are translatable
                foreach ($model->translatedAttributes as $key)
                {
                    $locales[$translation->locale][$key] = [
                        'value' => $translation->{$key},
                    ];
                }
                
                if (count($locales[$translation->locale]) == 0)
                {
                    unset( $locales[$translation->locale] );
                }
            }
            
            if (count($locales) > 0)
            {
                return $locales;
            }
        }
        
        return false;
    }
    
    /**
     * Rejects groups that arn't registered.
     *
     * @param string $group
     *
     * @return bool
     */
    public function rejectGroup($group)
    {
        return !array_key_exists($group, $this->manager->managerConfig['models']);
    }
    
    /**
     * Returns the display key of a translation value.
     *
     * @param string    $group
     * @param int|TranslatableManager $key
     *
     * @return string
     */
    public function displayKey($group, $key)
    {
        $localGroups = $this->groups();
        
        if (!isset( $localGroups[$group] ))
        {
            return false;
        }
        
        // If a model was provided, no need to fetch the record.
        if (is_a($key, Model::class))
        {
            $model = $key;
        }
        else
        {
            $modelClass = $localGroups[$group];
            
            // Load the record in question
            $model = $modelClass::find($key);
        }
        
        return [
            'identifier' => $model->getTranslationUIValue(),
            'render_value' => $model->getTranslationUIKey()
        ];
    }
}