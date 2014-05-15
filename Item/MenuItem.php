<?php

namespace c33s\MenuBundle\Item;

use c33s\MenuBundle\Exception\OptionRequiredException;
use c33s\MenuBundle\Menu\Menu;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use c33s\MenuBundle\Exception\InvalidConfigException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyAccess;

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
     * @var MenuItem
     */
    protected $parentItem = null;
    
    /**
     * @var boolean
     */
    protected $visible = true;
    
    /**
     * @var boolean
     */
    protected $visibleIfDisabled = false;
    
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
     * @var array
     */
    protected $setRequestVariables = array();
    
    /**
     * @var array
     */
    protected $matchRequestVariables = array();
    
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
    protected $isDivider = false;
    
    /**
     *
     * @var string
     */
    protected $isSectionHeader = false;
    
    /**
     * Symfony security role required to enable this MenuItem
     *
     * @var string
     */
    protected $requireRole = null;
    
    /**
     * @var boolean
     */
    protected $enabledIfRoleMissing = false;
    
    /**
     * Name of bootstrap icon to use.
     *
     * @var string
     */
    protected $bootstrapIcon = null;
    
    /**
     * (bootstrap) icon class name to display next to disabled items due to missing role.
     *
     * @var string
     */
    protected $lockIcon = 'fa fa-lock';
    
    /**
     *
     * @var string
     */
    protected $customUrlIcon = 'fa fa-external-link';
    
    /**
     * Provides default option values for all child items of this item. These will be assigned recursively.
     *
     * @var array
     */
    protected $defaultOptions = array();
    
    /**
     * @var string
     */
    protected $itemTemplate = null;
    
    /**
     * @var string
     */
    protected $submenuTemplate = null;
    
    /**
     * @var unknown
     */
    protected $customRouteName;
    
    /**
     *
     * @var mixed
     */
    protected $customObject;
    
    protected $propelClassName = null;
    protected $propelQueryMethods = array();
    protected $propelChildRouteParameters = array('id');
    protected $propelChildRoute = null;
    protected $propelChildOptions = array();
    protected $propelTitleField = null;
    
    /**
     * Construct a new menu item. It requires its routeName, options and
     * the menu the item is assigned to.
     *
     * The following options are available in the basic MenuItem implementation:
     * * title                      The item's title to display. By default this
     *                              is the only required option.
     * * copy_to_children           Insert copy of this item as first child item.
     *                              This is useful for drop-down menus where the button itself
     *                              cannot contain the item. Defaults to false
     * * copy_to_children_title     Alternate title to use when copying this item as first child.
     * * anchor                     Optional (html) anchor to add to the final item url
     * * visible                    Set to false to hide this item from rendering
     *                              by always returning false on MenuItem::isVisible()
     * * visible_if_disabled        If set to false, the item will be hidden from
     *                              rendering when not enabled.
     * * alias_route_names          Provide alias route names for the current route
     * * custom_route_name          If you cannot inject the route name via yml key, you can override it
     *                              using custom_route_name.
     *                              name to mark the item as "current".
     * * require_route_name         If this option is set, the item will be disabled
     *                              if the current route name does not match the
     *                              given value. This provides an easy way to hook
     *                              an item to another item.
     *                              If prefixed with a !, the item will only be
     *                              enabled if the route name is NOT the given one.
     * * custom_url                 Provide a custom url that is used instead of
     *                              the item's routeName. If this option is set,
     *                              the routeName may be a dummy one. Beware
     *                              that it is still added to the alias routes.
     * * custom_url_icon            Class name of (bootstrap) icon to display for custom url
     *                              Defaults to font-awesome 4.x "fa fa-external-link"
     * * add_request_variables      When this option is provided either as single
     *                              string or array, the given variable names will
     *                              be pulled from the request and added during url
     *                              generation. Of course this does not affect the
     *                              custom_url if provided.
     * * match_request_variables    Using this option you can specify request variables that
     *                              have to match the current request to mark the item as
     *                              current endpoint
     * * set_request_variables      Supply additional request variables to set to a specific value
     * * require_role               Specify a role that the security context has to contain
     *                              to enable this MenuItem
     * * enabled_if_role_missing    Define if the item should be enabled if the required role is missing
     *                              Defaults to false
     * * lock_icon                  (bootstrap) icon class name to display next to disabled items
     *                              due to missing role. Defaults to font-awesome 4.x "fa fa-lock"
     * * item_class                 Specify a custom item class (full namespace).
     *                              The class must extend \c33s\MenuBundle\Item\MenuItem.
     * * children                   The definition of child items as associative
     *                              array pointing routeNames to item options.
     * * is_divider                 Set to true to display this item as divider
     * * is_section_header          Set to true to make this item a section header
     * * bootstrap_icon             Name of bootstrap icon to use
     * * item_template              Set a non-default (twig) template name for this item
     * * submenu_template           Set a non-default (twig) template name for this item's submenu
     *
     * * propel                     Auto-generate children fetched from a propel collection
     *                              Each element will be injected into its menu item as custom_object
     *       class_name             Propel model class name to use (full namespace)
     *       child_route            Route name to use for generated child elements
     *
     *       query_methods          Optional list of query methods to apply to the query in the format:
     *           filterByXY:        [ method, parameters]
     *       child_route_parameters Optional list of parameters to extract from the each object and use
     *                              as route parameters / request variables. Defaults to [ id ]
     *       child_options          Additional menu item options only to use for the generated children, such as item_class
     *       title_field            Field to use for children titles. If not set, a __toString() cast will be used.
     *
     * * custom_object              Provide custom object that availably inside the menu item
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
            ->fetchItemVariants()
            ->fetchCustomRouteName()
            ->fetchDefaultOptions()
            ->fetchTitle()
            ->fetchVisible()
            ->fetchVisibleIfDisabled()
            ->fetchAliasRouteNames()
            ->fetchRequireRouteName()
            ->fetchCustomUrl()
            ->fetchAddRequestVariables()
            ->fetchSetRequestVariables()
            ->fetchMatchRequestVariables()
            ->fetchAnchor()
            ->fetchIcons()
            ->fetchRequireRole()
            ->fetchTemplates()
            ->fetchPropel()
            ->fetchCustomObject()
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
    
    protected function fetchDefaultOptions()
    {
        if (isset($this->options['children']['.defaults']))
        {
            $this->defaultOptions = (array) $this->options['children']['.defaults'];
            unset($this->options['children']['.defaults']);
        }
        
        return $this;
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
        
        $this->insertCopyToChildren();
        
        foreach ($this->getOption('children') as $routeName => $options)
        {
            $this->addChildByData($routeName, $options);
        }
        
        $this->generatePropelChildren();
    }
    
    /**
     * If the item is configured to insert itself as its first child, this will be done here.
     */
    protected function insertCopyToChildren()
    {
        if ($this->getOption('copy_to_children', false))
        {
            $options = $this->getOptions();
            $options['children'] = array();
            $options['copy_to_children'] = false;
            $options['title'] = $this->getOption('copy_to_children_title', $this->getTitle());
            
            $this->addChildByData($this->getRouteName(), $options);
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
        if (isset($options['children']['.defaults']))
        {
            $options['children']['.defaults'] = array_merge($this->defaultOptions, $options['children']['.defaults']);
        }
        else
        {
            $options['children']['.defaults'] = $this->defaultOptions;
        }
        
        $options = array_merge($this->defaultOptions, $options);
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
     * Fetch the item's "custom_route_name" option.
     *
     * @return MenuItem
     */
    protected function fetchCustomRouteName()
    {
        $this
            ->fetchOption('customRouteName')
        ;
        
        if (null !== $this->customRouteName)
        {
            $this->routeName = $this->customRouteName;
        }
        
        return $this;
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
     * Fetch the item's "bootstrap_icon" option.
     *
     * @return MenuItem
     */
    protected function fetchIcons()
    {
        return $this
            ->fetchOption('bootstrapIcon')
            ->fetchOption('lockIcon')
            ->fetchOption('customUrlIcon')
        ;
    }
    
    /**
     * Fetch the item's "require_role" option.
     *
     * @return MenuItem
     */
    protected function fetchRequireRole()
    {
        return $this
            ->fetchOption('requireRole')
            ->fetchOption('enabledIfRoleMissing')
        ;
    }
    
    /**
     * Fetch the item's "item_template" and "submenu_template" options.
     *
     * @return MenuItem
     */
    protected function fetchTemplates()
    {
        return $this
            ->fetchOption('itemTemplate')
            ->fetchOption('submenuTemplate')
        ;
    }
    
    /**
     * Fetch the item's "custom_object" option.
     *
     * @return MenuItem
     */
    protected function fetchCustomObject()
    {
        return $this
            ->fetchOption('customObject')
        ;
    }
    
    /**
     * Check if this item contains a custom object
     *
     * @return boolean
     */
    public function hasCustomObject()
    {
        return null !== $this->customObject;
    }
    
    /**
     * Get the item's custom object, whatever it is.
     *
     * @return mixed
     */
    public function getCustomObject()
    {
        return $this->customObject;
    }
    
    /**
     * Fetch the item's "propel" option and sub options.
     *
     * @return MenuItem
     */
    protected function fetchPropel()
    {
        if (!$this->hasOption('propel'))
        {
            return $this;
        }
        
        $config = $this->getOption('propel');
        
        if (!isset($config['class_name']))
        {
            throw new OptionRequiredException('Propel menu item requires "propel/class_name" config value in menu config');
        }
        elseif (!class_exists($config['class_name']))
        {
            throw new InvalidConfigException('Class does not exist: ' . $config['class_name']);
        }
        elseif (!is_subclass_of($config['class_name'], '\Persistent'))
        {
            throw new InvalidConfigException('Invalid propel class name. Class does not implement \Persistent interface: ' . $config['class_name']);
        }
        $this->propelClassName = $config['class_name'];
        
        if (!isset($config['child_route']))
        {
            throw new OptionRequiredException('Propel menu item requires "propel/child_route" config value in menu config');
        }
        $this->propelChildRoute = $config['child_route'];
        
        if (isset($config['child_route_parameters']))
        {
            $this->propelChildRouteParameters = (array) $config['child_route_parameters'];
        }
        if (isset($config['child_options']))
        {
            $this->propelChildOptions = (array) $config['child_options'];
        }
        if (isset($config['title_field']))
        {
            $this->propelTitleField = $config['title_field'];
        }
        
        if (isset($config['query_methods']) && is_array($config['query_methods']))
        {
            foreach ($config['query_methods'] as $name => $values)
            {
                $this->propelQueryMethods[$name] = (array) $values;
            }
        }
        
        return $this;
    }
    
    /**
     * Generate propel children if the specific config was set.
     */
    protected function generatePropelChildren()
    {
        if (null === $this->propelClassName)
        {
            return;
        }
        
        $queryClass = $this->propelClassName.'Query';
        $query = $queryClass::create();
        
        foreach ($this->propelQueryMethods as $method => $params)
        {
            call_user_func_array(array($query, $method), $params);
        }
        
        $accessor = PropertyAccess::getPropertyAccessor();
        
        $elements = $query->find();
        foreach ($elements as $element)
        {
            $options = $this->propelChildOptions;
            
            if (null !== $this->propelTitleField)
            {
                $options['title'] = $accessor->getValue($element, $this->propelTitleField);
            }
            else
            {
                $options['title'] = (string) $element;
            }
            
            if (!isset($options['set_request_variables']))
            {
                $options['set_request_variables'] = array();
            }
            
            foreach ($this->propelChildRouteParameters as $param)
            {
                $value = $accessor->getValue($element, $param);
                $options['set_request_variables'][$param] = $value;
                $options['match_request_variables'][$param] = $value;
            }
            
            $options['custom_object'] = $element;
            
            $this->addChildByData($this->propelChildRoute, $options);
        }
    }
    
    /**
     * Get the bootstrap icon name.
     *
     * @return string
     */
    public function getBootstrapIcon()
    {
        return $this->bootstrapIcon;
    }
    
    /**
     * Check if the item has a bootstrap icon.
     *
     * @return boolean
     */
    public function hasBootstrapIcon()
    {
        return null !== $this->getBootstrapIcon();
    }
    
    /**
     * Check if the item has a lock icon.
     * By default this is the case if the item is disabled due to security restrictions.
     *
     * @return boolean
     */
    public function hasLockIcon()
    {
        if (null === $this->lockIcon)
        {
            return false;
        }
        
        return !$this->userHasRequiredRole();
    }
    
    /**
     * Get the lock icon class name.
     *
     * @return boolean
     */
    public function getLockIcon()
    {
        return $this->lockIcon;
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
     * Check for specific item variations based on the route name. This is kinda hacky but useful.
     *
     * @return MenuItem
     */
    protected function fetchItemVariants()
    {
        return $this
            ->fetchItemVariantDivider()
            ->fetchItemVariantHeadline()
        ;
    }
    
    /**
     * If the routename start with ".divider" the item will be used as a divider.
     *
     * @return MenuItem
     */
    protected function fetchItemVariantDivider()
    {
        if ($this->getOption('is_divider') || substr($this->getRouteName(), 0, 8) == '.divider')
        {
            $this->isDivider = true;
            $this->options['title'] = 'dummy';
        }
        
        return $this;
    }
    
    /**
     * If the routename start with ".headline" the item will be used as a section header.
     *
     * @return MenuItem
     */
    protected function fetchItemVariantHeadline()
    {
        if ($this->getOption('is_section_header') || substr($this->getRouteName(), 0, 7) == '.header')
        {
            $this->isSectionHeader = true;
        }
        
        return $this;
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
        $urlParameters = $this->addRequestVariablesToUrlParameters($urlParameters);
        
        if ($this->customUrl && count($urlParameters) > 0)
        {
            $params = http_build_query($urlParameters);
            
            if ('' != parse_url($this->customUrl, PHP_URL_QUERY))
            {
                // existing query string, append
                return $this->customUrl.'&'.$params;
            }
            
            return $this->customUrl.'?'.$params;
        }
        
        return $this->customUrl;
    }
    
    /**
     * Check if the item has a custom url.
     *
     * @return boolean
     */
    public function hasCustomUrl()
    {
        return null !== $this->customUrl;
    }
    
    /**
     * Check if the item should display a custom url icon (e.g. for external links)
     *
     * @return boolean
     */
    public function hasCustomUrlIcon()
    {
        return null !== $this->customUrl && '' != $this->customUrlIcon;
    }
    
    /**
     * Get the icon class to display next to custom urls.
     *
     * @return boolean
     */
    public function getCustomUrlIcon()
    {
        return $this->customUrlIcon;
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
     * Fetch the item's "set_request_variables" option.
     *
     * @return MenuItem
     */
    protected function fetchSetRequestVariables()
    {
        $this->fetchOption('setRequestVariables');
        $this->setRequestVariables = (array) $this->setRequestVariables;
        
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
     * Get the request variable names and values to add when generating the item URL.
     *
     * @return array
     */
    protected function getSetRequestVariables()
    {
        return $this->setRequestVariables;
    }
    
    /**
     * Fetch the item's "add_request_variables" option.
     *
     * @return MenuItem
     */
    protected function fetchMatchRequestVariables()
    {
        $this->fetchOption('matchRequestVariables');
        $this->matchRequestVariables = (array) $this->matchRequestVariables;
        
        return $this;
    }
    
    /**
     * Get the request variable names that should be added when generating
     * the item URL.
     *
     * @return array
     */
    protected function getMatchRequestVariables()
    {
        return $this->matchRequestVariables;
    }
    
    /**
     * Check if the current request is matching all required vars.
     *
     * @return boolean
     */
    protected function isMatchingRequestVariables()
    {
        foreach ($this->getMatchRequestVariables() as $key => $value)
        {
            if ($this->getRequest()->get($key) != $value)
            {
                return false;
            }
        }
        
        return true;
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
     * Check the require_role option and check with the security context if necessary.
     *
     * @return boolean
     */
    public function userHasRequiredRole()
    {
        if (null === $this->requireRole)
        {
            return true;
        }
        
        return $this->isSecurityGranted($this->requireRole);
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
        
        return $this->enabledIfRoleMissing || $this->userHasRequiredRole();
    }
    
    /**
     * Check if the given route name is matching the request route name or alias route names
     *
     * @return boolean
     */
    protected function isMatchingRouteName()
    {
        return $this->getCurrentRouteName() == $this->getRouteName() || array_key_exists($this->getCurrentRouteName(), $this->getAliasRouteNames());
    }
    
    /**
     * Check if the item is currently selected itself. This is the case when
     * the current (request) route name matches the item route name or the
     * item's alias routes.
     *
     * @return boolean
     */
    public function isCurrent()
    {
        return $this->isMatchingRouteName() && $this->isMatchingRequestVariables();
    }
    
    /**
     * Check if any of the child items of the current item are currently selected.
     *
     * @return boolean
     */
    public function isCurrentAncestor()
    {
        foreach ($this->getChildren() as $child)
        {
            if ($child->isOnCurrentPath())
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if the item is somewhere on the path of the currently selected item.
     * An item will be marked selected if it is selected itself or any child
     * item is selected.
     *
     * @return boolean    True if the item path is currently selected in the menu
     */
    public function isOnCurrentPath()
    {
        return $this->isCurrent() || $this->isCurrentAncestor();
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
     * Get the security context
     *
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->getContainer()->get('security.context');
    }
    
    /**
     * Check if the current security context contains the role. Checks for a NULL token first to avaid exception.
     *
     * @param mixed $attributes
     * @param mixed $object
     *
     * @return boolean
     */
    protected function isSecurityGranted($attributes, $object = null)
    {
        if (null === $this->getSecurityContext()->getToken())
        {
            return false;
        }
        
        return $this->getSecurityContext()->isGranted($attributes, $object);
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
        foreach ($this->getSetRequestVariables() as $key => $value)
        {
            $urlParameters[$key] = $value;
        }
        
        return $urlParameters;
    }
    
    /**
     * Check if this item is supposed to render as a divider.
     */
    public function isDivider()
    {
        return $this->isDivider;
    }
    
    /**
     * Check if the item has a section header.
     *
     * @return type
     */
    public function isSectionHeader()
    {
        return $this->isSectionHeader;
    }
    
    /**
     * Get the (twig) template name to use for rendering this item. This overrides the default in the twig renderer.
     *
     * @return string
     */
    public function getItemTemplate()
    {
        return $this->itemTemplate;
    }
    
    /**
     * Get the (twig) template name to use for rendering the submenu of this item. This overrides the default in the twig renderer.
     *
     * @return string
     */
    public function getSubmenuTemplate()
    {
        return $this->submenuTemplate;
    }
}
