<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Component;
use EphemeralTodos\Rruler\Ical\Property;
use PHPUnit\Framework\TestCase;

final class ComponentTest extends TestCase
{
    public function testCreateComponentWithTypeOnly(): void
    {
        $component = new Component('VEVENT');

        $this->assertEquals('VEVENT', $component->getType());
        $this->assertEmpty($component->getProperties());
        $this->assertEmpty($component->getChildren());
    }

    public function testCreateComponentWithProperties(): void
    {
        $properties = [
            new Property('SUMMARY', 'Test Event'),
            new Property('DTSTART', '20250107T100000Z'),
        ];

        $component = new Component('VEVENT', $properties);

        $this->assertEquals('VEVENT', $component->getType());
        $this->assertCount(2, $component->getProperties());
        $this->assertEquals($properties, $component->getProperties());
    }

    public function testCreateComponentWithChildren(): void
    {
        $childComponent = new Component('VEVENT');
        $children = [$childComponent];

        $component = new Component('VCALENDAR', [], $children);

        $this->assertEquals('VCALENDAR', $component->getType());
        $this->assertEmpty($component->getProperties());
        $this->assertCount(1, $component->getChildren());
        $this->assertEquals($children, $component->getChildren());
    }

    public function testGetPropertyByName(): void
    {
        $summaryProperty = new Property('SUMMARY', 'Test Event');
        $dtStartProperty = new Property('DTSTART', '20250107T100000Z');

        $component = new Component('VEVENT', [$summaryProperty, $dtStartProperty]);

        $this->assertEquals($summaryProperty, $component->getProperty('SUMMARY'));
        $this->assertEquals($dtStartProperty, $component->getProperty('DTSTART'));
        $this->assertNull($component->getProperty('RRULE'));
    }

    public function testGetPropertyIsCaseInsensitive(): void
    {
        $summaryProperty = new Property('SUMMARY', 'Test Event');
        $component = new Component('VEVENT', [$summaryProperty]);

        $this->assertEquals($summaryProperty, $component->getProperty('SUMMARY'));
        $this->assertEquals($summaryProperty, $component->getProperty('summary'));
        $this->assertEquals($summaryProperty, $component->getProperty('Summary'));
    }

    public function testHasProperty(): void
    {
        $summaryProperty = new Property('SUMMARY', 'Test Event');
        $component = new Component('VEVENT', [$summaryProperty]);

        $this->assertTrue($component->hasProperty('SUMMARY'));
        $this->assertTrue($component->hasProperty('summary'));
        $this->assertFalse($component->hasProperty('RRULE'));
        $this->assertFalse($component->hasProperty('DTSTART'));
    }

    public function testGetPropertiesByName(): void
    {
        $attendee1 = new Property('ATTENDEE', 'mailto:john@example.com');
        $attendee2 = new Property('ATTENDEE', 'mailto:jane@example.com');
        $summary = new Property('SUMMARY', 'Test Event');

        $component = new Component('VEVENT', [$attendee1, $summary, $attendee2]);

        $attendees = $component->getPropertiesByName('ATTENDEE');
        $this->assertCount(2, $attendees);
        $this->assertEquals([$attendee1, $attendee2], $attendees);

        $summaries = $component->getPropertiesByName('SUMMARY');
        $this->assertCount(1, $summaries);
        $this->assertEquals([$summary], $summaries);

        $rrules = $component->getPropertiesByName('RRULE');
        $this->assertEmpty($rrules);
    }

    public function testGetPropertiesByNameIsCaseInsensitive(): void
    {
        $attendee = new Property('ATTENDEE', 'mailto:john@example.com');
        $component = new Component('VEVENT', [$attendee]);

        $this->assertEquals([$attendee], $component->getPropertiesByName('ATTENDEE'));
        $this->assertEquals([$attendee], $component->getPropertiesByName('attendee'));
        $this->assertEquals([$attendee], $component->getPropertiesByName('Attendee'));
    }

    public function testAddProperty(): void
    {
        $component = new Component('VEVENT');
        $property = new Property('SUMMARY', 'Test Event');

        $component->addProperty($property);

        $this->assertCount(1, $component->getProperties());
        $this->assertEquals($property, $component->getProperty('SUMMARY'));
    }

    public function testAddChild(): void
    {
        $parent = new Component('VCALENDAR');
        $child = new Component('VEVENT');

        $parent->addChild($child);

        $this->assertCount(1, $parent->getChildren());
        $this->assertEquals($child, $parent->getChildren()[0]);
    }

    public function testComponentIsImmutableAfterConstruction(): void
    {
        $originalProperties = [new Property('SUMMARY', 'Test Event')];
        $originalChildren = [new Component('VEVENT')];

        $component = new Component('VCALENDAR', $originalProperties, $originalChildren);

        // Modifying original arrays should not affect component
        $originalProperties[] = new Property('DTSTART', '20250107T100000Z');
        $originalChildren[] = new Component('VTODO');

        $this->assertCount(1, $component->getProperties());
        $this->assertCount(1, $component->getChildren());
    }

    public function testGetPropertiesReturnsNewArray(): void
    {
        $property = new Property('SUMMARY', 'Test Event');
        $component = new Component('VEVENT', [$property]);

        $properties = $component->getProperties();
        $properties[] = new Property('DTSTART', '20250107T100000Z');

        // Original component should be unchanged
        $this->assertCount(1, $component->getProperties());
    }

    public function testGetChildrenReturnsNewArray(): void
    {
        $child = new Component('VEVENT');
        $component = new Component('VCALENDAR', [], [$child]);

        $children = $component->getChildren();
        $children[] = new Component('VTODO');

        // Original component should be unchanged
        $this->assertCount(1, $component->getChildren());
    }
}
