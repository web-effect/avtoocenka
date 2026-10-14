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

namespace Tests\YooKassa\Request\PersonalData;

use Exception;
use PHPUnit\Framework\TestCase;
use YooKassa\Helpers\Random;
use YooKassa\Model\Metadata;
use YooKassa\Model\PersonalData\PersonalDataType;
use YooKassa\Request\PersonalData\CreatePersonalDataRequest;
use YooKassa\Request\PersonalData\CreatePersonalDataRequestSerializer;

class CreatePersonalDataRequestSerializerTest extends TestCase
{
    /**
     * @dataProvider validDataProvider
     *
     * @param $options
     */
    public function testSerialize($options)
    {
        $serializer = new CreatePersonalDataRequestSerializer();
        $instance = CreatePersonalDataRequest::builder()->build($options);
        $data = $serializer->serialize($instance);

        $expected = array(
            'type' => $options['type'],
            'last_name' => $options['lastName'],
            'first_name' => $options['firstName'],
        );

        if (!empty($options['middleName'])) {
            $expected['middle_name'] = $options['middleName'];
        }

        if (!empty($options['metadata'])) {
            $expected['metadata'] = array();
            foreach ($options['metadata'] as $key => $value) {
                $expected['metadata'][$key] = $value;
            }
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
            $request = array(
                'type' => PersonalDataType::SBP_PAYOUT_RECIPIENT,
                'lastName' => Random::str(1, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME),
                'firstName' => Random::str(1, CreatePersonalDataRequest::MAX_LENGTH_FIRST_NAME),
            );

            if (Random::bool()) {
                $request['middleName'] = Random::str(1, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME);
            }

            if (Random::bool()) {
                $request['metadata'] = array(
                    Random::str(3, 10, 'abcdefghijklmnopqrstuvwxyz') => Random::str(3, 20),
                );
            }

            $result[] = array($request);
        }

        return $result;
    }

    public function testSerializeEmptyMetadata()
    {
        $serializer = new CreatePersonalDataRequestSerializer();
        $instance = CreatePersonalDataRequest::builder()->build(array(
            'type' => PersonalDataType::SBP_PAYOUT_RECIPIENT,
            'lastName' => Random::str(10),
            'firstName' => Random::str(10),
        ));
        $data = $serializer->serialize($instance);

        $expected = array(
            'type' => PersonalDataType::SBP_PAYOUT_RECIPIENT,
            'last_name' => $instance->getLastName(),
            'first_name' => $instance->getFirstName(),
        );

        self::assertEquals($expected, $data);
    }

    public function testSerializeWithAllFields()
    {
        $serializer = new CreatePersonalDataRequestSerializer();
        $instance = CreatePersonalDataRequest::builder()->build(array(
            'type' => PersonalDataType::SBP_PAYOUT_RECIPIENT,
            'lastName' => 'Иванов',
            'firstName' => 'Иван',
            'middleName' => 'Иванович',
            'metadata' => array(
                'order_id' => '12345',
                'source' => 'web',
            ),
        ));
        $data = $serializer->serialize($instance);

        $expected = array(
            'type' => PersonalDataType::SBP_PAYOUT_RECIPIENT,
            'last_name' => 'Иванов',
            'first_name' => 'Иван',
            'middle_name' => 'Иванович',
            'metadata' => array(
                'order_id' => '12345',
                'source' => 'web',
            ),
        );

        self::assertEquals($expected, $data);
    }
}
