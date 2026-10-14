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

namespace Tests\YooKassa\Request\SelfEmployed;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use YooKassa\Common\Exceptions\InvalidRequestException;
use YooKassa\Helpers\Random;
use YooKassa\Model\SelfEmployed\SelfEmployedConfirmationType;
use YooKassa\Request\SelfEmployed\SelfEmployedRequest;
use YooKassa\Request\SelfEmployed\SelfEmployedRequestBuilder;
use YooKassa\Request\SelfEmployed\SelfEmployedRequestConfirmation;
use YooKassa\Request\SelfEmployed\SelfEmployedRequestConfirmationFactory;
use YooKassa\Request\SelfEmployed\SelfEmployedRequestConfirmationRedirect;

class SelfEmployedRequestBuilderTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testBuilderMethods()
    {
        $builder = new SelfEmployedRequestBuilder();

        self::assertSame($builder, $builder->setItn(Random::str(12, 12, '0123456789')));
        self::assertSame($builder, $builder->setPhone(Random::str(11, 11, '0123456789')));

        $factory = new SelfEmployedRequestConfirmationFactory();
        $confirmation = $factory->factoryFromArray(array('type' => SelfEmployedConfirmationType::REDIRECT));
        self::assertSame($builder, $builder->setConfirmation($confirmation));
    }

    /**
     * @throws Exception
     */
    public function testBuildWithItn()
    {
        $builder = new SelfEmployedRequestBuilder();
        $itn = Random::str(12, 12, '0123456789');
        $builder->setItn($itn);
        $instance = $builder->build();

        self::assertSame($itn, $instance->getItn());
        self::assertNull($instance->getPhone());
        self::assertNull($instance->getConfirmation());
    }

    /**
     * @throws Exception
     */
    public function testBuildWithPhone()
    {
        $builder = new SelfEmployedRequestBuilder();
        $phone = Random::str(11, 11, '0123456789');
        $builder->setPhone($phone);
        $instance = $builder->build();

        self::assertNull($instance->getItn());
        self::assertSame($phone, $instance->getPhone());
        self::assertNull($instance->getConfirmation());
    }

    /**
     * @dataProvider confirmationDataProvider
     *
     * @param $confirmation
     * @throws Exception
     */
    public function testBuildWithConfirmation($confirmation)
    {
        $builder = new SelfEmployedRequestBuilder();
        $builder->setItn(Random::str(12, 12, '0123456789'));
        $builder->setConfirmation($confirmation);
        $instance = $builder->build();

        if (empty($confirmation)) {
            self::assertNull($instance->getConfirmation());
        } elseif ($confirmation instanceof SelfEmployedRequestConfirmation) {
            self::assertSame($confirmation, $instance->getConfirmation());
        } elseif (is_array($confirmation)) {
            self::assertEquals($confirmation, $instance->getConfirmation()->toArray());
        }
    }

    public function testBuildEmpty()
    {
        $builder = new SelfEmployedRequestBuilder();
        try {
            $builder->build();
        } catch (InvalidRequestException $e) {
            return;
        }
        self::fail('Exception not thrown in build method');
    }

    /**
     * @throws Exception
     */
    public function testSetOptions()
    {
        $builder = new SelfEmployedRequestBuilder();

        $builder->setOptions(array(
            'itn' => Random::str(12, 12, '0123456789'),
        ));
        $instance = $builder->build();
        self::assertTrue($instance instanceof SelfEmployedRequest);

        $builder->setOptions(array());
        try {
            $builder->build();
        } catch (InvalidRequestException $e) {
            try {
                $builder->setOptions('test');
            } catch (Exception $e) {
                self::assertTrue($e instanceof InvalidArgumentException);
                return;
            }
            self::fail('Exception not thrown in setOptions method');
            return;
        }
        self::fail('Exception not thrown in build method');
    }

    /**
     * @dataProvider invalidConfirmationDataProvider
     *
     * @param $value
     */
    public function testSetInvalidConfirmation($value)
    {
        $builder = new SelfEmployedRequestBuilder();
        try {
            $builder->setConfirmation($value);
        } catch (Exception $e) {
            self::assertTrue($e instanceof InvalidArgumentException);
            return;
        }
        self::fail('Exception not thrown in setConfirmation method');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function confirmationDataProvider()
    {
        $factory = new SelfEmployedRequestConfirmationFactory();

        $result = array(
            array(null),
            array(
                $factory->factoryFromArray(array('type' => SelfEmployedConfirmationType::REDIRECT)),
            ),
            array(
                array('type' => SelfEmployedConfirmationType::REDIRECT),
            ),
        );

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function invalidConfirmationDataProvider()
    {
        return array(
            array(false),
            array(true),
            array(new stdClass()),
            array(Random::str(10)),
        );
    }
}
