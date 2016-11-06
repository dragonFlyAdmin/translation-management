<?php

namespace DragonFly\TranslationManager\Managers\Template;


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
}