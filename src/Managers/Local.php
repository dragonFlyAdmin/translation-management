<?php
/**
 * Created by PhpStorm.
 * User: maximkerstens
 * Date: 16/11/2016
 * Time: 07:37
 */

namespace DragonFly\TranslationManager\Managers;


class Local
{
    /**
     * @var \DragonFly\TranslationManager\Managers\Manager
     */
    protected $manager;
    
    /**
     * Local constructor.
     *
     * @param \DragonFly\TranslationManager\Managers\Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }
    
    /**
     * Do all the calls to the driver's repository.
     *
     * @param string $method    Method name
     * @param array  $arguments Method arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->manager->repository, $method], $arguments);
    }
}