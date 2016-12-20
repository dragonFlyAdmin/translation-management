<?php

namespace DragonFly\TranslationManager\Managers;


trait TranslatableManager
{
    /**
     * Return the key we'll use to render the value in the translation manager.
     *
     * @return string
     */
    public function getTranslationUIKey()
    {
        if(property_exists($this, 'default_translation_key'))
        {
            return $this->default_translation_key;
        }
        
        // Otherwise, grab the first defined translated attribute.
        return $this->translatedAttributes[0];
    }
    
    /**
     * Return the key we'll use to show a more contextual reference in the UI.
     *
     * @return string
     */
    public function getTranslationUIIdentifier()
    {
        if(property_exists($this, 'represent_translation'))
        {
            return $this->represent_translation;
        }
        
        // Otherwise, grab the first defined translated attribute.
        return $this->translatedAttributes[0];
    }
    
    /**
     * @return string
     */
    public function getTranslationUIValue()
    {
        return $this->{$this->getTranslationUIIdentifier()};
    }
    
    /**
     * Check if the model is set up correct for its translations to be managed.
     * @return bool
     */
    public function translationsCanBeManaged()
    {
        return property_exists($this, 'translation_slug') && $this->translation_slug != '';
    }
}