<?php

namespace DragonFly\TranslationManager\Managers;


use Carbon\Carbon;
use DragonFly\TranslationManager\Managers\Drivers\DimSav;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Store
{
    /**
     * @var \DragonFly\TranslationManager\Managers\Manager
     */
    protected $manager;
    
    /**
     * Local constructor.
     *
     * @param \DragonFly\TranslationManager\Managers\Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Remove all translations that don't have a value assigned.
     *
     * @param null|string $group
     */
    public function clean($group = null)
    {
        $translations = ( $group == null ) ? TranslationString::where('manager', $this->manager->managerName)->get()
            : TranslationString::where('group', $group)->where('manager', $this->manager->managerName)->get();
    
        $translations->each(function ($translation)
        {
            // Delete if none of the strings under the value are filled
            if ($translation->value == null || count($translation->value) == 0 || $this->checkIfValuesAreEmpty($translation->value))
            {
                $translation->delete();
            }
        });
    }
    
    protected function checkIfValuesAreEmpty($translation)
    {
        $totalKeys = count($translation);
        $emptyValues = 0;
        
        foreach ($translation as $key => $string)
        {
            if ($string == '' || $string == null || (is_array($string) && ($string['value'] == '' || $string['value'] == null)))
            {
                $emptyValues++;
            }
        }
        
        return $totalKeys == $emptyValues;
    }
    
    /**
     * Clean out the translations on record.
     *
     * @param null|string $group
     */
    public function truncate($group = null)
    {
        $translations = new TranslationString();
        
        if ($group != null)
        {
            $translations->where('group', $group)->delete();
            
            return;
        }
        
        $translations->truncate();
    }
    
    /**
     * Update a translation key's value in the specified locales.
     *
     * @param string $group
     * @param string $key
     * @param array  $locales
     * @param int    $status
     */
    public function update($group, $key, $locales, $status = TranslationString::STATUS_CHANGED)
    {
        $multipleKeys = $this->manager->canHaveMultipleKeys();
        
        // Loop over the locales
        foreach ($locales as $locale => $translation)
        {
            $translationRecord = TranslationString::where('locale', $locale)
                                                  ->where('group', $group)
                                                  ->where('key', $key)
                                                  ->first();
            if (!$multipleKeys)
            {
                $value = ( $translation['string']['value'] == '' || count($translation['string']['value']) == 0 ) ?
                    ['value' => null] :
                    $translation['string'];
            }
            else
            {
                $value = ( count($translation['string']) == 0 || $this->checkIfValuesAreEmpty($translation['string']) ) ?
                    null :
                    $translation['string'];
            }
            
            // Create the translation if it does not exist
            if ($translationRecord == null)
            {
                TranslationString::create([
                    'locale' => $locale,
                    'group' => $group,
                    'key' => $key,
                    'status' => $status,
                    'value' => $value,
                    'manager' => $this->manager->managerName
                ]);
            }
            else
            {
                // Otherwise update
                $translationRecord->update([
                    'status' => ( $translationRecord-> status == TranslationString::STATUS_SAVED && $translationRecord->value == $value ) ? TranslationString::STATUS_SAVED : $status,
                    'value' => $value,
                ]);
            }
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
    public function remove($group, $key)
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
     * Return the group names for the current manager.
     *
     * @todo universal local groups
     *
     * @return array
     */
    public function groups()
    {
        $groups = TranslationString::TranslatedGroups($this->manager->managerName)->pluck('group');
        $localGroups = $this->manager->repository->groups();
        
        // Normalise groups
        if ($groups instanceof Collection)
        {
            $groups = $groups
                ->reject(function ($group)
                {
                    // Remove any groups we don't support
                    $this->manager->local()->rejectGroup($group);
                })
                ->mapWithKeys(function ($group) use ($localGroups)
                {
                    if($this->manager->canHaveMultipleKeys())
                    {
                        return [
                            $group => [
                                'title' => str_replace(['_', '-'], ' ', ucfirst(snake_case($group, ' '))),
                                // Shown in the UI.
                                'value' => $group,
                                // Key the translations are stored under.
                                'display_key' => ( new $localGroups[$group]() )->getTranslationUIKey(),
                                // Which of the translation key will be shown in the overview.
                            ],
                        ];
                    }
                    return [
                        $group => [
                            'title' => str_replace(['_', '-'], ' ', ucfirst(snake_case($group, ' '))),
                            // Shown in the UI.
                            'value' => $group,
                            // Key the translations are stored under.
                            'display_key' => 'value',
                            // Which of the translation key will be shown in the overview.
                        ],
                    ];
                    
                })
                ->all();
        }
        
        $groups = ['' => ['title' => 'Choose a group']] + $groups;
        
        return $groups;
    }
    
    /**
     * Load all the unique locales found in the database for this manager.
     *
     * @return array
     */
    public function locales()
    {
        $baseQuery = TranslationString::where('manager', $this->manager->managerName);
        
        //Set the default locale as the first one.
        $locales = $baseQuery
            ->select('locale')
            ->groupBy('locale')
            ->get()
            ->pluck('locale');
        
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
    
    /**
     * Load all the translation keys for the specified group.
     *
     * If a timestamp is provided, it will only load translations that were updated after the timestamp.
     *
     * @param string $group
     * @param bool   $timestamp
     *
     * @return array|bool
     */
    public function translations($group, $timestamp = false)
    {
        $translations = TranslationString::where('group', $group)->orderBy('updated_at', 'DESC');
        
        // If a time stamp was specified, convert it to the standard updated_at format
        if ($timestamp !== false)
        {
            $dateTime = Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i:s');
            $translations->where('updated_at', '>', $dateTime);
        }
        
        $foundRecords = $translations->get();
        
        // No translations were found
        if ($foundRecords->count() == 0)
        {
            return false;
        }
        
        // Get the last updated record's timestamp
        $lastUpdated = $foundRecords[0]->updated_at->format('U');
        
        // Reformat the data
        $strings = $foundRecords
            ->sortBy('key')
            ->groupBy('key')
            ->map(function ($items) use($group)
            {
                $meta = false;
    
                if($this->manager->canHaveMultipleKeys())
                {
                    $meta = $this->manager->local()->displayKey($group, $items[0]['key']);
                }
                
                // Combine the locales
                $data = [
                    'key' => $items[0]->key,
                    'meta' => $meta,
                    'locales' => $items
                        ->mapWithKeys(function ($item)
                        {
                            return [
                                $item['locale'] => [
                                    'key' => $item['key'],
                                    'string' => $item['value'],
                                    'group' => $item['group'],
                                    'status' => $item['status'],
                                    'locale' => $item['locale'],
                                ],
                            ];
                        }),
                ];
                
                return $data;
            })->toArray();
        
        return [$strings, $lastUpdated, false];
    }
    
    /**
     * Generate an array filled with the defined stats.
     *
     * @param array $types
     *
     * @return array
     */
    public function stats($types)
    {
        $stats = [];
        
        foreach ($types as $type)
        {
            switch ($type)
            {
                case 'changed':
                    $stats[$type] = $this->amountChangedRecords();
                break;
                case 'records':
                    $stats[$type] = $this->uniqueKeysCount();
                break;
                case 'locales':
                    $stats[$type] = $this->countLocales();
                break;
            }
        }
        
        return $stats;
    }
    
    /**
     * Count the locales.
     *
     * @return int
     */
    public function countLocales()
    {
        return TranslationString::where('manager', $this->manager->managerName)
                                ->groupBy('locale')
                                ->pluck('locale')
                                ->count();
    }
    
    /**
     * Returns the amount of records that were changed and not persisted to their source.
     *
     * @return integer
     */
    public function amountChangedRecords()
    {
        return TranslationString::where('manager', $this->manager->managerName)
                                ->where('status', TranslationString::STATUS_CHANGED)
                                ->count();
    }
    
    /**
     * Count the unique translations
     *
     * @return int
     */
    public function uniqueKeys()
    {
        return TranslationString::select('key', 'group')
            ->groupBy('key', 'group')
            ->where('manager', $this->manager->managerName)
            ->get();
    }
    
    /**
     * Count the unique translations
     *
     * @return int
     */
    public function uniqueKeysCount()
    {
        return $this->uniqueKeys()->count();
    }
}