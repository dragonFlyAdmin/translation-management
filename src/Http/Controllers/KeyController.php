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
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRemoveKey($group, $key)
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
    
    public function postCreateKeys(Request $request, $group)
    {
        $newKeys = $request->input('keys');
        $locale = $request->input('locale');
        
        $errors = 0;
        
        foreach($newKeys as $i => $key)
        {
            // Mark it as an error if it already exists
            if(TranslationString::where('group', $group)->where('key', $key['value'])->count() > 0)
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
                'locale' => $locale
            ]);
        }
        
        // Calculate the successfully created keys
        $successfulSaves = count($newKeys) - $errors;
        
        // Error out if none were created
        if($successfulSaves == 0)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'No keys were added, they all already exist.'
            ]);
        }
        
        // (partial) success
        return response()->json([
            'status' => 'success',
            'errors' => $errors,
            'created' => $successfulSaves,
            'keys' => $newKeys
        ]);
    }
}