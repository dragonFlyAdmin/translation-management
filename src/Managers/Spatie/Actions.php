<?php

namespace DragonFly\TranslationManager\Managers\Spatie;


use DragonFly\TranslationManager\Managers\Template\Actions as BaseActions;
use DragonFly\TranslationManager\Models\TranslationExternal;
use Illuminate\Support\Collection;

class Actions extends BaseActions
{
    
    /**
     * Load the manager's local translation group(s).
     *
     * @param string $group
     *
     * @return false | Collection
     */
    protected function loadLocalGroups($group)
    {
        $localGroups = $this->manager->localGroups;
        
        if (count($localGroups) == 0 || ( $group != '*' && !in_array($group, $localGroups) ))
        {
            return false;
        }
        
        $groups = new Collection();
        
        // Which groups will we be looking for translation strings?
        $searchIn = ( $group == '*' ) ? $localGroups : [$group => $localGroups[$group]];
        
        // Loop over the groups to retrieve records and their values
        foreach ($searchIn as $group => $modelClass)
        {
            // Initialise the model and retrieve its translated attributes
            $model = new $modelClass();
            $translationKeys = $model->translatable;
            
            // Register the group
            $groups->add(compact('model', 'group', 'translationKeys'));
        }
        
        return $groups;
    }
    
    /**
     * Parse a group definition and import it into the manager's DB.
     *
     * @param array $groupDefinition
     * @param bool  $replace
     *
     * @return integer
     */
    protected function importGroup($groupDefinition, $replace)
    {
        $counter = 0;
        $model = $groupDefinition['model'];
        $group = $groupDefinition['group'];
        $translationKeys = $groupDefinition['translationKeys'];
        
        $translatedRecords = $model->all();
        
        $translatedRecords->each(
            function ($string) use ($translationKeys, $group, $replace, $counter)
            {
                // Retrieve the translated values
                $locales = [];
    
                // Loop over the record's translations and fill locales with values
                foreach($translationKeys as $key)
                {
                    $translations = $string->getTranslations($key);
                    
                    foreach($translations as $locale => $value)
                    {
                        if(!isset($locales[$locale]))
                        {
                            $locales[$locale] = [];
                        }
    
                        $locales[$locale][$key] = $value;
                    }
                }
                
                // Let's register each locale separately
                foreach ($locales as $locale => $value)
                {
                    $translation = TranslationExternal::firstOrNew([
                        'manager' => $this->manager->managerName,
                        'model' => $group,
                        'model_id' => $string->getKey(),
                        'locale' => $locale,
                    ]);
                    
                    // Check if the translation is replaceable
                    $unableToReplace = true;
                    if ($replace && $translation->exists)
                    {
                        // If there's a different amount of keys, replace
                        if (count($translation->value) != count($value))
                        {
                            $unableToReplace = false;
                        }
                        else
                        {
                            // If there's a new key or the value doesn't match, replace
                            foreach ($translation->value as $key => $string)
                            {
                                if (!isset( $value[$key] ) || $string != $value[$key])
                                {
                                    $unableToReplace = false;
                                    break;
                                }
                            }
                        }
                    }
                    else
                    {
                        $unableToReplace = false;
                    }
                    
                    // If the record exists and we're able to replace
                    if ($translation->exists && !$unableToReplace)
                    {
                        $translation->status = TranslationExternal::STATUS_SAVED;
                    }
                    // If it's a fresh record
                    else if (!$translation->exists)
                    {
                        $translation->status = TranslationExternal::STATUS_SAVED;
                    }
                    
                    // Only replace when empty, or explicitly told so
                    if (( $replace && !$unableToReplace ) || !$translation->value)
                    {
                        $translation->value = $value;
                    }
                    
                    $translation->save();
                    
                    $counter++;
                }
            });
        
        return $counter;
    }
    
    /**
     * Export a specific group.
     *
     * @param $group
     *
     * @return bool
     */
    protected function exportGroup($group)
    {
        $localGroups = $this->manager->localGroups;
        
        if (!isset( $localGroups[$group] ))
        {
            return false;
        }
        
        $modelClass = $localGroups[$group];
        
        // Retrieve all the strings that have a value
        $strings = TranslationExternal::whereNotNull('value')
                                      ->where('model', $group)
                                      ->where('manager', $this->manager->managerName)
                                      ->get();
        
        $strings->each(function ($record) use ($modelClass)
        {
            $model = $modelClass::find($record->model_id);
            
            if ($model)
            {
                foreach($model->value as $key => $value)
                {
                    $model->setTranslation($key, $record->locale, $value);
                }
                // Save the value for the current locale
                $model->save();
            }
            else
            {
                // The source has been deleted, delete translation as wel.
                $record->delete();
            }
        });
        
        // Mark all translations of this group as saved.
        TranslationExternal::whereNotNull('value')
                           ->where('model', $group)
                           ->where('manager', $this->manager->managerName)
                           ->update([
                               'status' => TranslationExternal::STATUS_SAVED,
                           ]);
    }
    
    /**
     * Replace the provided record with the local one.
     *
     * @param string $group
     * @param string $key
     *
     * @return mixed
     */
    public function replaceRecordWithLocal($group, $key)
    {
        $localGroups = $this->manager->localGroups;
    
        if (!isset( $localGroups[$group] ))
        {
            return false;
        }
    
        $modelClass = $localGroups[$group];
        
        // Get the master copy
        $originals = $modelClass::find($key);
        $translationKeys = $originals->translatable;
        
        $newValues = [];
    
        // Loop over the record's translations and fill locales with values
        foreach($translationKeys as $key)
        {
            $translations = $originals->getTranslations($key);
        
            foreach($translations as $locale => $value)
            {
                if(!isset($locales[$locale]))
                {
                    $newValues[$locale] = [];
                }
                
                // Register the value, no arrays or objects or empty values are allowed
                $newValues[$locale][$key] = (!is_array($value) && !is_object($value) || $value != '') ? $value : null;
            }
        }
    
        $amountOfUpdates = count($newValues);
    
        // We've found some locales to update
        if ($amountOfUpdates > 0)
        {
            $this->updateRecord($group, $key, $newValues, TranslationExternal::STATUS_SAVED);
        
            return $amountOfUpdates;
        }
        
        return false;
    }
}