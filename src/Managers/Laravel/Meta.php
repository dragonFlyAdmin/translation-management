<?php

namespace DragonFly\TranslationManager\Managers\Laravel;


use Carbon\Carbon;
use DragonFly\TranslationManager\Managers\Template\Meta as BaseMeta;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Support\Collection;

class Meta extends BaseMeta
{
    /**
     * Get the amount of records that are translatable through the UI
     *
     * @return integer
     */
    public function uniqueKeys($whereNot = false)
    {
        $query = TranslationString::select('group', 'key');
        
        if (is_array($whereNot) && count($whereNot) == 2)
        {
            $query->where($whereNot[0], '!=', $whereNot[1]);
        }
        
        return $query->get()
                     ->map(function ($r)
                     {
                         return $r->group . '.' . $r->key;
                     })
                     ->unique();
    }
    
    /**
     * Load all the unique locales found in the database for this manager.
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
    
    /**
     * Returns the amount of records that were changed and not persisted to their source.
     *
     * @return integer
     */
    public function loadAmountChangedRecords()
    {
        return TranslationString::where('status', TranslationString::STATUS_CHANGED)->count();
    }
    
    /**
     * Load all the groups we have stored in the manager's DB.
     *
     * @return array
     */
    public function loadGroups()
    {
        $groups = TranslationString::groupBy('group')->select('group');
        $excludedGroups = $this->manager->config['exclude_groups'];
        
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
     * Load all the translation keys for the specified group.
     *
     * If a timestamp is provided, it will only load translations that were updated after the timestamp.
     *
     * @param string $group
     * @param bool   $timestamp
     *
     * @return array|bool
     */
    public function loadTranslations($group, $timestamp = false)
    {
        $translations = TranslationString::where('group', $group)->orderBy('last_updated', 'DESC');
        
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
            ->map(function ($items)
            {
                return [
                    'key' => $items[0]->key,
                    'locales' => $items
                        ->mapWithKeys(function ($item)
                        {
                            return [
                                $item['locale'] => [
                                    'key' => $item['key'],
                                    'value' => $item['value'],
                                    'group' => $item['group'],
                                    'status' => $item['status'],
                                    'locale' => $item['locale'],
                                ],
                            ];
                        }),
                ];
            })->toArray();
        
        return [$strings, $lastUpdated];
    }
}