<?php

namespace DragonFly\TranslationManager\Managers\Laravel;


use DragonFly\TranslationManager\Managers\Template\Actions as BaseActions;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

class Actions extends BaseActions
{
    /**
     * @var array These are the functions we'll scan for translation keys
     */
    public $transFunctions = [
        'trans',
        'trans_choice',
        'Lang::get',
        'Lang::choice',
        'Lang::trans',
        'Lang::transChoice',
        '@lang',
        '@choice',
    ];
    
    /**
     * Scan the provided path's files for keys that were used in translation functions ($transFunctions).
     *
     * @param null $path
     *
     * @return int
     */
    public function scan($path = null)
    {
        $path = $path ?: base_path();
        $keys = [];
        
        $pattern =                              // See http://regexr.com/392hu
            "[^\w|>]" .                          // Must not have an alphanum or _ or > before real method
            "(" . implode('|', $this->transFunctions) . ")" .  // Must start with one of the functions
            "\(" .                               // Match opening parenthese
            "[\'\"]" .                           // Match " or '
            "(" .                                // Start a new group to match:
            "[a-zA-Z0-9_-]+" .               // Must start with group
            "([.][^\1)]+)+" .                // Be followed by one or more items/keys
            ")" .                                // Close group
            "[\'\"]" .                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter
        
        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();
        
        $finder->in($path)
               ->exclude('storage')
               ->name('*.php')
               ->name('*.twig')
               ->files();
        
        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file)
        {
            // Search the current file for the pattern
            if (preg_match_all("/$pattern/siU", $file->getContents(), $matches))
            {
                // Get all matches
                foreach ($matches[2] as $key)
                {
                    $keys[] = $key;
                }
            }
        }
        // Remove duplicates
        $keys = array_unique($keys);
        
        // Add the translations to the database, if not existing.
        foreach ($keys as $key)
        {
            // Split the group and item
            list( $group, $item ) = explode('.', $key, 2);
            $this->missingKey('', $group, $item);
        }
        
