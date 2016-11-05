<?php namespace DragonFly\TranslationManager;

use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\Finder\Finder;

class LaravelStringManager
{
    
    /** @var \Illuminate\Foundation\Application */
    protected $app;
    /** @var \Illuminate\Filesystem\Filesystem */
    protected $files;
    /** @var \Illuminate\Events\Dispatcher */
    protected $events;
    
    /** @var array */
    protected $config;
    
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
    
    public function __construct(Application $app, Filesystem $files, Dispatcher $events)
    {
        $this->app = $app;
        $this->files = $files;
        $this->events = $events;
        $this->config = $app['config']['translations'];
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
        if (!in_array($group, $this->config['exclude_groups']))
        {
            TranslationString::firstOrCreate([
                'locale' => $this->app['config']['app.locale'],
                'group' => $group,
                'key' => $key,
            ]);
        }
    }
    
    /**
     * Import all translations.
     *
     * @param bool $replace
     *
     * @return int
     */
    public function importTranslations($replace = false)
    {
        $counter = 0;
        
        // Loop over all directories that contain translations
        foreach ($this->files->directories($this->app->langPath()) as $langPath)
        {
            $locale = basename($langPath);
            
            foreach ($this->files->allfiles($langPath) as $file)
            {
                
                $info = pathinfo($file);
                $group = $info['filename'];
                
                // If it's defined as 'excluded', ignore
                if (in_array($group, $this->config['exclude_groups']))
                {
                    continue;
                }
                
                $this->import($replace, $langPath, $group, $info, $locale, $counter);
            }
        }
        
        return $counter;
    }
    
    /**
     * Import translation keys for a specified group.
     *
     * @param string $group
     * @param bool   $replace Should the keys be replaced or appended
     *
     * @return int
     */
    public function importGroupTranslations($group, $replace = false)
    {
        $counter = 0;
        
        // If it's excluded, don't run this method
        if(in_array($group, $this->config['exclude_groups']))
            return $counter;
        
        // Loop over all directories that contain translations
        foreach ($this->files->directories($this->app->langPath()) as $langPath)
        {
            $locale = basename($langPath);
            
            foreach ($this->files->allfiles($langPath) as $file)
            {
                
                $info = pathinfo($file);
                
                // If the file doesn't have the same name as the group, ignore
                if ($info['filename'] != $group)
                {
                    continue;
                }
                
                $this->import($replace, $langPath, $group, $info, $locale, $counter);
            }
        }
        
        return $counter;
    }
    
    /**
     * Run the import.
     *
     * @param bool   $replace   Should the DB's content be replaced or appended?
     * @param string $langPath  Path to language file
     * @param string $group     Group name
     * @param array  $info      File info
     * @param string $locale    Locale
     * @param int    $counter   Tracks the amount of keys added in DB.
     */
    protected function import($replace, $langPath, $group, $info, $locale, &$counter)
    {
        $subLangPath = str_replace($langPath . DIRECTORY_SEPARATOR, "", $info['dirname']);
        
        if ($subLangPath != $langPath)
        {
            $group = $subLangPath . "/" . $group;
        }
        
        $translations = Lang::getLoader()->load($locale, $group);
        
        if ($translations && is_array($translations))
        {
            foreach (array_dot($translations) as $key => $value)
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
                $newStatus = ($translation->exists && $translation->value === $value) || !$translation->exists ?
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
    }
    
    /**
     * Scan the provided path's files for keys that were used in translation functions ($transFunctions).
     *
     * @param null $path
     *
     * @return int
     */
    public function scanForTranslations($path = null)
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
        $finder->in($path)->exclude('storage')->name('*.php')->name('*.twig')->files();
        
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
     * Export translations, optionally specify which group to export.
     *
     * @param $group
     */
    public function exportTranslations($group = '*')
    {
        if (!in_array($group, $this->config['exclude_groups']))
        {
            if ($group == '*')
            {
                return $this->exportAllTranslations();
            }
            
            $tree = $this->makeTree(TranslationString::where('group', $group)->whereNotNull('value')->get());
            
            foreach ($tree as $locale => $groups)
            {
                if (isset( $groups[$group] ))
                {
                    $translations = $groups[$group];
                    
                    // Make sure the lang directory exists
                    if(!$this->files->exists($this->app->langPath() . '/' . $locale))
                    {
                        $this->files->makeDirectory($this->app->langPath() . '/' . $locale);
                    }
                    
                    // Save the translations to disk
                    $path = $this->app->langPath() . '/' . $locale . '/' . $group . '.php';
                    $output = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
                    $this->files->put($path, $output);
                }
            }
            
            TranslationString::where('group', $group)->whereNotNull('value')
                             ->update(['status' => TranslationString::STATUS_SAVED]);
        }
    }
    
    /**
     * Retrieve all translation groups and export them one by one.
     */
    public function exportAllTranslations()
    {
        switch (DB::getDriverName())
        {
            case 'mysql':
                $select = 'DISTINCT `group`';
            break;
            
            default:
                $select = 'DISTINCT "group"';
            break;
        }
        
        $groups = TranslationString::whereNotNull('value')->select(DB::raw($select))->get('group');
        
        // Export each group individually
        $groups->each(function ($group)
        {
            $this->exportTranslations($group->group);
        });
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
     * Remove all translations that don't have a value assigned
     */
    public function cleanTranslations()
    {
        TranslationString::whereNull('value')->delete();
    }
    
    /**
     * Clean out the translations on record (local translations are untouched)
     */
    public function truncateTranslations()
    {
        TranslationString::truncate();
    }
    
    /**
     * Get this package's config.
     *
     * @param null $key
     *
     * @return array|mixed
     */
    public function getConfig($key = null)
    {
        return ( $key == null ) ? $this->config : $this->config[$key];
    }
    
    public function uniqueKeys($whereNot=false)
    {
        $query = TranslationString::select('group', 'key');
        
        if(is_array($whereNot) && count($whereNot) == 2)
        {
            $query->where($whereNot[0], '!=', $whereNot[1]);
        }
        
        return $query->get()->map(function ($r)
        {
            return $r->group . '.' . $r->key;
        })->unique();
    }
    
    public function loadGroups()
    {
        $groups = TranslationString::groupBy('group')->select('group');
        $excludedGroups = $this->getConfig('exclude_groups');
    
        if ($excludedGroups)
        {
            $groups->whereNotIn('group', $excludedGroups);
        }
    
        $groups = $groups->get();
    
        // Normalise groups
        if ($groups instanceof Collection)
        {
            $groups = $groups
                ->mapWithKeys(function ($record)
                {
                    return [$record->group => str_replace(['_', '-'], ' ', ucfirst($record->group))];
                })
                ->all();
    
        }
        else
        {
            $groups = [];
        }
    
        $groups = ['' => 'Choose a group'] + $groups;
    
        return $groups;
    }
    
    /**
     * Load all the unique locales found in the database.
     *
     * @return array
     */
    public function loadLocales()
    {
        //Set the default locale as the first one.
        $locales = TranslationString::groupBy('locale')->get()->pluck('locale');
        
        if ($locales instanceof Collection)
        {
            $locales = $locales->toArray();
        }
        else
        {
            $locales = [];
        }
        
        return [config('app.locale')] + $locales;
    }
    
    public function loadAmountChangedRecords()
    {
        return TranslationString::where('status', TranslationString::STATUS_CHANGED)->count();
    }
}
