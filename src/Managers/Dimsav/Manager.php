<?php

namespace DragonFly\TranslationManager\Managers\Dimsav;


use DragonFly\TranslationManager\Managers\BaseManager;

class Manager extends BaseManager
{
    public $managerName = 'dimsav';
    
    public function import($model = '*', $replace = false)
    {
        // TODO: Implement import() method.
    }
    
    protected function importRecords($model, $replace = false)
    {
        
    }
    
    public function export($model = '*')
    {
        // TODO: Implement export() method.
    }
}