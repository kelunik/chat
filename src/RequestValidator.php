<?php

namespace Kelunik\Chat;

use JsonSchema\Validator;
use Kelunik\Chat\Boundaries\Request;
use stdClass;

class RequestValidator {
    protected $validator;
    protected $argsSchema;
    protected $payloadSchema;

    public function __construct(Validator $validator) {
        $this->validator = $validator;
    }

    public function validate(Request $request): array {
        // reset at the beginning so we don't have to add
        // a finally block because of short circuit returns.
        $this->validator->reset();

        $args = $this->validateArgs($request);
        $payload = $this->validatePayload($request);

        if (!$args || !$payload) {
            $errors = $this->validator->getErrors();

            $errors = array_map(function ($error) {
                if ($error["property"]) {
                    return sprintf("%s: %s", $error["property"], $error["message"]);
                } else {
                    return $error["message"];
                }
            }, $errors);

            return $errors;
        }

        return [];
    }

    public function setArgsSchema(string $uri, stdClass $schema) {
        $this->argsSchema[$uri] = $schema;
    }

    public function setPayloadSchema(string $uri, stdClass $schema) {
        $this->payloadSchema[$uri] = $schema;
    }

    private function validateArgs(Request $request) {
        $uri = $request->getUri();
        $args = $request->getArgs();
        $argsSchema = $this->argsSchema[$uri] ?? null;

        if ($argsSchema === null) {
            if (!empty((array) $args)) {
                $this->validator->addError("args", "there must not be any args");
                return false;
            }
        } else {
            $this->validator->check($args, $argsSchema);

            if (!$this->validator->isValid()) {
                return false;
            }
        }

        return true;
    }

    private function validatePayload(Request $request) {
        $uri = $request->getUri();
        $payload = $request->getPayload();
        $payloadSchema = $this->payloadSchema[$uri] ?? null;

        if ($payloadSchema === null) {
            if ($payload !== null) {
                $this->validator->addError("payload", "there must not be a payload");
                return false;
            }
        } else {
            $this->validator->check($payload, $payloadSchema);

            if (!$this->validator->isValid()) {
                return false;
            }
        }

        return true;
    }
}