<?php

namespace App\Chat;

use JsonSchema\Validator;
use ReflectionClass;
use stdClass;

abstract class Command {
    protected $validator;
    protected $argsSchema;
    protected $payloadSchema;

    public function __construct (Validator $validator) {
        $this->validator = $validator;
    }

    public function isValid ($args, $payload) {
        if ($this->argsSchema === null) {
            $valid = $args === null;
        } else {
            $this->validator->check($args, $this->argsSchema);
            $valid = $this->validator->isValid();
        }

        if (!$valid) {
            var_dump($this->validator->getErrors());
        }

        if ($this->payloadSchema === null) {
            $valid = $valid && $payload === null;
        } else {
            $this->validator->check($payload, $this->payloadSchema);
            $valid = $valid && $this->validator->isValid();
        }

        if (!$valid) {
            var_dump($this->validator->getErrors());
        }

        return $valid;
    }

    public function setArgsSchema (stdClass $schema) {
        $this->argsSchema = $schema;
    }

    public function setPayloadSchema (stdClass $schema) {
        $this->payloadSchema = $schema;
    }

    public function getSchemaUri () {
        $base = (new ReflectionClass(self::class))->getNamespaceName() . "\\Command\\";
        $sub = str_replace($base, "", get_class($this));

        return strtolower(str_replace("\\", "/", $sub));
    }

    public abstract function execute ($args, $payload);
}