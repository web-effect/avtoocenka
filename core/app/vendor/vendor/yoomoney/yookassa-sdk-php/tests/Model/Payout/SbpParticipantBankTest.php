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

namespace Tests\YooKassa\Model\Payout;

use PHPUnit\Framework\TestCase;
use YooKassa\Helpers\Random;
use YooKassa\Model\Payout\SbpParticipantBank;

class SbpParticipantBankTest extends TestCase
{
    /**
     * @dataProvider validDataProvider
     */
    public function testGetSetBankId($value)
    {
        $instance = new SbpParticipantBank();
        self::assertNull($instance->getBankId());
        self::assertNull($instance->bankId);
        self::assertNull($instance->bank_id);
        $instance->setBankId($value);
        self::assertEquals($value, $instance->getBankId());
        self::assertEquals($value, $instance->bankId);
        self::assertEquals($value, $instance->bank_id);

        $instance = new SbpParticipantBank();
        $instance->bankId = $value;
        self::assertEquals($value, $instance->getBankId());

        $instance = new SbpParticipantBank();
        $instance->bank_id = $value;
        self::assertEquals($value, $instance->getBankId());
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testGetSetName($value)
    {
        $instance = new SbpParticipantBank();
        self::assertNull($instance->getName());
        self::assertNull($instance->name);
        $instance->setName($value);
        self::assertEquals($value, $instance->getName());
        self::assertEquals($value, $instance->name);
    }

    /**
     * @dataProvider validDataProvider
     */
    public function testGetSetBic($value)
    {
        $instance = new SbpParticipantBank();
        self::assertNull($instance->getBic());
        self::assertNull($instance->bic);
        $instance->setBic($value);
        self::assertEquals($value, $instance->getBic());
        self::assertEquals($value, $instance->bic);
    }

    public function validDataProvider()
    {
        return array(
            array(Random::str(1)),
            array(Random::str(2, 64)),
            array(null),
            array(''),
            array(123),
        );
    }
}
