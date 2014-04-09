<?php

namespace c33s\MenuBundle\Builder;

use c33s\MenuBundle\Exception\MenuDoesNotExistException;
use c33s\MenuBundle\Menu\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MenuBuilder
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * @var array
     */
    protected $menuDefinitions;
    
    protected $menus = array();
    
    /**
     * Create a new MenuBuilder instance
     *
     * @param array $menuDefinitions
     */
    public function __construct(array $menuDefinitions, ContainerInterface $container)
    {
        $this->menuDefinitions = $menuDefinitions;
        $this->container = $container;
    }
    
    /**
     * Initialize the menus defined in the menuDefinitions.
     */
    public function initialize()
    {
        foreach ($this->getMenuDefinitions() as $name => $itemData)
        {
            if (isset($itemData['.menu_class_name']))
            {
                $menuClass = $itemData['.menu_class_name'];
                
                unset($itemData['.menu_class_name']);
            }
            else
            {
                $menuClass = 'c33s\MenuBundle\Menu\Menu';
            }
            
            $this->menus[$name] = new $menuClass($itemData, $this->getContainer());
        }
    }
    
    /**
     * Getter for the dependency injection container.
     *
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }
    
    public function getMenuDefinitions()
    {
        return $this->menuDefinitions;
    }
    
    public function getMenus()
    {
        return $this->menus;
    }
    
    /**
     * Get a menu by name
     *
     * @throws MenuDoesNotExistException
     *
     * @param string $name
     *
     * @return Menu
     */
    public function getMenu($name = 'default')
    {
        if (!isset($this->menus[$name]))
        {
            throw new MenuDoesNotExistException(sprintf('Menu %s does not exist', $name));
        }
        
        return $this->menus[$name];
    }
}
