<?php

/**
 * @version     3.0.0
 * @package     com_imc
 * @copyright   Copyright (C) 2014. All rights reserved.
 * @license     GNU AFFERO GENERAL PUBLIC LICENSE Version 3; see LICENSE
 * @author      Ioannis Tsampoulatidis <tsampoulatidis@gmail.com> - https://github.com/itsam
 */
// No direct access
defined('_JEXEC') or die;

/**
 * Routing class from com_imc
 *
 * @since  3.3
 */
class ImcRouter extends JComponentRouterBase
{
    /**
     * Build the route for the com_imc component
     *
     * @param   array  &$query  An array of URL arguments
     *
     * @return  array  The URL arguments to use to assemble the subsequent URL.
     *
     * @since   3.3
     */
    public function build(&$query)
    {
        $segments = array();

        if (isset($query['task'])) {
            $segments[] = implode('/', explode('.', $query['task']));
            unset($query['task']);
        }
        if (isset($query['view'])) {
            $segments[] = $query['view'];
            unset($query['view']);
        }
        if (isset($query['id'])) {
            $segments[] = $query['id'];
            unset($query['id']);
        }

        return $segments;
    }

    /**
     * Parse the segments of a URL.
     *
     * @param   array  &$segments  The segments of the URL to parse.
     *
     * @return  array  The URL attributes to be used by the application.
     *
     * @since   3.3
     */
    public function parse(&$segments)
    {
        $vars = array();

        // view is always the first element of the array
        $vars['view'] = array_shift($segments);

        if ($vars['view'] == 'api') {
            $vars['format'] = 'json';
        }

        if ($vars['view'] == 'api2') {
            $vars['format'] = 'json';
        }

        while (!empty($segments)) {
            $segment = array_pop($segments);
            if (is_numeric($segment)) {
                $vars['id'] = $segment;
            } else {
                $vars['task'] = $vars['view'] . '.' . $segment;
            }
        }

        return $vars;
    }
}

/**
 * Imc router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$query  An array of URL arguments
 *
 * @return  array  The URL arguments to use to assemble the subsequent URL.
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function ImcBuildRoute(&$query)
{
    $router = new ImcRouter;

    return $router->build($query);
}

/**
 * Imc router functions
 *
 * These functions are proxys for the new router interface
 * for old SEF extensions.
 *
 * @param   array  &$segments  The segments of the URL to parse.
 *
 * @return  array  The URL attributes to be used by the application.
 *
 * @deprecated  4.0  Use Class based routers instead
 */
function ImcParseRoute($segments)
{
    $router = new ImcRouter;

    return $router->parse($segments);
}
