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

use Exception;
use PHPUnit\Framework\TestCase;
use YooKassa\Helpers\Random;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\Receipt\ReceiptItemAmount;

class ReceiptItemAmountTest extends TestCase
{
    const DEFAULT_CURRENCY = CurrencyCode::RUB;
    const DEFAULT_VALUE = '0.00';

    protected static function getInstance($value = null, $currency = null)
    {
        return new ReceiptItemAmount($value, $currency);
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param $value
     * @param $currency
     */
    public function testConstructor($value, $currency)
    {
        $instance = new ReceiptItemAmount();

        self::assertEquals(self::DEFAULT_VALUE, $instance->getValue());
        self::assertEquals(self::DEFAULT_CURRENCY, $instance->getCurrency());

        $instance = new ReceiptItemAmount($value, $currency);

        $expectedValue = number_format(round($value, 2), 2, '.', '');
        self::assertEquals($expectedValue, $instance->getValue());
        self::assertEquals(strtoupper($currency), $instance->getCurrency());
    }

    /**
     * @dataProvider validArrayDataProvider
     *
     * @param $data
     */
    public function testArrayConstructor($data)
    {
        $instance = new ReceiptItemAmount();

        self::assertEquals(self::DEFAULT_VALUE, $instance->getValue());
        self::assertEquals(self::DEFAULT_CURRENCY, $instance->getCurrency());

        $instance = new ReceiptItemAmount($data);

        $expectedValue = number_format(round($data['value'], 2), 2, '.', '');
        self::assertEquals($expectedValue, $instance->getValue());
        self::assertEquals(strtoupper($data['currency']), $instance->getCurrency());
    }

    /**
     * @dataProvider validValueDataProvider
     *
     * @param $value
     */
    public function testGetSetValue($value)
    {
        $expected = number_format(round($value, 2), 2, '.', '');

        $instance = self::getInstance();
        self::assertEquals(self::DEFAULT_VALUE, $instance->getValue());
        self::assertEquals(self::DEFAULT_VALUE, $instance->value);
        $instance->setValue($value);
        self::assertEquals($expected, $instance->getValue());
        self::assertEquals($expected, $instance->value);

        $instance = self::getInstance();
        $instance->value = $value;
        self::assertEquals($expected, $instance->getValue());
        self::assertEquals($expected, $instance->value);
    }

    /**
     * @dataProvider invalidValueDataProvider
     * @param mixed $value
     * @param string $exceptionClassName
     */
    public function testSetInvalidValue($value, $exceptionClassName)
    {
        $instance = self::getInstance();
        try {
            $instance->setValue($value);
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider invalidValueDataProvider
     * @param mixed $value
     * @param string $exceptionClassName
     */
    public function testSetterInvalidValue($value, $exceptionClassName)
    {
        $instance = self::getInstance();
        try {
            $instance->value = $value;
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider validCurrencyDataProvider
     * @param string $currency
     */
    public function testGetSetCurrency($currency)
    {
        $instance = self::getInstance();

        self::assertEquals(self::DEFAULT_CURRENCY, $instance->getCurrency());
        self::assertEquals(self::DEFAULT_CURRENCY, $instance->currency);
        $instance->setCurrency($currency);
        self::assertEquals(strtoupper($currency), $instance->getCurrency());
        self::assertEquals(strtoupper($currency), $instance->currency);
    }

    /**
     * @dataProvider invalidCurrencyDataProvider
     * @param string $currency
     * @param string $exceptionClassName
     */
    public function testSetInvalidCurrency($currency, $exceptionClassName)
    {
        $instance = self::getInstance();
        try {
            $instance->setCurrency($currency);
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider invalidCurrencyDataProvider
     * @param string $currency
     * @param string $exceptionClassName
     */
    public function testSetterInvalidCurrency($currency, $exceptionClassName)
    {
        $instance = self::getInstance();
        try {
            $instance->currency = $currency;
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider validGetValueDataProvider
     * @param int $cents
     * @param string $expected
     */
    public function testGetValueFormatting($cents, $expected)
    {
        $instance = self::getInstance();
        $reflection = new \ReflectionProperty($instance, '_value');
        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }
        $reflection->setValue($instance, $cents);

        self::assertEquals($expected, $instance->getValue());
    }

    /**
     * @dataProvider validValueDataProvider
     * @param $value
     */
    public function testGetIntegerValue($value)
    {
        $instance = self::getInstance();
        $instance->setValue($value);
        $expected = (int)round($value * 100.0);
        self::assertEquals($expected, $instance->getIntegerValue());
    }

    /**
     * @dataProvider validMultiplyDataProvider
     * @param $source
     * @param $coefficient
     * @param $expected
     */
    public function testMultiply($source, $coefficient, $expected)
    {
        $instance = new ReceiptItemAmount($source);
        $instance->multiply($coefficient);
        self::assertEquals($expected, $instance->getIntegerValue());
    }

    /**
     * @dataProvider invalidMultiplyDataProvider
     * @param $source
     * @param $coefficient
     * @param $exceptionClassName
     */
    public function testInvalidMultiply($source, $coefficient, $exceptionClassName)
    {
        $instance = new ReceiptItemAmount($source);
        try {
            $instance->multiply($coefficient);
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider validIncreaseDataProvider
     * @param $source
     * @param $amount
     * @param $expected
     */
    public function testIncrease($source, $amount, $expected)
    {
        $instance = new ReceiptItemAmount($source);
        $instance->increase($amount);
        self::assertEquals($expected, $instance->getIntegerValue());
    }

    /**
     * @dataProvider invalidIncreaseDataProvider
     * @param $source
     * @param $amount
     * @param $exceptionClassName
     */
    public function testInvalidIncrease($source, $amount, $exceptionClassName)
    {
        $instance = new ReceiptItemAmount($source);
        try {
            $instance->increase($amount);
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param $value
     * @param $currency
     */
    public function testJsonSerialize($value, $currency)
    {
        $instance = new ReceiptItemAmount($value, $currency);
        $expected = array(
            'value' => number_format(round($value, 2), 2, '.', ''),
            'currency' => strtoupper($currency),
        );
        self::assertEquals($expected, $instance->jsonSerialize());
    }

    /**
     * @dataProvider fromArrayValidDataProvider
     *
     * @param array $data
     */
    public function testFromArray($data)
    {
        $instance = new ReceiptItemAmount();
        $instance->fromArray($data);

        $expectedValue = number_format(round($data['value'], 2), 2, '.', '');
        self::assertEquals($expectedValue, $instance->getValue());
        self::assertEquals(strtoupper($data['currency']), $instance->getCurrency());
    }

    /**
     * @dataProvider fromArrayInvalidDataProvider
     * @param array $data
     * @param string $exceptionClassName
     */
    public function testFromArrayInvalidData($data, $exceptionClassName)
    {
        $instance = new ReceiptItemAmount();
        try {
            $instance->fromArray($data);
            self::fail('Expected exception not thrown');
        } catch (Exception $e) {
            self::assertInstanceOf($exceptionClassName, $e);
        }
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param $value
     * @param $currency
     */
    public function testToArray($value, $currency)
    {
        $instance = new ReceiptItemAmount($value, $currency);
        $array = $instance->toArray();

        self::assertArrayHasKey('value', $array);
        self::assertArrayHasKey('currency', $array);
        self::assertEquals(number_format(round($value, 2), 2, '.', ''), $array['value']);
        self::assertEquals(strtoupper($currency), $array['currency']);
    }

    public function testToArrayRoundtrip()
    {
        $data = array(
            'value' => 123.45,
            'currency' => 'USD',
        );
        $instance = new ReceiptItemAmount($data);
        $array = $instance->toArray();
        self::assertEquals($data['value'], (float)$array['value']);
        self::assertEquals(strtoupper($data['currency']), $array['currency']);

        $instance2 = new ReceiptItemAmount();
        $instance2->fromArray($array);
        self::assertEquals($instance->getValue(), $instance2->getValue());
        self::assertEquals($instance->getCurrency(), $instance2->getCurrency());
    }

    public function validDataProvider()
    {
        $result = $this->validValueDataProvider();
        foreach ($this->validCurrencyDataProvider() as $index => $tmp) {
            if (isset($result[$index])) {
                $result[$index][] = $tmp[0];
            }
        }
        return $result;
    }

    public function validArrayDataProvider()
    {
        $result = array();
        foreach (range(1, 10) as $i) {
            $result[$i][] = array(
                'value' => Random::float(0, 9999.99),
                'currency' => Random::value(CurrencyCode::getValidValues()),
            );
        }
        return $result;
    }

    public function validValueDataProvider()
    {
        $result = array(
            array(0.01),
            array(0.1),
            array(0.11),
            array(0.1111),
            array(0.1166),
            array('0.01'),
            array(1),
            array('1'),
            array(100.00),
            array(100.50),
            array(9999.99),
        );
        for ($i = 0; $i < 10; $i++) {
            $result[] = array(Random::float(0, 9999999));
        }
        return $result;
    }

    public function validGetValueDataProvider()
    {
        return array(
            array(0, '0.00'),
            array(1, '0.01'),
            array(5, '0.05'),
            array(9, '0.09'),
            array(10, '0.10'),
            array(11, '0.11'),
            array(50, '0.50'),
            array(99, '0.99'),
            array(100, '1.00'),
            array(101, '1.01'),
            array(150, '1.50'),
            array(10050, '100.50'),
            array(123456, '1234.56'),
        );
    }

    public function validCurrencyDataProvider()
    {
        $result = array();
        foreach (CurrencyCode::getValidValues() as $value) {
            $result[] = array($value);
            $result[] = array(strtolower($value[0]) . $value[1] . $value[2]);
            $result[] = array($value[0] . strtolower($value[1]) . $value[2]);
            $result[] = array($value[0] . $value[1] . strtolower($value[2]));
            $result[] = array(strtolower($value[0]) . strtolower($value[1]) . $value[2]);
            $result[] = array(strtolower($value[0]) . $value[1] . strtolower($value[2]));
            $result[] = array(strtolower($value));
        }
        return $result;
    }

    public function invalidValueDataProvider()
    {
        $exceptionNamespace = 'YooKassa\\Common\\Exceptions\\';
        return array(
            array(null, $exceptionNamespace . 'EmptyPropertyValueException'),
            array('', $exceptionNamespace . 'EmptyPropertyValueException'),
            array(array(), $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(fopen(__FILE__, 'r'), $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array('invalid_value', $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(-1, $exceptionNamespace . 'InvalidPropertyValueException'),
            array(-0.01, $exceptionNamespace . 'InvalidPropertyValueException'),
            array(true, $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(false, $exceptionNamespace . 'InvalidPropertyValueTypeException'),
        );
    }

    public function invalidCurrencyDataProvider()
    {
        $exceptionNamespace = 'YooKassa\\Common\\Exceptions\\';
        return array(
            array(null, $exceptionNamespace . 'EmptyPropertyValueException'),
            array('', $exceptionNamespace . 'EmptyPropertyValueException'),
            array(array(), $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(fopen(__FILE__, 'r'), $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array('invalid_value', $exceptionNamespace . 'InvalidPropertyValueException'),
            array(true, $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(false, $exceptionNamespace . 'InvalidPropertyValueTypeException'),
        );
    }

    public function validMultiplyDataProvider()
    {
        return array(
            array(1, 0.5, 50),
            array(1.01, 0.5, 51),
            array(1.00, 0.01, 1),
            array(0.99, 0.01, 1),
            array(2.00, 2.0, 400),
            array(1.50, 1.5, 225),
        );
    }

    public function invalidMultiplyDataProvider()
    {
        $exceptionNamespace = 'YooKassa\\Common\\Exceptions\\';
        return array(
            array(1, null, $exceptionNamespace . 'EmptyPropertyValueException'),
            array(1.01, '', $exceptionNamespace . 'EmptyPropertyValueException'),
            array(1.00, true, $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(0.99, array(), $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(0.99, 'test', $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(0.99, -1.0, $exceptionNamespace . 'InvalidPropertyValueException'),
            array(0.99, -0.00001, $exceptionNamespace . 'InvalidPropertyValueException'),
            array(0.99, 0.0, $exceptionNamespace . 'InvalidPropertyValueException'),
            array(0.01, 0.001, $exceptionNamespace . 'InvalidPropertyValueException'),
        );
    }

    public function validIncreaseDataProvider()
    {
        return array(
            array(1, 0.5, 150),
            array(1.01, -0.5, 51),
            array(1.00, -0.001, 100),
            array(0.99, 0.01, 100),
            array(0.50, 0.50, 100),
        );
    }

    public function invalidIncreaseDataProvider()
    {
        $exceptionNamespace = 'YooKassa\\Common\\Exceptions\\';
        return array(
            array(1, null, $exceptionNamespace . 'EmptyPropertyValueException'),
            array(1.01, '', $exceptionNamespace . 'EmptyPropertyValueException'),
            array(1.00, true, $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(0.99, array(), $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(0.99, 'test', $exceptionNamespace . 'InvalidPropertyValueTypeException'),
            array(0.99, -1.0, $exceptionNamespace . 'InvalidPropertyValueException'),
            array(0.99, -0.99, $exceptionNamespace . 'InvalidPropertyValueException'),
        );
    }

    public function fromArrayValidDataProvider()
    {
        return array(
            array(
                array(
                    'value' => 100.00,
                    'currency' => 'RUB',
                ),
            ),
            array(
                array(
                    'value' => 250.50,
                    'currency' => 'USD',
                ),
            ),
            array(
                array(
                    'value' => 0.01,
                    'currency' => 'EUR',
                ),
            ),
        );
    }

    public function fromArrayInvalidDataProvider()
    {
        $exceptionNamespace = 'YooKassa\\Common\\Exceptions\\';
        return array(
            array(
                array('value' => -10.00, 'currency' => 'RUB'),
                $exceptionNamespace . 'InvalidPropertyValueException',
            ),
            array(
                array('value' => 100.00, 'currency' => 'invalid'),
                $exceptionNamespace . 'InvalidPropertyValueException',
            ),
        );
    }
}
