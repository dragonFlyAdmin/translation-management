<?php

namespace DragonFly\TranslationManager\Http\Controllers;


use DragonFly\TranslationManager\Managers;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;

class KeyController
{
    /** @var \DragonFly\TranslationManager\Managers\Template\Manager */
    protected $manager;
    
    public function __construct(Router $router)
    {
        $this->manager = (new Managers())->make($router->current()->parameter('manager', 'laravel'));
    }
    
    /**
     * Update a key in the specified group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postSaveTranslation(Request $request, $manager, $group)
    {
        $key = $request->input('key');
        $locales = $request->input('locales');
        
        $this->manager->store()->update($group, $key, $locales);
        
        return response()->json([
            'status' => 'success',
            'updates' => count($locales),
            'changed' => $this->manager->store()->amountChangedRecords(),
        ]);
    }
    
    /**
     * Replace the specified key with its local translations.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postReplaceWithLocal(Request $request, $manager, $group)
    {
        $translations = $this->manager->local()->value($group, $request->input('key'));
    
        if (count($translations) == 0 || $translations == false)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'No locales were found for this key to reset.',
            ]);
        }
        
        // Delete stored translaions
        TranslationString::where('manager', $this->manager->managerName)
            ->where('group', $group)
            ->where('key', $request->input('key'))
            ->delete();
    
        
        // Add the existing translations
        foreach ($translations as $locale => $string)
        {
            TranslationString::create([
                'manager' => $this->manager->managerName,
                'group' => $group,
                'key' => $request->input('key'),
                'locale' => $locale,
                'value' => $string,
                'status' => TranslationString::STATUS_SAVED
            ]);
        }
        
        return response()->json([
            'status' => 'success',
            'changed' => $this->manager->store()->amountChangedRecords(),
            'updates' => count($translations),
        ]);
    }
    
    /**
     * Remove all locales for a specific key in the database.
     *
     * @param string $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteRemoveKey($manager, $group, $key)
    {
        if (!$this->manager->config['features']['delete_translations'])
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'This feature has been turned off.',
            ]);
        }
        
        $keyCount = $this->manager->store()->remove($group, $key);
        
        return response()->json([
            'status' => 'success',
            'deleted_entries' => ( !$keyCount ) ? 0 : $keyCount,
            'records' => $this->manager->store()->uniqueKeysCount(),
            'groups' => $this->manager->store()->groups(),
            'locales' => $this->manager->store()->locales(),
            'changed' => $this->manager->store()->amountChangedRecords(),
        ]);
    }
    
    /**
     * Create new keys for the specified group.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $group
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCreateKeys(Request $request, $manager, $group)
    {
        if (!$this->manager->canCreateLocal())
        {
            return response()->json([
                'status' => 'unauthorized',
                'message' => $this->manager->managerName . ' does not support creating new keys.',
            ]);
        }
        
        $newKeys = $request->input('keys');
        $locale = $request->input('locale');
    
        $errors = 0;
    
        foreach ($newKeys as $i => $key)
        {
            // Mark it as an error if it already exists
            if (TranslationString::where('group', $group)
                    ->where('key', $key['value'])
                    ->where('manager', $this->manager->managerName)
                    ->count() > 0)
            {
                $newKeys[$i]['error'] = true;
                $errors++;
                continue;
            }
        
            // Create the new key
            TranslationString::create([
                'group' => $group,
                'key' => $key['value'],
                'value' => ['value' => null],
                'locale' => $locale,
                'manager' => $this->manager->managerName,
                'status' => TranslationString::STATUS_CHANGED
            ]);
        }
    
        // Calculate the successfully created keys
        $successfulSaves = count($newKeys) - $errors;
        
        // Error out if none were created
        if ($successfulSaves == 0)
        {
            return response()->json([
                'status' => 'error',
                'message' => 'No keys were added, they all already exist.',
            ]);
        }
        
        // (partial) success
        return response()->json([
            'status' => 'success',
            'errors' => $errors,
            'created' => $successfulSaves,
            'keys' => $newKeys,
        ]);
    }
}