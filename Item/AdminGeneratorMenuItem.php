<?php

namespace c33s\MenuBundle\Item;

use c33s\MenuBundle\Exception\MenuException;

/**
 * @author david
 *
 * Special MenuItem for AdminGenerator menu entries, providing all alias routes for AdminGenerator tasks.
 */
class AdminGeneratorMenuItem extends MenuItem
{
    /**
     * Fetch the item's "alias_route_names" option.
     *
     * @return MenuItem
     */
    protected function fetchAliasRouteNames()
    {
        if ('_list' != substr($this->getRouteName(), -5))
        {
            throw new MenuException('Route name used for AdminGeneratorMenuItems must end with _list');
        }
        
        $baseName = substr($this->getRouteName(), 0, -4);
        $actions = array('edit', 'update', 'show', 'object', 'batch', 'new', 'create', 'filters', 'scopes');
        
        $routes = array();
        foreach ($actions as $action)
        {
            $routes[] = $baseName.$action;
        }
        
        $this->addAliasRoutes($routes);
        
        return parent::fetchAliasRouteNames();
    }
}
