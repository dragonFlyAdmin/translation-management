<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\LaravelStringManager;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Http\Request;

class KeyController
{
    /** @var \DragonFly\TranslationManager\LaravelStringManager */
    protected $manager;
    
    public function __construct(LaravelStringManager $manager)
    {
        $this->manager = $manager;
    }
    
    public function postSaveTranslation(Request $request, $group)
    {
        $key = $request->input('key');
        $locales = $request->input('locales');
        
        // Loop over the locales
        foreach($locales as $locale => $translation)
        {
            // Update or create the value for this key/locale/group
            TranslationString::updateOrCreate([
                'locale' => $locale,
                'group' => $group,
                'key' => $key
            ], [
                'status' => TranslationString::STATUS_CHANGED,
                'value' => ($translation['value'] == '') ? null : $translation['value']
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'updates' => count($locales),
            'changed' => $this->manager->loadAmountChangedRecords(),
        ]);
    }
    
    public function postReplaceWithLocal(Request $request, $group)
    {
        
    }
    
    /**
     * Remove all locales for a specific key in the database.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRemoveKey(Request $request, $group, $key)
    {
        if (!$this->manager->getConfig('features')['delete_translations'])
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'This feature has been turned off.',
            ]);
        }
        
        $keys = TranslationString::where('group', $group)->where('key', $key);
        
        $keyCount = $keys->count();
        
        // remove locales for key in DB.
        if($keyCount > 0)
        {
            $keys->delete();
        }
        
        return response()->json([
            'status' => 'success',
            'deleted_entries' => $keyCount,
            'records' => $this->manager->uniqueKeys()->count(),
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales(),
            'changed' => $this->manager->loadAmountChangedRecords(),
        ]);
    }
}