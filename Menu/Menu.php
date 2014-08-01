<?php

namespace C33s\MenuBundle\Menu;

use C33s\MenuBundle\Exception\NoMenuItemClassException;
use C33s\MenuBundle\Item\MenuItem;
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

    protected $defaults;

    protected $itemClassAliases;

    protected $checkedClasses = array();

    /**
     * The base item of the given menu. It will not appear anywhere, it's just there
     * to hold the other items.
     *
     * @var MenuItem
     */
    protected $baseItem;

    public function __construct(array $itemData, ContainerInterface $container, array $itemClassAliases = array())
    {
        $this->container = $container;
        $this->itemData = $this->fetchDefaults($itemData);
        $this->itemClassAliases = $itemClassAliases;

        $this->configure();
        $this->initialize();
    }

    protected function fetchDefaults(array $itemData)
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

        $this->defaults = $defaults;

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
        return 'C33s\MenuBundle\Item\MenuItem';
    }

    /**
     * Initialize base menu item and its children
     */
    protected function initialize()
    {
        $itemData = $this->getItemData();

        $itemsMerged = array();
        foreach ($itemData as $key => $itemOptions)
        {
            $itemsMerged[$key] = array_merge($this->defaults, $itemOptions);

            if (isset($itemsMerged[$key]['children']['.defaults']))
            {
                $itemsMerged[$key]['children']['.defaults'] = array_merge($this->defaults, $itemsMerged[$key]['children']['.defaults']);
            }
            else
            {
                $itemsMerged[$key]['children']['.defaults'] = $this->defaults;
            }
        }

        $this->baseItem = $this->createItem('', array(
            'title' => '',
            'item_class' => 'C33s\MenuBundle\Item\MenuItem',
            'children' => $itemsMerged,
        ));
    }

    /**
     * MenuItem factory method.
     *
     * @param string $itemRouteName
     * @param array $itemOptions
     * @throws NoMenuItemClassException
     *
     * @return MenuItem
     */
    public function createItem($itemRouteName, array $itemOptions)
    {
        if (isset($itemOptions['item_class']))
        {
            $class = $itemOptions['item_class'];
        }
        else
        {
            $class = $this->getDefaultItemClass();
        }

        if (isset($this->itemClassAliases[$class]))
        {
            $class = $this->itemClassAliases[$class];
        }

        if (!$this->isValidMenuItemClass($class))
        {
            throw new NoMenuItemClassException(sprintf('Item class %s does not extend \C33s\MenuBundle\Item\MenuItem', $itemOptions['item_class']));
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
     * Get all items on the current item's path, starting with the lowest. Useful for breadcrumb rendering.
     *
     * @return array
     */
    public function getBreadcrumbItems()
    {
        $item = $this->getBaseItem();
        $items = array();

        while ($current = $item->getCurrentChild())
        {
            $items[] = $current;

            $item = $current;
        }

        return $items;
    }

    /**
     * Fetch the current MenuItem (end point in the menu selection).
     *
     * @return MenuItem|NULL
     */
    public function getCurrentItem()
    {
        $item = $this->getBaseItem();

        $found = false;
        while ($current = $item->getCurrentChild())
        {
            $found = true;

            $item = $current;
        }

        if ($found)
        {
            return $item;
        }

        return null;
    }

    /**
     * Check if the given class is a valid MenuItem class.
     *
     * @param string $className
     *
     * @return boolean
     */
    protected function isValidMenuItemClass($className)
    {
        if (!isset($this->checkedClasses[$className]))
        {
            $this->checkedClasses[$className] = $this->hasParentClass($className, 'C33s\\MenuBundle\\Item\\MenuItem');
        }

        return $this->checkedClasses[$className];
    }

    /**
     * Check if the given $parentName class is a parent of $class.
     *
     * @param string $class
     * @param string $parentName
     */
    public function hasParentClass($class, $parentName)
    {
        return $class == $parentName || array_key_exists($parentName, $this->getAncestors($class));
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
