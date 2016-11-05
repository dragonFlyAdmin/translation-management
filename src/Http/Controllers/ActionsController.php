<?php

namespace DragonFly\TranslationManager\Http\Controllers;



use DragonFly\TranslationManager\LaravelStringManager;
use DragonFly\TranslationManager\Models\TranslationString;
use Illuminate\Http\Request;

class ActionsController
{
    /** @var \DragonFly\TranslationManager\LaravelStringManager */
    protected $manager;
    
    public function __construct(LaravelStringManager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Remove all translations keys whose value is 'null'.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getClean()
    {
        $this->manager->cleanTranslations();
        
        return response()->json([
            'status' => 'success',
            'records' => $this->manager->uniqueKeys()->count(),
            'groups' => $this->manager->loadGroups(),
            'locales' => $this->manager->loadLocales(),
            'changed' => $this->manager->loadAmountChangedRecords()
        ]);
    }
    
    /**
     * Remove all translations from the database.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTruncate()
    {
        $this->manager->truncateTranslations();
        
        return response()->json([
            'status' => 'success'
        ]);
    }
    
    /**
     * Adds the new locale to all unique keys in the DB.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function postCreateLocale(Request $request)
    {
        $newLocale = $request->input('locale');
        
        // Check if it already exists
        if(TranslationString::where('locale', $newLocale)->count() > 0)
        {
            return response()->json([
                'status' => 'on_record'
            ]);
        }
        
        $addedKeys = 0;
        
        // Loop over the unique keys and add the new locale with a null value
        $this->manager
            ->uniqueKeys(['locale', $newLocale])
            ->each(function($fullKey) use($newLocale, $addedKeys){
                $values = explode('.', $fullKey);
                $group = array_shift($values);
                $key = implode('.', $values);
                
                TranslationString::create([
                    'group' => $group,
                    'key' => $key,
                    'locale' => $newLocale,
                    'value' => null,
                    'status' => TranslationString::STATUS_CHANGED
                ]);
    
                $addedKeys++;
            });
        
        return response()->json([
            'status' => 'success',
            'added' => $addedKeys
        ]);
    }
}