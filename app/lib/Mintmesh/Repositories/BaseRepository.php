<?php namespace Mintmesh\Repositories;

abstract class BaseRepository {

        protected $model;

        public function __construct($model) {
                $this->model = $model;
        }

        public function create(array $data) {
                return $this->model->create($data);
        }

}