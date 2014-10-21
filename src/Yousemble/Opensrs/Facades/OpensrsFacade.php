<?php namespace Yousemble\Opensrs\Facades;

use Illuminate\Support\Facades\Facade;

class OpensrsFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'ys-opensrs'; }

}