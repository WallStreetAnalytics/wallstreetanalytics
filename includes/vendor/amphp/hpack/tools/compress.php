<?php

/**
 * The MIT License (MIT).
 *
 * Copyright (c) 2017 Christian Lück
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

// Taken from https://github.com/leproxy/leproxy/blob/e2ca7917c17ac8b853800f5b390297a2a1525cf7/compile.php

$small = '';
$all = \token_get_all(\file_get_contents($argv[1]));

// search next non-whitespace/non-comment token
$next = function ($i) use (&$all) {
    for ($i = $i + 1; !isset($all[$i]) || \is_array($all[$i]) && ($all[$i][0] === T_COMMENT || $all[$i][0] === T_DOC_COMMENT || $all[$i][0] === T_WHITESPACE); ++$i);
    return $i;
};

// search previous non-whitespace/non-comment token
$prev = function ($i) use (&$all) {
    for ($i = $i -1; $i >= 0 && (!isset($all[$i]) || (\is_array($all[$i]) && ($all[$i][0] === T_COMMENT || $all[$i][0] === T_DOC_COMMENT || $all[$i][0] === T_WHITESPACE))); --$i);
    return $i;
};

$first = true;
foreach ($all as $i => $token) {
    if (\is_array($token) && ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT)) {
        // remove all comments except first
        if ($first === true) {
            $first = false;
            continue;
        }
        unset($all[$i]);
    } elseif (\is_array($token) && $token[0] === T_PUBLIC) {
        // get next non-whitespace token after `public` visibility
        $token = $all[$next($i)];

        if (\is_array($token) && $token[0] === T_VARIABLE) {
            // use shorter variable notation `public $a` => `var $a`
            $all[$i] = [T_VAR, 'var'];
        } else {
            // remove unneeded public identifier `public static function a()` => `static function a()`
            unset($all[$i]);
        }
    } elseif (\is_array($token) && $token[0] === T_LNUMBER) {
        // Use shorter integer notation `0x0F` => `15` and `011` => `9`.
        // Technically, hex codes may be shorter for very large ints, but adding
        // another 2 leading chars is rarely worth it.
        // Optimizing floats is not really worth it, as they have many special
        // cases, such as e-notation and we would lose types for `0.0` => `0`.
        $all[$i][1] = (string) \intval($token[1], 0);
    } elseif (\is_array($token) && $token[0] === T_NEW) {
        // remove unneeded parenthesis for constructors without args `new a();` => `new a;`
        // jump over next token (class name), then next must be open parenthesis, followed by closing
        $open = $next($next($i));
        $close = $next($open);
        if ($all[$open] === '(' && $all[$close] === ')') {
            unset($all[$open], $all[$close]);
        }
    } elseif (\is_array($token) && $token[0] === T_STRING) {
        // replace certain functions with their shorter alias function name
        // http://php.net/manual/en/aliases.php
        static $replace = [
            'implode' => 'join',
            'fwrite' => 'fputs',
            'array_key_exists' => 'key_exists',
            'current' => 'pos',
        ];

        // check this has a replacement and "looks like" a function call
        // this works on a number of assumptions, such as not being aliased/namespaced
        if (isset($replace[$token[1]])) {
            $p = $all[$prev($i)];

            if ($all[$next($i)] === '(' && (!\is_array($p) || !\in_array($p[0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW]))) {
                $all[$i][1] = $replace[$all[$i][1]];
            }
        }
    } elseif (\is_array($token) && $token[0] === T_EXIT) {
        // replace `exit` with shorter alias `die`
        // it's a language construct, not a function (see above)
        $all[$i][1] = 'die';
    } elseif (\is_array($token) && $token[0] === T_RETURN) {
        // replace `return null;` with `return;`
        $t = $next($i);
        if (\is_array($all[$t]) && $all[$t][0] === T_STRING && $all[$t][1] === 'null' && $all[$next($t)] === ';') {
            unset($all[$t]);
        }
    }
}
$all = \array_values($all);
foreach ($all as $i => $token) {
    if (\is_array($token) && $token[0] === T_WHITESPACE) {
        if (\strpos($token[1], "\n") !== false) {
            $token = \strpos("()[]<>=+-*/%|,.:?!'\"\n", \substr($small, -1)) === false ? "\n" : '';
        } else {
            $last = \substr($small, -1);
            $next = isset($all[$i + 1]) ? \substr(\is_array($all[$i + 1]) ? $all[$i + 1][1] : $all[$i + 1], 0, 1) : ' ';

            $token = (\strpos('()[]{}<>;=+-*/%&|,.:?!@\'"' . "\r\n", $last) !== false || \strpos('()[]{}<>;=+-*/%&|,.:?!@\'"' . '\\$', $next) !== false) ? '' : ' ';
        }
    }

    $small .= isset($token[1]) ? $token[1] : $token;
}
\file_put_contents($argv[1], $small);
