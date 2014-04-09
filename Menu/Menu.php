<?php

namespace c33s\MenuBundle\Menu;

use c33s\MenuBundle\Exception\NoMenuItemClassException;
use c33s\MenuBundle\Item\MenuItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Menu
{
    /**
     * @var array
     */
    protected $itemData;
    
    /**
     * @var ContainerInterface $container
     */
    protected $container;
    
    /**
     * The base item of the given menu. It will not appear anywhere, it's just there
     * to hold the other items.
     *
     * @var MenuItem
     */
    protected $baseItem;
    
    public function __construct(array $itemData, ContainerInterface $container)
    {
        $this->container = $container;
        $this->itemData = $this->mergeItemDefaults($itemData);
        
        $this->configure();
        $this->initialize();
    }
    
    /**
     * Look for item defaults and merge them into the list of menu items.
     * 
     * @param array $itemData
     * @return type
     */
    protected function mergeItemDefaults(array $itemData)
    {
        if (array_key_exists('.defaults', $itemData))
        {
            // .defaults provides a way to set default options for all first-level items
            $defaults = $itemData['.defaults'];
            unset($itemData['.defaults']);
        }
        else
        {
            $defaults = array();
        }
        
        if (!array_key_exists('item_class', $defaults))
        {
            $defaults['item_class'] = $this->getDefaultItemClass();
        }
        
        foreach ($itemData as $key => $value)
        {
            $itemData[$key] = array_merge($defaults, $itemData[$key]);
        }
        
        return $itemData;
    }
    
    /**
     * This is executed before initialize(). Put awesome stuff here.
     */
    protected function configure()
    {
        
    }
    
    /**
     * Get the default class name to use for first-level items.
     * 
     * @return string
     */
    protected function getDefaultItemClass()
    {
        return 'c33s\MenuBundle\Item\MenuItem';
    }
    
    /**
     * Initialize base menu item and its children
     */
    protected function initialize()
    {
        $itemData = $this->getItemData();
        
        
        $this->baseItem = $this->createItem('', array(
            'title' => '',
            'children' => $itemData,
        ));
    }
    
    public function createItem($itemRouteName, array $itemOptions)
    {
        $class = 'c33s\MenuBundle\Item\MenuItem';
        if (isset($itemOptions['item_class']))
        {
            if (!$this->hasParentClass($itemOptions['item_class'], $class))
            {
                throw new NoMenuItemClassException(sprintf('Item class %s does not extend \c33s\MenuBundle\Item\MenuItem', $itemOptions['item_class']));
            }
            
            $class = $itemOptions['item_class'];
        }
        
        $item = new $class($itemRouteName, $itemOptions, $this);
        
        return $item;
    }
    
    public function getItemData()
    {
        return $this->itemData;
    }
    
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * @return MenuItem
     */
    public function getBaseItem()
    {
        return $this->baseItem;
    }
    
    /**
     * @return array
     */
    public function getAllItems()
    {
        return $this->getBaseItem()->getChildren();
    }
    
    /**
     * Check if the given $parentName class is a parent of $class.
     *
     * @param string $class
     * @param string $parentName
     */
    public function hasParentClass($class, $parentName)
    {
        return array_key_exists($parentName, $this->getAncestors($class));
    }
    
    /**
     * Get all ancestors of the given class name.
     *
     * @param string $class
     *
     * @return array
     */
    public function getAncestors($class)
    {
        $classes = array();
        while($class = get_parent_class($class))
        {
            $classes[$class] = $class;
        }
        
        return $classes;
    }
}
