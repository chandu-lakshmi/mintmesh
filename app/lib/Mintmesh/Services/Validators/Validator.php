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
                $input = $this->input;
                $validation = \Validator::make($this->input, static::$rules[$filter]);

                if (!empty(static::$conditional_rules))
                foreach (static::$conditional_rules as $cr){
                    $a = $cr;
                    $validation->sometimes(static::${$a}, 'required', function($input) use($a)
                    {
                        if (!empty(static::$conditional_rules_keys[$a]) && !empty(static::$conditional_rules_values[$a]))
                        return $input[static::$conditional_rules_keys[$a]] == static::$conditional_rules_values[$a];
                    });
                }
                
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
