<?php

namespace DragonFly\TranslationManager\Managers\Dimsav;


trait TranslatableFront
{
    /**
     * Return the key we'll use to render the value in the translation manager.
     *
     * @return string
     */
    public function getUIKey()
    {
        if(property_exists($this, 'represent_translation'))
        {
            return $this->represent_translation;
        }
        
        return $this->translatedAttributes[0];
    }
    
    /**
     * Get the value of the translation key for the translation manager
     * @return mixed
     */
    public function getUIValue()
    {
        return $this->{$this->getUIKey()};
    }
    
    /**
     * Get the translation slug for storing records of this model.
     * @return string
     */
    public function getTranslationSlug()
    {
        // If the model provides a 'translation_slug' property, use it
        if(property_exists($this, 'translation_slug') && !empty($this->translation_slug))
        {
            return $this->translation_slug;
        }
        
        // return the class name
        return strtolower(array_slice(explode('\\', __CLASS__), -1));
    }
}