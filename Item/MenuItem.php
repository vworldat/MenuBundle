<?php

namespace c33s\MenuBundle\Item;

use c33s\MenuBundle\Exception\OptionRequiredException;
use c33s\MenuBundle\Menu\Menu;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author david
 *
 * This is the base menu item class, providing basic menu structure information.
 */
class MenuItem implements ContainerAwareInterface
{
    /**
     * @var Menu
     */
    protected $menu;
    
    /**
     * @var ContainerInterface $container
     */
    protected $container;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * @var string
     */
    protected $routeName;
    
    /**
     * @var string
     */
    protected $anchor;
    
    /**
     * @var string
     */
    protected $currentRouteName;
    
    /**
     * @var Request
     */
    protected $request;
    
    /**
     * @var RouterInterface
     */
    protected $router;
    
    /**
     * @var array
     */
    protected $aliasRouteNames = array();
    
    /**
     * @var string
     */
    protected $title;
    
    /**
     * @var string
     */
    protected $titleInMenuHeader;
    
    /**
     * @var MenuItem
     */
    protected $parentItem = null;
    
    /**
     * @var string
     */
    protected $itemGroup = 'default';
    
    /**
     * @var boolean
     */
    protected $visible = true;
    
    /**
     * @var boolean
     */
    protected $visibleIfDisabled = true;
    
    /**
     * @var string
     */
    protected $requireRouteName = null;
    
    /**
     * @var boolean
     */
    protected $invertRequireRouteName = false;
    
    /**
     * @var string
     */
    protected $customUrl = null;
    
    /**
     * @var array
     */
    protected $addRequestVariables = array();
    
    /**
     * @var string
     */
    protected $url;
    
    /**
     * @var array
     */
    protected $children = array();
    
    /**
     * @var boolean
     */
	protected $preDivider = false;
    
    /**
     * @var boolean
     */
	protected $postDivider = false;
    
    /**
     *
     * @var string
     */
    protected $sectionHeader = null;
    
    /**
     * Construct a new menu item. It requires its routeName, options and
     * the menu the item is assigned to.
     *
     * The following options are available in the basic MenuItem implementation:
     * * title                 The item's title to display. By default this
     *                         is the only required option.
     * * title_in_menu_header  Provider alternate title to use when header in 
     *                         drop-down menus
     * * anchor                Optional (html) anchor to add to the final item url
     * * item_group            You can optionally split items in one level
     *                         into groups. To do so, provide group names.
     *                         The default group is "default"
     * * visible               Set to false to hide this item from rendering
     *                         by always returning false on MenuItem::isVisible()
     * * visible_if_disabled   If set to false, the item will be hidden from
     *                         rendering when not enabled.
     * * alias_route_names     Provide alias route names for the current route
     *                         name to mark the item as "current".
     * * require_route_name    If this option is set, the item will be disabled
     *                         if the current route name does not match the
     *                         given value. This provides an easy way to hook
     *                         an item to another item.
     *                         If prefixed with a !, the item will only be
     *                         enabled if the route name is NOT the given one.
     * * custom_url            Provide a custom url that is used instead of
     *                         the item's routeName. If this option is set,
     *                         the routeName may be a dummy one. Beware
     *                         that it is still added to the alias routes.
     * * add_request_variables When this option is provided either as single
     *                         string or array, the given variable names will
     *                         be pulled from the request and added during url
     *                         generation. Of course this does not affect the
     *                         custom_url if provided.
     * * item_class            Specify a custom item class (full namespace).
     *                         The class must extend \c33s\MenuBundle\Item\MenuItem.
     *                         TODO: If it is prefixed with a +, it will be set for
     *                         all child items until defined otherwiese.
     * * children              The definition of child items as associative
     *                         array pointing routeNames to item options.
     * * pre_divider           Divider before / above the item
     * * post_divider          Divider after / below the item
     * * section_header        Section header text to be displayed before / above the item
     *
     * @throws OptionRequiredException
     *
     * @param string $routeName
     * @param array $options
     * @param Menu $menu
     */
    public function __construct($routeName, array $options, Menu $menu)
    {
        $this
            ->setMenu($menu)
            ->setContainer($menu->getContainer())
            ->setRouteName($routeName)
            ->setOptions($this->prepareOptions($options))
        ;
        
        $this->initOptions();
        $this->configure();
        $this->generateChildren();
    }
    
