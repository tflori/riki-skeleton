<?php

namespace App\Http\Router;

use FastRoute\BadRouteException;
use FastRoute\DataGenerator\GroupCountBased;

class MiddlewareDataGenerator extends GroupCountBased
{
    protected $groups;

    public function addGroup($routeData, $handler)
    {
        if (count($routeData) !== 1 || !is_string($routeData[0])) {
            throw new BadRouteException('Groups can only have static routes');
        } elseif (isset($this->groups[$routeData[0]])) {
            throw new BadRouteException(sprintf('Cannot register two groups matching "%s"', $routeData[0]));
        }

        $this->groups[$routeData[0]] = $handler;
    }

    public function getData()
    {
        $data = parent::getData();
        $data[] = empty($this->groups) ? [] : $this->generateGroupData();
        return $data;
    }

    protected function generateGroupData()
    {
        $prefixes = array_keys($this->groups);
        usort($prefixes, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        $regexes = [];
        $groupMap = [];
        $numGroups = 0;
        foreach ($prefixes as $prefix) {
            $regexes[] = $prefix . str_repeat('()', $numGroups);
            $groupMap[$numGroups + 1] = $this->groups[$prefix];
            $numGroups++;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')~';
        return ['regex' => $regex, 'groupMap' => $groupMap];
    }
}
