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

namespace Tests\YooKassa\Model\Deal;

use PHPUnit\Framework\TestCase;
use stdClass;
use YooKassa\Helpers\Random;
use YooKassa\Model\CurrencyCode;
use YooKassa\Model\Deal\SettlementPayoutPaymentType;
use YooKassa\Model\Deal\SettlementPayoutRefund;
use YooKassa\Model\MonetaryAmount;

class SettlementPayoutRefundTest extends TestCase
{
    protected function getTestInstance()
    {
        return new SettlementPayoutRefund();
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testGetSetType($value)
    {
        $instance = $this->getTestInstance();
        self::assertNull($instance->getType());
        self::assertNull($instance->type);
        $instance->setType($value['type']);
        self::assertSame($value['type'], $instance->getType());
        self::assertSame($value['type'], $instance->type);
    }

    /**
     * @dataProvider invalidTypeDataProvider
     */
    public function testSetInvalidType($value)
    {
        $this->expectException('\InvalidArgumentException');
        $this->getTestInstance()->setType($value);
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testGetSetAmount($value)
    {
        $instance = $this->getTestInstance();
        self::assertNull($instance->getAmount());
        self::assertNull($instance->amount);

        $amount = new MonetaryAmount($value['amount']['value'], $value['amount']['currency']);
        $instance->setAmount($amount);
        self::assertSame($amount, $instance->getAmount());
        self::assertSame($amount, $instance->amount);
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testSetAmountFromArray($value)
    {
        $instance = $this->getTestInstance();
        $instance->setAmount($value['amount']);
        self::assertInstanceOf('YooKassa\Model\MonetaryAmount', $instance->getAmount());
        self::assertEquals($value['amount']['value'], $instance->getAmount()->getValue());
    }

    /**
     * @dataProvider invalidAmountDataProvider
     */
    public function testSetInvalidAmount($value)
    {
        $this->expectException('\InvalidArgumentException');
        $this->getTestInstance()->setAmount($value);
    }

    public function validDataProvider()
    {
        $result = array();
        for ($i = 0; $i < 5; $i++) {
            $result[] = array(
                array(
                    'type' => Random::value(SettlementPayoutPaymentType::getValidValues()),
                    'amount' => array(
                        'value' => sprintf('%.2f', round(Random::float(0.1, 99.99), 2)),
                        'currency' => Random::value(CurrencyCode::getValidValues()),
                    ),
                ),
            );
        }
        return $result;
    }

    public function invalidTypeDataProvider()
    {
        return array(
            array(null),
            array(''),
            array(1.0),
            array(1),
            array(true),
            array(false),
            array(array()),
            array(new stdClass()),
            array(Random::str(1, 10)),
        );
    }

    public function invalidAmountDataProvider()
    {
        return array(
            array(null),
            array(''),
            array(1.0),
            array(1),
            array(true),
            array(false),
            array(new stdClass()),
        );
    }
}