    /**
     * Override this method to modify the raw options array before it is used.
     * Only do this if you know what you are doing!
     *
     * @param array $options
     *
     * @return array    The (modified) options array
     */
    protected function prepareOptions(array $options)
    {
        return $options;
    }
    
    /**
     * Initialize the item's option values.
     */
    protected function initOptions()
    {
        $this
            ->fetchTitle()
            ->fetchItemGroup()
            ->fetchVisible()
            ->fetchVisibleIfDisabled()
            ->fetchDividers()
            ->fetchSectionHeader()
            ->fetchAliasRouteNames()
            ->fetchRequireRouteName()
            ->fetchCustomUrl()
            ->fetchAddRequestVariables()
            ->fetchAnchor()
        ;
    }
    
    /**
     * Override this method to provide additional configuration in your custom
     * implementation. This is called before the item's children are generated.
     */
    protected function configure()
    {
        // additional configuration goes here
    }
    
    /**
     * Generate child items based on the passed options.
     */
    protected function generateChildren()
    {
        if (!$this->hasOption('children') || !is_array($this->getOption('children')))
        {
            return;
        }
        
        foreach ($this->getOption('children') as $routeName => $options)
        {
            $this->addChildByData($routeName, $options);
        }
    }
    
    /**
     * Add a child to the menu using item data.
     *
     * Possible value for position are:
     * * 'last': insert at last position (append), this is the default
     * * 'first': insert at first position
     * * positive number (e.g. 2): insert at this position, count starts at 0
     * * negative number (e.g. -1): insert at this position from the END of the children backwards
     *
     * @param string $routeName
     * @param array $options
     * @param string $position
     *
     * @return MenuItem    The generated item
     */
    public function addChildByData($routeName, $options, $position = 'last')
    {
        $item = $this->getMenu()->createItem($routeName, $options);
        
        return $this->addChild($item, $position);
    }
    
    /**
     * Add a child item to the menu at the given position.
     *
     * Possible value for position are:
     * * 'last': insert at last position (append), this is the default
     * * 'first': insert at first position
     * * positive number (e.g. 2): insert at this position, count starts at 0
     * * negative number (e.g. -1): insert at this position from the END of the children backwards
     *
     * @param MenuItem $item
     * @param mixed $position
     *
     * @return MenuItem    The added item
     */
    public function addChild(MenuItem $item, $position = 'last')
    {
        if ('first' == $position)
        {
            $this->children = array_merge(array($item), $this->children);
        }
        elseif (is_numeric($position))
        {
            array_splice($this->children, (int) $position, 0, array($item));
        }
        else
        {
            $this->children[] = $item;
        }
        
        $item->setParentItem($this);
        
        return $item;
    }
    
    /**
     * Add an item next to this menu item.
     *
     * @param string $routeName
     * @param array $options
     *
     * @return MenuItem    The generated item
     */
    public function addSiblingByData($routeName, array $options)
    {
        $item = $this->getMenu()->createItem($routeName, $itemData);
        
        return $this->getParent()->addChild($item, $this->getItemPosition() + 1);
    }
    
    /**
     * Get the item's child items.
     *
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }
    
    /**
     * Check if the menu item has any children.
     * 
     * @return boolean
     */
    public function hasChildren()
    {
        return count($this->getChildren()) > 0;
    }
    
