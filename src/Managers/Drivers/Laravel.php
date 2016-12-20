<?php

namespace DragonFly\TranslationManager\Managers\Drivers;


use DragonFly\TranslationManager\Managers\Contracts\CanCreateLocal;
use DragonFly\TranslationManager\Managers\Contracts\CanScan;
use DragonFly\TranslationManager\Managers\Repository;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

class Laravel extends Repository implements CanScan, CanCreateLocal
{
    /**
     * Load the list of groups this managers has.
     *
     * @return array
     */
    public function groups()
    {
        $groups = [];
        
        // Loop over all directories that contain translations
        $localeDirectories = $this->manager->files->directories($this->manager->app->langPath());
       
        foreach ($localeDirectories as $langPath)
        {
            // Loop over all translation files
            $translationFiles = $this->manager->files->allfiles($langPath);
            
            foreach ($translationFiles as $file)
            {
                $info = pathinfo($file);
                $fileName = $info['filename'];
                
                // Make sure it's not an excluded group
                if (in_array($fileName, $this->manager->managerConfig['exclude_groups']))
                {
                    continue;
                }
                
                // Make sure we don't have it on record when we register the group
                if (!in_array($fileName, $groups))
                {
                    $groups[] = $fileName;
                }
            }
        }
        
        return $groups;
    }
    
    protected function locales()
    {
        $localeDirectories = $this->manager->files->directories($this->manager->app->langPath());
        
        $locales = [];
        
        foreach ($localeDirectories as $dir)
        {
            $locales[] = str_replace($this->manager->app->langPath() . DIRECTORY_SEPARATOR, '', $dir);
        }
        
        return $locales;
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
        // If it's not just one group or it's excluded, don't run this method
        if ($group == '*' || in_array($group, $this->manager->managerConfig['exclude_groups']))
        {
            return false;
        }
        
        $locales = $this->locales();
        
        $values = [];
        
        foreach ($locales as $locale)
        {
            // Load all the translation keys
            $translations = Lang::getLoader()->load($locale, $group);
            
            if ($translations && is_array($translations))
            {
                $values[$locale] = [];
                
                $keys = array_dot($translations);
                
                // Reformat translation keys and their values
                foreach ($keys as $key => $translation)
                {
                    $values[$locale][$key] = [
                        'value' => $translation,
                    ];
                }
            }
        }
        
        return $values;
    }
    
    /**
     * Export a specific group.
     *
     * @param string $group
     * @params array $records
     */
    public function export($group, $records)
    {
        $translations = $this->makeTree($records);
        
        foreach ($translations as $locale => $keys)
        {
            $localeDirectory = $this->manager->app->langPath() . '/' . $locale;
            
            // Make sure the lang directory exists
            if (!$this->manager->files->exists($localeDirectory))
            {
                $this->manager->files->makeDirectory($localeDirectory);
            }
            
            // Save the translations to disk
            $path = $localeDirectory . '/' . $group . '.php';
            $output = "<?php\n\nreturn " . var_export($keys, true) . ";\n";
            $this->manager->files->put($path, $output);
            
            // Update the newly exported strings' status
            TranslationString::where('manager', $this->manager->managerName)
                ->where('locale', $locale)
                ->where('group', $group)
                ->update(['status' => TranslationString::STATUS_SAVED]);
        }
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
        $tree = [];
        foreach ($translations as $translation)
        {
            foreach($translation['locales'] as $locale => $definition)
            {
                array_set($tree, $locale.'.'.$definition['group'].'.'.$definition['key'], $definition['string']['value']);
            }
        }
        
        return $tree;
    }
    
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
    public function value($group, $key)
    {
        // Load all the active locales
        $locales = $this->locales();
        
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
            return $newValues;
        }
        
        return false;
    }
    
    
    
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
        
        $requireTranslations = [];
        
        // Add the translations to the database, if not existing.
        foreach ($keys as $key)
        {
            // Split the group and item
            list( $group, $item ) = explode('.', $key, 2);
            
            // Only register if we don't have it on record
            if (!in_array($group, $this->manager->managerConfig['exclude_groups']) && TranslationString::where('group', $group)->where('key', $item)->where('manager', $this->manager->managerName)->count() == 0)
            {
                $requireTranslations[] = [
                    'key' => $item,
                    'group' => $group,
                    'manager' => $this->manager->managerName,
                    'locale' => config('app.locale')
                ];
            }
        }
        
        // Return found translations
        return $requireTranslations;
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
        return in_array($group, $this->manager->managerConfig['exclude_groups']);
    }
    
    /**
     * Returns the display key of a translation value.
     *
     * @param string $group
     *
     * @param int    $key
     *
     * @return string
     */
    public function displayKey($group, $key)
    {
        // TODO: Implement displayKey() method.
    }
}