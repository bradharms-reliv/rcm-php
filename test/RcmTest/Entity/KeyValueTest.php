<?php


namespace RcmTest\Entity;


use Rcm\Entity\KeyValue;

class KeyValueTest extends \PHPUnit_Framework_TestCase
{
    /** @var  \Rcm\Entity\KeyValue */
    protected $keyValue;

    public function setUp()
    {
        $this->keyValue = new KeyValue();
    }

    /**
     * @covers \Rcm\Entity\KeyValue
     */
    public function testSetGetKeyName()
    {
        $key = 'testKey';
        $this->keyValue->setKeyName($key);
        $this->assertEquals($this->keyValue->getKeyName(), $key);
    }

    /**
     * @covers \Rcm\Entity\KeyValue
     */
    public function testSetGetValue()
    {
        $value = 'testValue';
        $this->keyValue->setValue($value);
        $this->assertEquals($this->keyValue->getValue(), $value);
    }

} 