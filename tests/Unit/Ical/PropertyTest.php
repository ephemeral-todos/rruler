<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Property;
use PHPUnit\Framework\TestCase;

final class PropertyTest extends TestCase
{
    public function testCreatePropertyWithNameAndValue(): void
    {
        $property = new Property('SUMMARY', 'Test Event');

        $this->assertEquals('SUMMARY', $property->getName());
        $this->assertEquals('Test Event', $property->getValue());
        $this->assertEquals([], $property->getParameters());
    }

    public function testCreatePropertyWithParameters(): void
    {
        $parameters = ['TZID' => 'America/New_York', 'VALUE' => 'DATE-TIME'];
        $property = new Property('DTSTART', '20250107T100000', $parameters);

        $this->assertEquals('DTSTART', $property->getName());
        $this->assertEquals('20250107T100000', $property->getValue());
        $this->assertEquals($parameters, $property->getParameters());
    }

    public function testGetParameterValue(): void
    {
        $parameters = ['TZID' => 'America/New_York', 'VALUE' => 'DATE-TIME'];
        $property = new Property('DTSTART', '20250107T100000', $parameters);

        $this->assertEquals('America/New_York', $property->getParameter('TZID'));
        $this->assertEquals('DATE-TIME', $property->getParameter('VALUE'));
        $this->assertNull($property->getParameter('NONEXISTENT'));
    }

    public function testGetParameterWithDefault(): void
    {
        $property = new Property('SUMMARY', 'Test Event');

        $this->assertEquals('default', $property->getParameter('NONEXISTENT', 'default'));
        $this->assertNull($property->getParameter('NONEXISTENT'));
    }

    public function testHasParameter(): void
    {
        $parameters = ['TZID' => 'America/New_York'];
        $property = new Property('DTSTART', '20250107T100000', $parameters);

        $this->assertTrue($property->hasParameter('TZID'));
        $this->assertFalse($property->hasParameter('VALUE'));
        $this->assertFalse($property->hasParameter('NONEXISTENT'));
    }

    public function testParameterNamesAreCaseInsensitive(): void
    {
        $parameters = ['TZID' => 'America/New_York'];
        $property = new Property('DTSTART', '20250107T100000', $parameters);

        $this->assertTrue($property->hasParameter('TZID'));
        $this->assertTrue($property->hasParameter('tzid'));
        $this->assertTrue($property->hasParameter('TzId'));

        $this->assertEquals('America/New_York', $property->getParameter('TZID'));
        $this->assertEquals('America/New_York', $property->getParameter('tzid'));
        $this->assertEquals('America/New_York', $property->getParameter('TzId'));
    }

    public function testPropertyIsImmutable(): void
    {
        $parameters = ['TZID' => 'America/New_York'];
        $property = new Property('DTSTART', '20250107T100000', $parameters);

        $retrievedParameters = $property->getParameters();
        $retrievedParameters['NEW_PARAM'] = 'value';

        // Original property should be unchanged
        $this->assertFalse($property->hasParameter('NEW_PARAM'));
        $this->assertEquals(['TZID' => 'America/New_York'], $property->getParameters());
    }

    public function testEmptyParametersArray(): void
    {
        $property = new Property('SUMMARY', 'Test Event', []);

        $this->assertEquals([], $property->getParameters());
        $this->assertFalse($property->hasParameter('ANY'));
        $this->assertNull($property->getParameter('ANY'));
    }
}
