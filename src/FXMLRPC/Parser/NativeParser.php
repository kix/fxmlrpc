<?php
/**
 * Copyright (C) 2012
 * Lars Strojny, InterNations GmbH <lars.strojny@internations.org>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace FXMLRPC\Parser;

use DateTime;
use DateTimeZone;
use stdClass;
use RuntimeException;
use FXMLRPC\Value\Base64;

class NativeParser implements ParserInterface
{
    public function __construct()
    {
        if (!extension_loaded('xmlrpc')) {
            throw new RuntimeException('PHP extension ext/xmlrpc missing');
        }
    }

    public function parse($xmlString, &$isFault)
    {
        $result = xmlrpc_decode($xmlString, 'UTF-8');

        $isFault = false;

        $toBeVisited = array(&$result);
        while (isset($toBeVisited[0]) && $value = &$toBeVisited[0]) {

            switch (gettype($value)) {
                case 'object':
                    switch ($value->xmlrpc_type) {

                        case 'datetime':
                            $value = DateTime::createFromFormat(
                                'Ymd\TH:i:s',
                                $value->scalar,
                                new DateTimeZone('UTC')
                            );
                            break;

                        case 'base64':
                            if ($value->scalar !== '') {
                                $value = new Base64($value->scalar);
                                break;
                            }
                            $value = null;
                            break;
                    }
                    break;

                case 'array':
                    foreach ($value as &$element) {
                        $toBeVisited[] = &$element;
                    }
                    break;
            }

            array_shift($toBeVisited);
        }

        if (is_array($result)) {
            reset($result);
            $isFault = xmlrpc_is_fault($result);
        }

        return $result;
    }
}
