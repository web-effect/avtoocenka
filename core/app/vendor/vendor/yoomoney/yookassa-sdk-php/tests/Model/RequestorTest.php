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

namespace Tests\YooKassa\Model;

use PHPUnit\Framework\TestCase;
use stdClass;
use YooKassa\Helpers\Random;
use YooKassa\Model\Requestor;

class RequestorTest extends TestCase
{
    /**
     * @dataProvider validDataProvider
     */
    public function testGetSetType($value)
    {
        $instance = new Requestor();
        self::assertNull($instance->getType());
        self::assertNull($instance->type);
        $instance->setType($value);
        self::assertEquals((string)$value, $instance->getType());
        self::assertEquals((string)$value, $instance->type);

        $instance = new Requestor();
        $instance->type = $value;
        self::assertEquals((string)$value, $instance->getType());
        self::assertEquals((string)$value, $instance->type);
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSetInvalidType($value)
    {
        $this->expectException('\InvalidArgumentException');
        $instance = new Requestor();
        $instance->setType($value);
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testSetterInvalidType($value)
    {
        $this->expectException('\InvalidArgumentException');
        $instance = new Requestor();
        $instance->type = $value;
    }

    /**
     * @dataProvider nullableDataProvider
     */
    public function testGetSetAccountId($value)
    {
        $instance = new Requestor();
        self::assertNull($instance->getAccountId());
        self::assertNull($instance->accountId);
        self::assertNull($instance->account_id);
        $instance->setAccountId($value);
        self::assertSame($value, $instance->getAccountId());
        self::assertSame($value, $instance->accountId);
        self::assertSame($value, $instance->account_id);

        $instance = new Requestor();
        $instance->accountId = $value;
        self::assertSame($value, $instance->getAccountId());
        self::assertSame($value, $instance->accountId);
        self::assertSame($value, $instance->account_id);

        $instance = new Requestor();
        $instance->account_id = $value;
        self::assertSame($value, $instance->getAccountId());
        self::assertSame($value, $instance->accountId);
        self::assertSame($value, $instance->account_id);
    }

    /**
     * @dataProvider nullableDataProvider
     */
    public function testGetSetClientId($value)
    {
        $instance = new Requestor();
        self::assertNull($instance->getClientId());
        self::assertNull($instance->clientId);
        self::assertNull($instance->client_id);
        $instance->setClientId($value);
        self::assertSame($value, $instance->getClientId());
        self::assertSame($value, $instance->clientId);
        self::assertSame($value, $instance->client_id);
    }

    /**
     * @dataProvider nullableDataProvider
     */
    public function testGetSetClientName($value)
    {
        $instance = new Requestor();
        self::assertNull($instance->getClientName());
        self::assertNull($instance->clientName);
        self::assertNull($instance->client_name);
        $instance->setClientName($value);
        self::assertSame($value, $instance->getClientName());
        self::assertSame($value, $instance->clientName);
        self::assertSame($value, $instance->client_name);
    }

    public function validDataProvider()
    {
        return array(
            array(Random::str(1)),
            array(Random::str(2, 64)),
        );
    }

    public function nullableDataProvider()
    {
        return array(
            array(null),
            array(Random::str(1)),
            array(Random::str(2, 64)),
        );
    }

    public function invalidDataProvider()
    {
        return array(
            array(''),
            array(true),
            array(false),
            array(array()),
            array(new stdClass()),
        );
    }
}
