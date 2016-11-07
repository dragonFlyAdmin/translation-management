<?php

namespace DragonFly\TranslationManager\Managers\Template;


use Carbon\Carbon;
use DragonFly\TranslationManager\Models\TranslationExternal;
use Illuminate\Support\Collection;

class Meta
{
    /**
     * @var \DragonFly\TranslationManager\Managers\Template\Manager
     */
    protected $manager;
    
    /**
     * BaseStats constructor.
     *
     * @param \DragonFly\TranslationManager\Managers\Template\Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Get the amount of records that are translatable through the UI
     *
     * @return integer
     */
    public function uniqueKeys()
    {
        return TranslationExternal::where('manager', $this->manager->managerName)->count();
    }
    
    /**
     * Load all the unique locales found in the database for this manager.
     *
     * @return array
     */
    public function loadLocales()
    {
        //Set the default locale as the first one.
        $locales = TranslationExternal::where('manager',
            $this->manager->managerName)->groupBy('locale')->get()->pluck('locale');
        
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
        return TranslationExternal::where('manager', $this->manager->managerName)
                                  ->where('status', TranslationExternal::STATUS_CHANGED)
                                  ->count();
    }
    
    /**
     * Load all the groups we have stored in the manager's DB.
     *
     * @return array
     */
    public function loadGroups()
    {
        $groups = TranslationExternal::groupBy('model')->select('model')->get();
        
        $localGroups = $this->manager->localGroups();
        
        // Normalise groups
        if ($groups instanceof Collection)
        {
            $groups = $groups
                ->reject(function ($record) use ($localGroups)
                {
                    // Remove any groups we don't support
                    return !isset( $localGroups[$record->model] );
                })
                ->mapWithKeys(function ($record) use ($localGroups)
                {
                    return [
                        $record->group => [
                            'title' => str_replace(['_', '-'], ' ', ucfirst(snake_case($record->model, ' '))), // Shown in the UI.
                            'value' => $record->model, // Key the translations are stored under.
                            'display_key' => ( new $localGroups[$record->model]() )->getTranslationUIKey(), // Which of the translation key will be shown in the overview.
                        ],
                    ];
                })
                ->all();
        }
        else
        {
            $groups = [];
        }
        
        $groups = ['' => ['title' => 'Choose a group']] + $groups;
        
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
        $translations = TranslationExternal::where('manager', $this->manager->managerName)->where('model',
            $group)->orderBy('last_updated', 'DESC');
        
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
                                    'model_id' => $item['model_id'],
                                    'model' => $item['model'],
                                    'value' => $item['value'],
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