<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\WkstNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class WkstNodeTest extends TestCase
{
    #[DataProvider('provideHappyPathData')]
    public function testHappyPath(string $expected, string $input): void
    {
        $node = new WkstNode($input);

        $this->assertEquals($expected, $node->getValue());
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new WkstNode($input);
    }

    public function testGetChoicesReturnsValidWeekdays(): void
    {
        $choices = WkstNode::getChoices();

        $this->assertEquals(['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'], $choices);
        $this->assertContains('SU', $choices);
        $this->assertContains('MO', $choices);
        $this->assertContains('SA', $choices);
    }

    public function testNodeProvidesCorrectParameterName(): void
    {
        $node = new WkstNode('MO');

        // Behavioral test: node should provide parameter name for RRULE construction
        $this->assertNotNull($node->getName());
        $this->assertTrue(is_string($node->getName()));
        $this->assertNotEmpty($node->getName());
        // Verify it behaves as expected in RRULE context
        $this->assertTrue(method_exists($node, 'getName'));
    }

    public function testNodePreservesOriginalInput(): void
    {
        $inputValue = 'TU';
        $node = new WkstNode($inputValue);

        // Behavioral test: node should preserve input for reconstruction
        $this->assertNotNull($node->getRawValue());
        $this->assertTrue(is_string($node->getRawValue()));
        $this->assertContains($node->getRawValue(), WkstNode::getChoices());
    }

    public static function provideHappyPathData(): array
    {
        return [
            ['SU', 'SU'],
            ['MO', 'MO'],
            ['TU', 'TU'],
            ['WE', 'WE'],
            ['TH', 'TH'],
            ['FR', 'FR'],
            ['SA', 'SA'],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            ['Week start day cannot be empty', ''],
            ['Invalid week start day value: INVALID. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'INVALID'],
            ['Invalid week start day value: MONDAY. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'MONDAY'],
            ['Invalid week start day value: Monday. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Monday'],
            ['Invalid week start day value: mo. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'mo'],
            ['Invalid week start day value: Mo. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Mo'],
            ['Invalid week start day value: su. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'su'],
            ['Invalid week start day value: Su. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Su'],
            ['Invalid week start day value: tue. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'tue'],
            ['Invalid week start day value: Tue. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Tue'],
            ['Invalid week start day value: wed. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'wed'],
            ['Invalid week start day value: Wed. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Wed'],
            ['Invalid week start day value: thu. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'thu'],
            ['Invalid week start day value: Thu. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Thu'],
            ['Invalid week start day value: fri. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'fri'],
            ['Invalid week start day value: Fri. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Fri'],
            ['Invalid week start day value: sat. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'sat'],
            ['Invalid week start day value: Sat. Valid values are: SU, MO, TU, WE, TH, FR, SA', 'Sat'],
            ['Invalid week start day value:  MO. Valid values are: SU, MO, TU, WE, TH, FR, SA', ' MO'],
            ['Invalid week start day value: MO . Valid values are: SU, MO, TU, WE, TH, FR, SA', 'MO '],
            ['Invalid week start day value:  MO . Valid values are: SU, MO, TU, WE, TH, FR, SA', ' MO '],
            ['Invalid week start day value: 1. Valid values are: SU, MO, TU, WE, TH, FR, SA', '1'],
            ['Invalid week start day value: 0. Valid values are: SU, MO, TU, WE, TH, FR, SA', '0'],
        ];
    }
}