        // Return the number of found translations
        return count($keys);
    }
    
    /**
     * Create a translation in the DB based on the group and key provided in the default locale.
     *
     * @param $namespace
     * @param $group
     * @param $key
     */
    public function missingKey($namespace, $group, $key)
    {
        if (!in_array($group, $this->manager->config['exclude_groups']))
        {
            TranslationString::firstOrCreate([
                'locale' => $this->manager->app['config']['app.locale'],
                'group' => $group,
                'key' => $key,
            ]);
        }
    }
    
    /**
     * Remove all translations that don't have a value assigned.
     */
    public function clean()
    {
        TranslationString::whereNull('value')->delete();
    }
    
    /**
     * Clean out the translations on record.
     */
    public function truncate()
    {
        TranslationString::truncate();
    }
    
    /**
     * Load the manager's local translation group(s).
     *
     * @param string $group
     *
     * @return false | Collection
     */
    protected function loadLocalGroups($group)
    {
        // If it's just one group and it's excluded, don't run this method
        if ($group != '*' && in_array($group, $this->manager->config['exclude_groups']))
        {
            return false;
        }
        
        $groups = new Collection();
        
        // Loop over all directories that contain translations
        $localeDirectories = $this->manager->files->directories($this->manager->app->langPath());
        
        foreach ($localeDirectories as $langPath)
        {
            $locale = basename($langPath);
            
            // Loop over all translation files
            $translationFiles = $this->manager->files->allfiles($langPath);
            
            foreach ($translationFiles as $file)
            {
                $info = pathinfo($file);
                
                // If we're checking a specific group and the file doesn't have the same name as the group, ignore
                if ($group != '*' && $info['filename'] != $group)
                {
                    continue;
                }
                // If we're checking all groups and it's excluded, ignore
                else if ($group == '*' && in_array($info['filename'], $this->manager->config['exclude_groups']))
                {
                    continue;
                }
                
                $fileName = $info['filename'];
                
                $groups->add([$langPath, $fileName, $info, $locale]);
            }
        }
        
        return $groups;
    }
    
    /**
     * Retrieve all the strings for the specified group and import them.
     *
     * @param array $groupDefinition
     * @param bool  $replace
     *
     * @return integer
     */
    protected function importGroup($groupDefinition, $replace)
    {
        $counter = 0;
        
        list( $langPath, $group, $info, $locale ) = $groupDefinition;
        
        $subLangPath = str_replace($langPath . DIRECTORY_SEPARATOR, "", $info['dirname']);
        
        if ($subLangPath != $langPath)
        {
            $group = $subLangPath . "/" . $group;
        }
        
        // Load all the translation keys
        $translations = Lang::getLoader()->load($locale, $group);
        
        if ($translations && is_array($translations))
        {
            // Reformat translation keys
            $strings = array_dot($translations);
            
            // Run actual import
            foreach ($strings as $key => $value)
            {
                // process only string values
                if (is_array($value))
                {
                    continue;
                }
                
                $value = (string) $value;
                
                $translation = TranslationString::firstOrNew([
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $key,
                ]);
                
                // Check if the database is different then the files (or if new)
                $newStatus = ( $translation->exists && $translation->value === $value ) || !$translation->exists ?
                    TranslationString::STATUS_SAVED : TranslationString::STATUS_CHANGED;
                
                // Update status if they differ
                if ($newStatus != $translation->status)
                {
                    $translation->status = $newStatus;
                }
                
                // Only replace when empty, or explicitly told so
                if ($replace || !$translation->value)
                {
                    $translation->value = $value;
                }
                
                $translation->save();
                
                $counter++;
            }
        }
        
        return $counter;
    }
    
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
            $select = ( DB::getDriverName() == 'mysql' ) ? 'DISTINCT `group`' : 'DISTINCT "group"';
            
            return TranslationString::whereNotNull('value')->select(DB::raw($select))->pluck('group');
        }
        
        return [$loadGroup];
    }
    
    /**
     * Export a specific group.
     *
     * @param $group
     */
    protected function exportGroup($group)
    {
        $tree = $this->makeTree(TranslationString::where('group', $group)->whereNotNull('value')->get());
        
        foreach ($tree as $locale => $groups)
        {
            if (isset( $groups[$group] ))
            {
                $translations = $groups[$group];
                
                $localeDirectory = $this->manager->app->langPath() . '/' . $locale;
                
                // Make sure the lang directory exists
                if (!$this->manager->files->exists($localeDirectory))
                {
                    $this->manager->files->makeDirectory($this->manager->app->langPath() . '/' . $locale);
                }
                
                // Save the translations to disk
                $path = $localeDirectory . '/' . $group . '.php';
                $output = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
                $this->manager->files->put($path, $output);
            }
        }
        
        TranslationString::where('group', $group)
                         ->whereNotNull('value')
                         ->update(['status' => TranslationString::STATUS_SAVED]);
    }
    
    /**
     * Build an array based on the provided translations.
     * Used in export.
     *
     * @param $translations
     *
     * @return array
     */
    protected function makeTree($translations)
    {
        $array = [];
        foreach ($translations as $translation)
        {
            array_set($array[$translation->locale][$translation->group], $translation->key, $translation->value);
        }
        
        return $array;
    }
    
    /**
     * Creates a new locale,
     * if successful it will return the amount of keys that had the new locale assigned.
     *
     * @param string $newLocale
     *
     * @return bool|int
     */
    public function createLocale($newLocale)
    {
        // Check if it already exists
        if (TranslationString::where('locale', $newLocale)->count() > 0)
        {
            return false;
        }
        
        $addedKeys = 0;
        
        // Loop over the unique keys and add the new locale with a null value
        $this->manager
            ->meta()
            ->uniqueKeys(['locale', $newLocale])
            ->each(function ($fullKey) use ($newLocale, $addedKeys)
            {
                $values = explode('.', $fullKey);
                $group = array_shift($values);
                $key = implode('.', $values);
                
                TranslationString::create([
                    'group' => $group,
                    'key' => $key,
                    'locale' => $newLocale,
                    'value' => null,
                    'status' => TranslationString::STATUS_CHANGED,
                ]);
                
                $addedKeys++;
            });
        
        return $addedKeys;
    }
    
    /**
     * Update a translation key's value in the specified locales.
     *
     * @param string $group
     * @param string $key
     * @param array  $locales
     * @param int    $status
     */
    public function updateRecord($group, $key, $locales, $status = TranslationString::STATUS_CHANGED)
    {
        // Loop over the locales
        foreach ($locales as $locale => $translation)
        {
            // Update or create the value for this key/locale/group
            TranslationString::updateOrCreate([
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
            ], [
                'status' => $status,
                'value' => ( $translation['value'] == '' ) ? null : $translation['value'],
            ]);
        }
    }
    
    /**
     * Remove a key from the database.
     *
     * @param $group
     * @param $key
     *
     * @return boolean|int
     */
    public function removeRecord($group, $key)
    {
        $keys = TranslationString::where('group', $group)->where('key', $key);
        
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
     * Create a new record for the provided group in the specified locale.
     *
     * @param string $group
     * @param array  $newKeys
     * @param string $locale
     *
     * @return bool|array
     */
    public function createRecords($group, $newKeys, $locale)
    {
        $errors = 0;
        
        foreach ($newKeys as $i => $key)
        {
            // Mark it as an error if it already exists
            if (TranslationString::where('group', $group)->where('key', $key['value'])->count() > 0)
            {
                $newKeys[$i]['error'] = true;
                $errors++;
                continue;
            }
            
            // Create the new key
            TranslationString::create([
                'group' => $group,
                'key' => $key['value'],
                'value' => null,
                'locale' => $locale,
            ]);
        }
        
        // Calculate the successfully created keys
        $successfulSaves = count($newKeys) - $errors;
        
        // Error out if none were created
        if ($successfulSaves == 0)
        {
            return false;
        }
        
        return [$successfulSaves, $errors, $newKeys];
    }
    
    /**
     * Replace the provided record with the local one.
     *
     * @param string $group
     * @param string $key
     *
     * @return false|int
     */
    public function replaceRecordWithLocal($group, $key)
    {
        // Load all the active locales
        $locales = $this->manager->meta()->loadLocales();
        
        $localKey = $group . '.' . $key;
        
        $newValues = [];
        
        foreach ($locales as $locale)
        {
            // Load the existing translation
            $string = Lang::get($localKey, [], $locale, false);
            
            // If it's the same as the key, there's no translation
            if ($string == $localKey)
            {
                continue;
            }
            
            // Register the value.
            $newValues[$locale] = [
                'value' => $string,
            ];
        }
        
        $amountOfUpdates = count($newValues);
        
        // We've found some locales to update
        if ($amountOfUpdates > 0)
        {
            $this->updateRecord($group, $key, $newValues, TranslationString::STATUS_SAVED);
            
            return $amountOfUpdates;
        }
        
        return false;
    }
}