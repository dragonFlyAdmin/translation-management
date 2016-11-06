<?php namespace DragonFly\TranslationManager;

class Manager
{
    public function make($manager='Laravel')
    {
        $managerClass = '\DragonFly\TranslationManager\Managers\\'.ucfirst($manager);
        return app($managerClass);
    }
}
