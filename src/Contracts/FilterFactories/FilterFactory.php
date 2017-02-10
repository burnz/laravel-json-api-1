<?php

namespace Ipunkt\LaravelJsonApi\Contracts\FilterFactories;

use Ipunkt\LaravelJsonApi\FilterFactories\FilterFactoryNotFoundException;

interface FilterFactory
{
    /**
     * returns all known filter with their corresponding class
     * Format: 'filtername' => 'Classpath'
     *
     * @return array|string[]
     */
    function allAvailable();

    /**
     * @param string $name
     * @param mixed $value
     * @return \Ipunkt\LaravelJsonApi\Contracts\Repositories\Conditions\RepositoryCondition
     * @throws FilterFactoryNotFoundException when no filter found
     */
    function make($name, $value);

    /**
     * returns default filter
     *
     * @return null|string
     */
    function getDefaultFilter();
}