    /**
     * Check if the menu item has any children that are enabled.
     *
     * @return boolean
     */
    public function hasEnabledChildren()
    {
        foreach ($this->getChildren() as $child)
        {
            if ($child->isEnabled())
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Assign a menu to this item
     *
     * @param Menu $menu
     *
     * @return MenuItem
     */
    protected function setMenu(Menu $menu)
    {
        $this->menu = $menu;
        
        return $this;
    }
    
    /**
     * Get the assigned menu
     *
     * @return Menu
     */
    protected function getMenu()
    {
        return $this->menu;
    }
    
    /**
     * Set this menu item's route name.
     *
     * @param string $routeName
     *
     * @return MenuItem
     */
    protected function setRouteName($routeName)
    {
        $this->routeName = $routeName;
        
        return $this;
    }
    
    /**
     * Get the item's route name.
     *
     * @return string
     */
    protected function getRouteName()
    {
        return $this->routeName;
    }
    
    /**
     * Add 1 or more alias routes, passing either a route name
     * or an array of route names.
     *
     * @param mixed $aliasRoutes
     *
     * @return MenuItem
     */
    protected function addAliasRoutes($aliasRoutes)
    {
        $aliasRoutes = (array) $aliasRoutes;
        foreach ($aliasRoutes as $aliasRoute)
        {
            $aliasRoute = (string) $aliasRoute;
            $this->aliasRouteNames[$aliasRoute] = $aliasRoute;
        }
        
        return $this;
    }
    
    /**
     * Set the DI container.
     *
     * @param ContainerInterface $container
     *
     * @return MenuItem
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        
        return $this;
    }
    
    /**
     * Get the DI container
     *
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }
    
    /**
     * Set a parent menu item.
     *
     * @param MenuItem $parentItem
     *
     * @return MenuItem
     */
    public function setParentItem(MenuItem $parentItem)
    {
        $this->parentItem = $parentItem;
        
        return $this;
    }
    
    /**
     * Get this item's parent item.
     *
     * @return MenuItem
     */
    public function getParentItem()
    {
        return $this->parentItem;
    }
    
    /**
     * Check if the item has a parent item.
     *
     * @return boolean
     */
    public function hasParentItem()
    {
        return null !== $this->parentItem;
    }
    
    /**
     * Fetch the item's "title" option.
     *
     * @return MenuItem
     */
    protected function fetchTitle()
    {
        return $this
            ->fetchOption('title', true)
            ->fetchOption('title_in_menu_header')
        ;
    }
    
    /**
     * Get the item's title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
    
    /**
     * Get the item's title to display when inside a menu header.
     * If not defined, getTitle() is returned.
     *
     * @return string
     */
    public function getTitleInMenuHeader()
    {
        if (null !== $this->titleInMenuHeader)
        {
            return $this->titleInMenuHeader;
        }
        
        return $this->getTitle();
    }
    
    /**
     * Get the item's title, html-safe
     *
     * @return string
     */
    public function getEscapedTitle()
    {
        return htmlspecialchars($this->getTitle());
    }
    
    /**
     * Fetch the item's "anchor" option.
     *
     * @return MenuItem
     */
    protected function fetchAnchor()
    {
        return $this->fetchOption('anchor');
    }
    
    /**
     * Get the html anchor to use with the URL.
     *
     * @return string
     */
    public function getAnchor()
    {
        return $this->anchor;
    }
    
    /**
     * Check if the item has an anchor. 
     * 
     * @return boolean
     */
    public function hasAnchor()
    {
        return null !== $this->getAnchor();
    }
    
    /**
     * Fetch the item's "item_group" option.
     *
     * @return MenuItem
     */
    protected function fetchItemGroup()
    {
        return $this->fetchOption('itemGroup');
    }
    
    /**
     * Get the item's group name. This can be used to separate items inside
     * a single menu level.
     *
     * @return string
     */
    public function getItemGroup()
    {
        return $this->itemGroup;
    }
    
    /**
     * Fetch the item's "visible" option.
     *
     * @return MenuItem
     */
    protected function fetchVisible()
    {
        return $this->fetchOption('visible');
    }
    
    /**
     * Get the value of the "visible" option. Don't mix this up with
     * MenuItem::isVisible().
     *
     * @return boolean
     */
    protected function getVisible()
    {
        return (boolean) $this->visible;
    }
    
    /**
     * Fetch the item's "visible_if_disabled" option.
     *
     * @return MenuItem
     */
    protected function fetchVisibleIfDisabled()
    {
        return $this->fetchOption('visibleIfDisabled');
    }
    
    /**
     * Fetch the item's "pre_divider" and "post_divider" options.
     *
     * @return MenuItem
     */
    protected function fetchDividers()
    {
        return $this
            ->fetchOption('preDivider')
            ->fetchOption('postDivider')
        ;
    }
    
    /**
     * Fetch the item's "section_header" option.
     *
     * @return MenuItem
     */
    protected function fetchSectionHeader()
    {
        return $this->fetchOption('sectionHeader');
    }
    
    /**
     * Check if the item should be displayed when not enabled.
     *
     * @return boolean
     */
    protected function getVisibleIfDisabled()
    {
        return $this->visibleIfDisabled;
    }
    
    /**
     * Fetch the item's "alias_route_names" option.
     *
     * @return MenuItem
     */
    protected function fetchAliasRouteNames()
    {
        return $this->addAliasRoutes($this->getOption('alias_route_names', array()));
    }
    
    /**
     * Get the alias route names to the item's route name.
     *
     * @return array
     */
    protected function getAliasRouteNames()
    {
        return $this->aliasRouteNames;
    }
    
    /**
     * Fetch the item's "require_route_name" option.
     *
     * @return MenuItem
     */
    protected function fetchRequireRouteName()
    {
        $this->fetchOption('requireRouteName');
        if ('' != $this->requireRouteName && '!' == substr($this->requireRouteName, 0, 1))
        {
            $this->invertRequireRouteName = true;
            $this->requireRouteName = substr($this->requireRouteName, 1);
        }
        
        return $this;
    }
    
    /**
     * Get the name of the required route for this item to be enabled.
     *
     * @return string
     */
    protected function getRequireRouteName()
    {
        return $this->requireRouteName;
    }
    
    /**
     * Check if the "require_route_name" option has to be inverted
     * (require everything but the route name).
     *
     * @return boolean
     */
    protected function getInvertRequireRouteName()
    {
        return $this->invertRequireRouteName;
    }
    
    /**
     * Fetch the item's "custom_url" option.
     *
     * @return MenuItem
     */
    protected function fetchCustomUrl()
    {
        return $this->fetchOption('customUrl');
    }
    
    /**
     * Get the item's custom url to use instead of the routing.
     *
     * @return string
     */
    protected function getCustomUrl(array $urlParameters = array())
    {
        return $this->customUrl;
    }
    
    /**
     * Fetch the item's "add_request_variables" option.
     *
     * @return MenuItem
     */
    protected function fetchAddRequestVariables()
    {
        $this->fetchOption('addRequestVariables');
        $this->addRequestVariables = (array) $this->addRequestVariables;
        
        return $this;
    }
    
    /**
     * Get the request variable names that should be added when generating
     * the item URL.
     *
     * @return array
     */
    protected function getAddRequestVariables()
    {
        return $this->addRequestVariables;
    }
    
    /**
     * Set the item's option settings.
     *
     * @param array $options
     *
     * @return MenuItem
     */
    protected function setOptions(array $options)
    {
        $this->options = $options;
        
        return $this;
    }
    
    /**
     * Get the item's option settings.
     *
     * @return array
     */
    protected function getOptions()
    {
        return $this->options;
    }
    
    /**
     * Check if the specific option is set in the item's option settings.
     *
     * @param string $name
     *
     * @return boolean
     */
    protected function hasOption($name)
    {
        return array_key_exists($name, $this->getOptions());
    }
    
    /**
     * Get the specific option value from the item's option settings.
     *
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    protected function getOption($name, $default = null)
    {
        if ($this->hasOption($name))
        {
            return $this->options[$name];
        }
        
        return $default;
    }
    
    /**
     * Fetch an option value from the given options array. If the specific option
     * is not set, the default value initialized in the class is used. If the
     * required argument is true and the option is not set an OptionRequiredException is thrown.
     *
     * @throws OptionRequiredException
     *
     * @param string $name
     * @param boolean $required
     *
     * @return MenuItem
     */
    protected function fetchOption($name, $required = false)
    {
        $optionName = Container::underscore($name);
        if ($this->hasOption($optionName))
        {
            $this->$name = $this->getOption($optionName);
        }
        elseif ($required)
        {
            throw new OptionRequiredException(sprintf('The menu item option %s is required', $optionName));
        }
        
        return $this;
    }
    
    /**
     * Check if the item is visible (should be rendered) or not.
     *
     * @return boolean
     */
    public function isVisible()
    {
        if (!$this->getVisible())
        {
            return false;
        }
        if ($this->getVisibleIfDisabled())
        {
            return true;
        }
        
        return $this->isEnabled();
    }
    
    /**
     * Check if the item should be rendered as enabled (with link)
     * or not (just title, no link).
     *
     * @return boolean
     */
    public function isEnabled()
    {
        if (null !== $this->getRequireRouteName())
        {
            if ($this->getInvertRequireRouteName() XOR ($this->getCurrentRouteName() != $this->getRequireRouteName()))
            {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if the item is currently selected.
     * An item will be marked selected if it is selected itself or any child
     * item is selected.
     *
     * @return boolean    True if the item is currently selected in the menu
     */
    public function isCurrent()
    {
        return $this->isCurrentEndPoint() || $this->isParentOfCurrentEndPoint();
    }
    
    /**
     * Check if the item is currently selected itself. This is the case when
     * the current (request) route name matches the item route name or the
     * item's alias routes.
     *
     * @return boolean
     */
    public function isCurrentEndPoint()
    {
        return $this->getCurrentRouteName() == $this->getRouteName() || array_key_exists($this->getCurrentRouteName(), $this->getAliasRouteNames());
    }
    
    /**
     * Check if any of the child items of the current item are currently selected.
     *
     * @return boolean
     */
    public function isParentOfCurrentEndPoint()
    {
        foreach ($this->getChildren() as $child)
        {
            if ($child->isCurrent())
            {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the current route name from the request.
     *
     * @return string
     */
    public function getCurrentRouteName()
    {
        if (null === $this->currentRouteName)
        {
            $this->currentRouteName = $this->getRequest()->get('_route');
        }
        
        return $this->currentRouteName;
    }
    
    /**
     * Fetch the request from the DI container.
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        if (null === $this->request)
        {
            $this->request = $this->getContainer()->get('request');
        }
        
        return $this->request;
    }
    
    /**
     * Fetch the router from the DI container.
     *
     * @return RouterInterface
     */
    protected function getRouter()
    {
        if (null === $this->router)
        {
            $this->router = $this->getContainer()->get('router');
        }
        
        return $this->router;
    }
    
    /**
     * Get the url for this menu item.
     *
     * @return string
     */
    public function getUrl(array $urlParameters = array(), $absolute = false)
    {
        return $this->generateUrl($urlParameters, $absolute);
    }
    
    /**
     * Generate the URL for this item.
     *
     * @param array $urlParameters
     *
     * @return string
     */
    protected function generateUrl(array $urlParameters = array(), $absolute = false)
    {
        $url = $this->getCustomUrl($urlParameters);
        if ($url)
        {
            return $url;
        }
        
        return $this->generateStandardUrl($urlParameters);
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
        $urlParameters = $this->addRequestVariablesToUrlParameters($urlParameters);
        
        $anchor = '';
        if ($this->hasAnchor())
        {
            $anchor = '#'.$this->getAnchor();
        }
        
        return $this->getRouter()->generate($this->getRouteName(), $urlParameters, $absolute) . $anchor;
    }
    
    /**
     * Add variable values defined in $addRequestVariables to the given
     * urlParameters. This can be used to pass through generally available
     * request parameters.
     *
     * @param array $urlParameters
     *
     * @return array
     */
    protected function addRequestVariablesToUrlParameters(array $urlParameters)
    {
        foreach ($this->getAddRequestVariables() as $key)
        {
            $urlParameters[$key] = $this->getRequest()->get($key);
        }
        
        return $urlParameters;
    }
    
	/**
	 * Check if this item is supposed to show a divider to its left / above.
	 */
	public function hasPreDivider()
	{
		return (boolean) $this->preDivider;
	}
	
	/**
	 * Check if this item is supposed to show a divider to its right / below.
	 */
	public function hasPostDivider()
	{
		return (boolean) $this->postDivider;
	}
    
    /**
     * Check if the item has a section header.
     * 
     * @return type
     */
    public function hasSectionHeader()
    {
        return null !== $this->sectionHeader;
    }
    
    /**
     * Get the section header text.
     * 
     * @return string
     */
    public function getSectionHeader()
    {
        return $this->sectionHeader;
    }
}
