<?php

namespace C33s\MenuBundle\Twig\Extension;

use C33s\MenuBundle\Builder\MenuBuilder;

class MenuExtension extends \Twig_Extension
{
    /**
     * @var MenuBuilder
     */
    protected $menuBuilder;
    
    /**
     * Create a new MenuExtension instance.
     *
     * @param MenuBuilder $menuBuilder
     */
    public function __construct(MenuBuilder $menuBuilder)
    {
        $this->menuBuilder = $menuBuilder;
    }
    
    public function getFunctions()
    {
        return array(
            'menu_items'        => new \Twig_Function_Method($this, 'getAllMenuItems'),
            'breadcrumb_items'  => new \Twig_Function_Method($this, 'getBreadcrumbMenuItems'),
        );
    }
    
    public function getAllMenuItems($menuName = 'default')
    {
        return $this->getMenuBuilder()->getMenu($menuName)->getAllItems();
    }
    
    public function getBreadcrumbMenuItems($menuName = 'default')
    {
        return $this->getMenuBuilder()->getMenu($menuName)->getBreadcrumbItems();
    }
    
    public function getName()
    {
        return 'c33s_menu';
    }
    
    /**
     * Get the assigned MenuBuilder
     *
     * @return \\MenuBundle\Builder\MenuBuilder
     */
    public function getMenuBuilder()
    {
        return $this->menuBuilder;
    }
}
