<?php
class Changes extends PHPUnit_Framework_TestCase
{
    public function testDeletedString()
    {
        $processor = new \Kizilare\Changes\Processor();
        $changes = $processor->showChange( 'This is a test', 'This is test' );
        $this->assertEquals( 'This is <del>a</del> test', $changes );
    }

    public function testAddedString()
    {
        $processor = new \Kizilare\Changes\Processor();
        $changes = $processor->showChange( 'This is test', 'This is a test' );
        $this->assertEquals( 'This is <ins>a</ins> test', $changes );
    }
}