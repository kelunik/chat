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
            if ($args !== null) {
                $this->validator->addError("args", "There must not be any parameters in the query string");
                return false;
            }
        } else {
            $this->validator->check($args, $this->argsSchema);

            if (!$this->validator->isValid()) {
                return false;
            }
        }

        if ($this->payloadSchema === null) {
            if ($payload !== null) {
                $this->validator->addError("payload", "There must not be any payload");
                return false;
            }
        } else {
            $this->validator->check($payload, $this->payloadSchema);

            if (!$this->validator->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function getValidationErrors () {
        return $this->validator->getErrors();
    }

    public function resetValidation () {
        $this->validator->reset();
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

    public abstract function execute (stdClass $args, $payload);

    public abstract function getPermissions (): array;
}