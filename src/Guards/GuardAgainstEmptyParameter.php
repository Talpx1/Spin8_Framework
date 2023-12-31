<?php

declare(strict_types=1);

namespace Spin8\Guards;

use InvalidArgumentException;

final class GuardAgainstEmptyParameter {
    /**
     * Assure that the passed parameter is not empty
     *
     * @throws InvalidArgumentException
     */
    public static function check(mixed $parameter_to_check, bool $allow_null = false) : void {
        if (is_numeric($parameter_to_check)) {
            return;
        }

        if (is_null($parameter_to_check) && $allow_null) {
            return;
        }

        if (is_bool($parameter_to_check)) {
            return;
        }

        if (empty($parameter_to_check)) {
            $caller = debug_backtrace()[1];

            $message = "function '{$caller['function']}' was called with non-allowed empty argument";
            $message .= array_key_exists('file', $caller) ? " in {$caller['file']}" : '';
            $message .= array_key_exists('line', $caller) ? " on line {$caller['line']}" : '';
            $message .= '.' . PHP_EOL;
            $message .= array_key_exists('args', $caller) ? PHP_EOL . 'Passed arguments:' . PHP_EOL . \Safe\json_encode($caller['args']) : '';

            throw new InvalidArgumentException($message);
        }
    }
}
