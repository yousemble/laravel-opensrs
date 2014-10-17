<?php namespace Yousemble\LaravelOpensrs;

use Illuminate\Support\Facades\Facade;

class OpenSRSFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'ys-opensrs'; }

}