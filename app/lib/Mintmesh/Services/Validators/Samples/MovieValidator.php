<?php namespace Mintmesh\Services\Validators\Samples;

/**
 * NOTE : If you want a separate validation for create and
 * update actions, create separate classes for each.
 * eg : UserCreateValidator & UserUpdateValidator
 */
use Mintmesh\Services\Validators\Validator;

class MovieValidator extends Validator {

        /**
         * Validation rules for Movie model
         */
        public static $rules = array(
                'title'      => 'required',
                'released'   => 'required|numeric|between:1900,2020',
                'tagline'    => 'required|max:100'
        );

}