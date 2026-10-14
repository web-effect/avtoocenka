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

namespace Tests\YooKassa\Helpers;

use Exception;
use PHPUnit\Framework\TestCase;
use YooKassa\Helpers\SecurityHelper;

class SecurityHelperTest extends TestCase
{
    /**
     * @dataProvider trustedIPv4DataProvider
     * @param string $ip
     */
    public function testTrustedIPv4($ip)
    {
        $helper = new SecurityHelper();
        self::assertTrue($helper->isIPTrusted($ip), 'IP ' . $ip . ' should be trusted');
    }

    /**
     * @dataProvider notTrustedIPv4DataProvider
     * @param string $ip
     */
    public function testNotTrustedIPv4($ip)
    {
        $helper = new SecurityHelper();
        self::assertFalse($helper->isIPTrusted($ip), 'IP ' . $ip . ' should NOT be trusted');
    }

    /**
     * @dataProvider trustedIPv6DataProvider
     * @param string $ip
     */
    public function testTrustedIPv6($ip)
    {
        $helper = new SecurityHelper();
        self::assertTrue($helper->isIPTrusted($ip), 'IPv6 ' . $ip . ' should be trusted');
    }

    /**
     * @dataProvider notTrustedIPv6DataProvider
     * @param string $ip
     */
    public function testNotTrustedIPv6($ip)
    {
        $helper = new SecurityHelper();
        self::assertFalse($helper->isIPTrusted($ip), 'IPv6 ' . $ip . ' should NOT be trusted');
    }

    /**
     * @dataProvider invalidIPDataProvider
     * @param string $ip
     */
    public function testInvalidIP($ip)
    {
        $helper = new SecurityHelper();
        $result = $helper->isIPTrusted($ip);
        self::assertFalse($result, 'Invalid IP "' . $ip . '" should return false');
    }

    public function trustedIPv4DataProvider()
    {
        return array(
            'Range 185.71.76.0/27 - first'    => array('185.71.76.1'),
            'Range 185.71.76.0/27 - last'     => array('185.71.76.30'),
            'Range 185.71.77.0/27 - first'    => array('185.71.77.1'),
            'Range 77.75.153.0/25 - first'    => array('77.75.153.1'),
            'Range 77.75.154.128/25 - first'  => array('77.75.154.129'),
            'Exact IP 77.75.156.11'           => array('77.75.156.11'),
            'Exact IP 77.75.156.35'           => array('77.75.156.35'),
        );
    }

    public function notTrustedIPv4DataProvider()
    {
        return array(
            'Google DNS'     => array('8.8.8.8'),
            'Localhost'      => array('127.0.0.1'),
            'Private range'  => array('192.168.1.1'),
            'Another private' => array('10.0.0.1'),
        );
    }

    public function trustedIPv6DataProvider()
    {
        return array(
            'First range - first IP'    => array('2a02:5180:0000:1509:0000:0000:0000:0001'),
            'First range - mid IP'      => array('2a02:5180:0000:1509:0000:0000:0000:1000'),
            'First range - last IP'     => array('2a02:5180:0000:1509:ffff:ffff:ffff:ffff'),
            'Second range - first IP'   => array('2a02:5180:0000:2655:0000:0000:0000:0000'),
            'Third range - first IP'    => array('2a02:5180:0000:1533:0000:0000:0000:0000'),
            'Fourth range - first IP'   => array('2a02:5180:0000:2669:0000:0000:0000:0000'),
        );
    }

    public function notTrustedIPv6DataProvider()
    {
        return array(
            'Outside all ranges'        => array('2a02:5180:0000:0000:0000:0000:0000:0001'),
            'Google DNS IPv6'           => array('2001:4860:4860::8888'),
            'Localhost IPv6'            => array('::1'),
        );
    }

    public function invalidIPDataProvider()
    {
        return array(
            'Not an IP string' => array('not_an_ip'),
            'Empty string'     => array(''),
        );
    }
}
