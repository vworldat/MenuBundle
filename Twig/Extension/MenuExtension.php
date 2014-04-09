<?php

namespace c33s\MenuBundle\Twig\Extension;

use c33s\MenuBundle\Builder\MenuBuilder;

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
            'menu'          => new \Twig_Function_Method($this, 'renderMenu'),
            'menu_items'    => new \Twig_Function_Method($this, 'getAllMenuItems'),
        );
    }
    
    public function getAllMenuItems($menuName = 'default')
    {
        return $this->getMenuBuilder()->getMenu($menuName)->getAllItems();
    }
    
    /**
     * Render the menu with the given name.
     *
     * @param string $name
     *
     * @return string
     */
    public function renderMenu($name = 'default')
    {
        return 'abc';
//         $menu = $this->container->get('c33s_menu');
    
//         $router = $this->container->get('router');
//         $request = $this->container->get('request');
//         $route = $request->get('_route');
    
//         $content = '';
    
//         foreach ($menu->getItems() as $itemRoute => $item)
//         {
//             $url = $router->generate($itemRoute);
//             if ($itemRoute == $route)
//             {
//                 $content .= '<li class="active"><a href="'.$url.'">'.$item['title'].'</a></li>';
//             }
//             else
//             {
//                 $content .= '<li><a href="'.$url.'">'.$item['title'].'</a></li>';
//             }
//         }
    
//         return $content;
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
