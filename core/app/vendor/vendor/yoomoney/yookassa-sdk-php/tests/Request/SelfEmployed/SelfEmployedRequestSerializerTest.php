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
use PHPUnit\Framework\TestCase;
use YooKassa\Helpers\Random;
use YooKassa\Model\SelfEmployed\SelfEmployedConfirmationType;
use YooKassa\Request\SelfEmployed\SelfEmployedRequest;
use YooKassa\Request\SelfEmployed\SelfEmployedRequestSerializer;

class SelfEmployedRequestSerializerTest extends TestCase
{
    /**
     * @dataProvider validDataProvider
     *
     * @param $options
     */
    public function testSerialize($options)
    {
        $serializer = new SelfEmployedRequestSerializer();
        $instance = SelfEmployedRequest::builder()->build($options);
        $data = $serializer->serialize($instance);

        $expected = array();

        if (!empty($options['itn'])) {
            $expected['itn'] = preg_replace('/\D/', '', $options['itn']);
        }

        if (!empty($options['phone'])) {
            $expected['phone'] = preg_replace('/\D/', '', $options['phone']);
        }

        if (!empty($options['confirmation'])) {
            $expected['confirmation'] = $instance->getConfirmation()->toArray();
        }

        self::assertEquals($expected, $data);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function validDataProvider()
    {
        $result = array();

        for ($i = 0; $i < 10; $i++) {
            $request = array();

            $request['itn'] = (string)Random::int(1000000000, 9999999999);

            if (Random::bool()) {
                $request['phone'] = Random::str(1, 15, '0123456789');
            }

            if (Random::bool()) {
                $request['confirmation'] = array(
                    'type' => Random::value(SelfEmployedConfirmationType::getValidValues()),
                );
            }

            $result[] = array($request);
        }

        return $result;
    }

    public function testSerializeWithItnOnly()
    {
        $serializer = new SelfEmployedRequestSerializer();
        $instance = SelfEmployedRequest::builder()->build(array(
            'itn' => '1234567890',
        ));
        $data = $serializer->serialize($instance);

        $expected = array(
            'itn' => '1234567890',
        );

        self::assertEquals($expected, $data);
    }

    public function testSerializeWithPhoneOnly()
    {
        $serializer = new SelfEmployedRequestSerializer();
        $instance = SelfEmployedRequest::builder()->build(array(
            'phone' => '79000000000',
        ));
        $data = $serializer->serialize($instance);

        $expected = array(
            'phone' => '79000000000',
        );

        self::assertEquals($expected, $data);
    }

    public function testSerializeWithAllFields()
    {
        $serializer = new SelfEmployedRequestSerializer();
        $instance = SelfEmployedRequest::builder()->build(array(
            'itn' => '1234567890',
            'phone' => '79000000000',
            'confirmation' => array(
                'type' => SelfEmployedConfirmationType::REDIRECT,
            ),
        ));
        $data = $serializer->serialize($instance);

        self::assertArrayHasKey('itn', $data);
        self::assertArrayHasKey('phone', $data);
        self::assertArrayHasKey('confirmation', $data);
        self::assertEquals('1234567890', $data['itn']);
        self::assertEquals('79000000000', $data['phone']);
        self::assertEquals(
            array('type' => SelfEmployedConfirmationType::REDIRECT),
            $data['confirmation']
        );
    }
}
