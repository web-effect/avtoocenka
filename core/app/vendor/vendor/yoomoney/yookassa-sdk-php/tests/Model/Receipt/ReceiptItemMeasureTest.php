<?php

/*
 * The MIT License
 *
 * Copyright (c) 2026 "YooMoney", NBСO LLC
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
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Tests\YooKassa\Model\Receipt;

use PHPUnit\Framework\TestCase;
use YooKassa\Model\Receipt\ReceiptItemMeasure;

class ReceiptItemMeasureTest extends TestCase
{
    public function testAllConstantsDefined()
    {
        $class = new \ReflectionClass('YooKassa\Model\Receipt\ReceiptItemMeasure');
        $constants = $class->getConstants();
        unset($constants['validValues']);
        foreach ($constants as $name => $value) {
            self::assertTrue(ReceiptItemMeasure::valueExists($value), "{$name}: {$value}");
        }
    }

    public function testValueExists()
    {
        $class = new \ReflectionClass('YooKassa\Model\Receipt\ReceiptItemMeasure');
        $constants = $class->getConstants();
        unset($constants['validValues']);
        $firstValue = reset($constants);

        self::assertTrue(ReceiptItemMeasure::valueExists($firstValue));
        self::assertFalse(ReceiptItemMeasure::valueExists('nonexistent_value'));
    }

    public function testGetValidValues()
    {
        $values = ReceiptItemMeasure::getValidValues();
        self::assertTrue(is_array($values));
        self::assertNotEmpty($values);
    }

    public function testGetEnabledValues()
    {
        $values = ReceiptItemMeasure::getEnabledValues();
        self::assertTrue(is_array($values));
    }
}