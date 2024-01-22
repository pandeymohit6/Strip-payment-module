<?php

namespace Mohit\Stripe\Test\Unit\Gateway\Helper;

use Mohit\Stripe\Gateway\Helper\SubjectReader;

class SubjectReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    protected function setUp()
    {
        $this->subjectReader = new SubjectReader();
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Helper\SubjectReader::readCustomerId
     * 
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage The "customerId" field does not exists
     */
    public function testReadCustomerIdWithException()
    {
        $this->subjectReader->readCustomerId([]);
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Helper\SubjectReader::readCustomerId
     */
    public function testReadCustomerId()
    {
        $customerId = 1;
        static::assertEquals($customerId, $this->subjectReader->readCustomerId(['customer_id' => $customerId]));
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Helper\SubjectReader::readPublicHash
     * 
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "public_hash" field does not exists
     */
    public function testReadPublicHashWithException()
    {
        $this->subjectReader->readPublicHash([]);
    }

    /**
     * @covers \Mohit\Stripe\Gateway\Helper\SubjectReader::readPublicHash
     */
    public function testReadPublicHash()
    {
        $hash = sha1(rand());
        static::assertEquals($hash, $this->subjectReader->readPublicHash(['public_hash' => $hash]));
    }
}
