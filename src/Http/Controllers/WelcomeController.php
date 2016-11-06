<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use Carbon\Carbon;
use DragonFly\TranslationManager\Manager;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\View\View;

class WelcomeController
{
    /** @var \DragonFly\TranslationManager\Managers\BaseManager */
    protected $manager;
    
    public function __construct()
    {
        $this->manager = (new Manager())->make();
    }
    
    /**
     * The translation manager's UI.
     *
     * @return View
     */
    public function getIndex()
    {
        $groups = $this->manager->meta()->loadGroups();
        
        return view('translations-manager::welcome')
            ->with('locales', $this->manager->meta()->loadLocales())
            ->with('groups', $groups)
            ->with('defaultLocale', config('app.locale'))
            ->with('stats', $this->loadStatistics($groups))
            ->with('features', $this->manager->getConfig('features'));
    }
    
    /**
     * Load various statistics.
     *
     * @param array $groups
     *
     * @return array
     */
    protected function loadStatistics($groups)
    {
        $uniqueKeys = $this->manager->meta()->uniqueKeys();
        
        return [
            'keys' => $uniqueKeys->count(), // unique keys (don't count locales)
            'groups' => count($groups), // unique groups
            'changed' => $this->manager->meta()->loadAmountChangedRecords(),// keys that need to be saved
        ];
    }
    
    /**
     * Load a specific group's translation keys & locales.
     * Optionally you can define a timestamp, to only get updated translations.
     *
     * @param string   $group
     * @param bool|int $timestamp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLoadGroupTranslations($group, $timestamp = false)
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
            return response()->json([
                'status' => 'empty',
                'message' => 'No (new) translations found',
            ]);
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
        
        return response()->json([
            'status' => ( $timestamp === false ) ? 'new' : 'update',
            'group' => $group,
            'strings' => $strings,
            'last_update' => $lastUpdated,
        ]);
    }
}