<?php

namespace C33s\MenuBundle\Builder;

use C33s\MenuBundle\Exception\MenuDoesNotExistException;
use C33s\MenuBundle\Menu\Menu;
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
    protected $itemClassAliases;

    protected $menus = [];

    /**
     * Create a new MenuBuilder instance
     *
     * @param array $menuDefinitions
     */
    public function __construct(array $menuDefinitions, ContainerInterface $container, array $itemClassAliases = [])
    {
        $this->menuDefinitions = $menuDefinitions;
        $this->container = $container;
        $this->itemClassAliases = $itemClassAliases;
    }

    /**
     * Initialize the menus defined in the menuDefinitions.
     */
    public function initialize()
    {
        if (null !== $this->menus) {
            return;
        }

        foreach ($this->getMenuDefinitions() as $name => $itemData) {
            if (isset($itemData['.menu_class_name'])) {
                $menuClass = $itemData['.menu_class_name'];

                unset($itemData['.menu_class_name']);
            } else {
                $menuClass = 'C33s\MenuBundle\Menu\Menu';
            }

            $this->menus[$name] = new $menuClass($itemData, $this->getContainer(), $this->itemClassAliases);
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

    /**
     * @return Menu[]
     */
    public function getMenus()
    {
        $this->initialize();

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
        $this->initialize();
        if (!isset($this->menus[$name])) {
            throw new MenuDoesNotExistException(sprintf('Menu %s does not exist', $name));
        }

        return $this->menus[$name];
    }
}
