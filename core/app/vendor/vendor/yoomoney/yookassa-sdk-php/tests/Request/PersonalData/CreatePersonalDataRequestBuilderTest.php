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
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use YooKassa\Common\Exceptions\InvalidRequestException;
use YooKassa\Helpers\Random;
use YooKassa\Model\Metadata;
use YooKassa\Model\PersonalData\PersonalDataType;
use YooKassa\Request\PersonalData\CreatePersonalDataRequest;
use YooKassa\Request\PersonalData\CreatePersonalDataRequestBuilder;

class CreatePersonalDataRequestBuilderTest extends TestCase
{
    /**
     * @return array
     * @throws Exception
     */
    protected function getRequiredData()
    {
        return array(
            'type' => Random::value(PersonalDataType::getEnabledValues()),
            'lastName' => Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME),
            'firstName' => Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_FIRST_NAME),
        );
    }

    /**
     * @throws Exception
     */
    public function testBuilderMethods()
    {
        $builder = new CreatePersonalDataRequestBuilder();

        self::assertSame($builder, $builder->setType(Random::value(PersonalDataType::getEnabledValues())));
        self::assertSame($builder, $builder->setLastName(Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME)));
        self::assertSame($builder, $builder->setFirstName(Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_FIRST_NAME)));
        self::assertSame($builder, $builder->setMiddleName(Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME)));
        self::assertSame($builder, $builder->setMetadata(array('test' => 'test')));
    }

    public function testBuildEmpty()
    {
        $builder = new CreatePersonalDataRequestBuilder();
        try {
            $builder->build();
        } catch (InvalidRequestException $e) {
            return;
        }
        self::fail('Exception not thrown in build method');
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param $options
     * @throws Exception
     */
    public function testBuild($options)
    {
        $builder = new CreatePersonalDataRequestBuilder();
        $builder->setType($options['type']);
        $builder->setLastName($options['lastName']);
        $builder->setFirstName($options['firstName']);
        if (array_key_exists('middleName', $options)) {
            $builder->setMiddleName($options['middleName']);
        }
        if (array_key_exists('metadata', $options)) {
            $builder->setMetadata($options['metadata']);
        }
        $instance = $builder->build();

        self::assertSame($options['type'], $instance->getType());
        self::assertSame($options['lastName'], $instance->getLastName());
        self::assertSame($options['firstName'], $instance->getFirstName());

        if (array_key_exists('middleName', $options) && !empty($options['middleName'])) {
            self::assertSame($options['middleName'], $instance->getMiddleName());
        } else {
            self::assertNull($instance->getMiddleName());
        }

        if (array_key_exists('metadata', $options) && !empty($options['metadata'])) {
            if ($options['metadata'] instanceof Metadata) {
                self::assertEquals($options['metadata']->toArray(), $instance->getMetadata()->toArray());
            } else {
                self::assertEquals($options['metadata'], $instance->getMetadata()->toArray());
            }
        } else {
            self::assertNull($instance->getMetadata());
        }
    }

    /**
     * @throws Exception
     */
    public function testSetOptions()
    {
        $builder = new CreatePersonalDataRequestBuilder();

        $builder->setOptions($this->getRequiredData());
        $instance = $builder->build();
        self::assertTrue($instance instanceof CreatePersonalDataRequest);

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
     * @dataProvider validDataProvider
     *
     * @param $options
     * @throws Exception
     */
    public function testSetType($options)
    {
        $builder = new CreatePersonalDataRequestBuilder();
        $builder->setType($options['type']);
        $builder->setLastName($options['lastName']);
        $builder->setFirstName($options['firstName']);
        $instance = $builder->build();
        self::assertSame($options['type'], $instance->getType());
    }

    /**
     * @dataProvider invalidTypeDataProvider
     *
     * @param $value
     */
    public function testSetInvalidType($value)
    {
        $builder = new CreatePersonalDataRequestBuilder();
        try {
            $builder->setType($value);
        } catch (Exception $e) {
            self::assertTrue($e instanceof InvalidArgumentException);
            return;
        }
        self::fail('Exception not thrown in setType method');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function validDataProvider()
    {
        $metadata = new Metadata();
        $metadata->test = 'test';

        $result = array();
        for ($i = 0; $i < 10; $i++) {
            $options = array(
                'type' => Random::value(PersonalDataType::getEnabledValues()),
                'lastName' => Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME),
                'firstName' => Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_FIRST_NAME),
                'middleName' => ($i % 2) ? Random::str(5, CreatePersonalDataRequest::MAX_LENGTH_LAST_NAME) : null,
                'metadata' => ($i % 3) ? $metadata : array('test' => 'test'),
            );
            $result[] = array($options);
        }

        return $result;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function invalidTypeDataProvider()
    {
        return array(
            array(false),
            array(true),
            array(new stdClass()),
            array(Random::str(10)),
        );
    }
}
