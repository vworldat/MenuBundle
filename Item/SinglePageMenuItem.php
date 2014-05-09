<?php

namespace c33s\MenuBundle\Item;

use c33s\MenuBundle\Menu\Menu;
use c33s\MenuBundle\Exception\OptionRequiredException;

/**
 * @author david
 *
 * Special MenuItem for single page layouts, where the menu selection is javascript-based
 */
class SinglePageMenuItem extends MenuItem
{
    protected $contentName;
    protected $isContentSelected = false;
    
    /**
     * Construct a new menu item. It requires its routeName, options and
     * the menu the item is assigned to.
     *
     * SimpleContentMenuItem requires the following routeName notation:
     * routeName/contentName
     *
     * @see MenuItem::__construct()
     *
     * @throws OptionRequiredException
     *
     * @param string $routeName
     * @param array $options
     * @param Menu $menu
     */
    public function __construct($routeName, array $options, Menu $menu)
    {
        if (false === strpos($routeName, '/'))
        {
            if (isset($options['content_name']))
            {
                $this->contentName = $options['content_name'];
            }
            else
            {
                throw new OptionRequiredException('SinglePageMenuItem requires either routeName/contentName notation or "content_name" option');
            }
        }
        else
        {
            list($routeName, $this->contentName) = explode('/', $routeName, 2);
        }
        
        if (isset($options['is_selected']))
        {
            $this->isContentSelected = (boolean) $options['is_selected'];
        }
        
        $options['anchor'] = $this->contentName;
        
        parent::__construct($routeName, $options, $menu);
    }
    
    public function isCurrent()
    {
        return $this->isContentSelected && parent::isCurrent();
    }
    
    /**
     * Generate a URL using the routing.
     *
     * @param array $urlParameters
     *
     * @return string
     */
    protected function generateStandardUrl(array $urlParameters = array(), $absolute = false)
    {
        if (parent::isCurrent())
        {
            return '#'.$this->getAnchor();
        }
        
        return parent::generateStandardUrl($urlParameters, $absolute);
    }
}
