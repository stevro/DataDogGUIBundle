<?php

namespace Stev\DataDogAuditGUIBundle\Tests\Twig;

use Stev\DataDogAuditGUIBundle\Twig\TwigExtension;

// Loaded explicitly so the test exercises this checkout even when an older copy of the
// bundle is installed in the autoloader's vendor directory.
require_once __DIR__.'/../../Twig/TwigExtension.php';

class TwigExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var TwigExtension */
    private $extension;

    protected function setUp()
    {
        $this->extension = new TwigExtension();
    }

    protected function tearDown()
    {
        $this->extension = null;
    }

    public function testListOfAssociativeArraysIsRenderedItemByItemWithKeys()
    {
        $stops = [
            ['stop_number' => 1, 'address' => 'Str. Ștefan cel Mare 1, București'],
            ['stop_number' => 2, 'address' => 'Calea Victoriei 100'],
        ];

        $this->assertSame(
            'routeStops: stop_number: 1, address: Str. Ștefan cel Mare 1, București | stop_number: 2, address: Calea Victoriei 100',
            $this->extension->formatAuditData('routeStops', $stops)
        );
    }

    public function testNestedArraysNeverRaiseArrayToStringConversion()
    {
        $data = ['a' => ['x' => 1, 'y' => [true, null]], 'b' => 'plain'];

        $this->assertSame(
            'nested: a: x: 1, y: DA, - | b: plain',
            $this->extension->formatAuditData('nested', $data)
        );
    }

    public function testListOfListsDropsNumericKeys()
    {
        $this->assertSame('m: 1, 2 | 3, 4', $this->extension->formatAuditData('m', [[1, 2], [3, 4]]));
    }

    public function testFlatArrayKeepsLegacyCommaSeparatedValues()
    {
        $this->assertSame('tags: a, b', $this->extension->formatAuditData('tags', ['a', 'b']));
        $this->assertSame('startLocation: 5', $this->extension->formatAuditData('startLocation', ['id' => 5]));
    }

    public function testEmptyValuesRenderAsDash()
    {
        $this->assertSame('f: -', $this->extension->formatAuditData('f', null));
        $this->assertSame('f: -', $this->extension->formatAuditData('f', []));
        $this->assertSame('f: -', $this->extension->formatAuditData('f', ''));
        $this->assertSame('-', $this->extension->formatKeylessAuditData(null));
        $this->assertSame('-', $this->extension->formatKeylessAuditData([]));
    }

    public function testZeroIsNotTreatedAsEmpty()
    {
        $this->assertSame('n: 0', $this->extension->formatAuditData('n', 0));
        $this->assertSame('0', $this->extension->formatKeylessAuditData('0'));
    }

    public function testBooleansRenderAsDaNu()
    {
        $this->assertSame('active: DA', $this->extension->formatAuditData('active', true));
        $this->assertSame('active: NU', $this->extension->formatAuditData('active', false));
        $this->assertSame('NU', $this->extension->formatKeylessAuditData(false));
    }

    public function testDateTimeIsRenderedInTheRequestedTimezoneWithoutMutatingIt()
    {
        $date = new \DateTime('2026-10-29 13:15:00', new \DateTimeZone('UTC'));

        $this->assertSame(
            'shipmentDate: 29-10-2026 15:15:00 EET',
            $this->extension->formatAuditData('shipmentDate', $date, 'Europe/Bucharest')
        );
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame('29-10-2026 15:15:00 EET', $this->extension->formatKeylessAuditData($date, 'Europe/Bucharest'));
    }

    public function testObjectsUseToStringThenGetName()
    {
        $stringable = new StringableStub();
        $named = new NamedStub();

        $this->assertSame('o: stringable', $this->extension->formatAuditData('o', $stringable));
        $this->assertSame('o: named', $this->extension->formatAuditData('o', $named));
        $this->assertSame('named', $this->extension->formatKeylessAuditData($named));
    }

    public function testUnrenderableObjectThrowsWithTheKeyInTheMessage()
    {
        $this->expectException('\Exception');
        $this->expectExceptionMessageRegExp('/for key opaque with object/');

        $this->extension->formatAuditData('opaque', new \stdClass());
    }

    public function testUnrenderableObjectThrowsWithoutAKeyInKeylessMode()
    {
        $this->expectException('\Exception');
        $this->expectExceptionMessageRegExp('/^Unable to render audit data with object stdClass$/');

        $this->extension->formatKeylessAuditData(new \stdClass());
    }

    public function testScalarsRenderUnchanged()
    {
        $this->assertSame('uit: 7R6W4J8V0V1E7506', $this->extension->formatAuditData('uit', '7R6W4J8V0V1E7506'));
        $this->assertSame('3.5', $this->extension->formatKeylessAuditData(3.5));
    }
}

class StringableStub
{
    public function __toString()
    {
        return 'stringable';
    }
}

class NamedStub
{
    public function getName()
    {
        return 'named';
    }
}
