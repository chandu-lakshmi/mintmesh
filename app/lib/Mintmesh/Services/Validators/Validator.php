<?php namespace Mintmesh\Services\Validators;

abstract class Validator {

        protected $input;

        protected $errors;

        public function __construct($input = NULL)
        {
                $this->input = $input ?: \Input::all();
        }

        public function passes($filter='default')
        {
                $validation = \Validator::make($this->input, static::$rules[$filter]);

                if ($validation->passes()) {
                        return true;
                }

                $this->errors = $validation->messages();
                return false;
        }

        public function getErrors()
        {
                return $this->errors;
        }


}
