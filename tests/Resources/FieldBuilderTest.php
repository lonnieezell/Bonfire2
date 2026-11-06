<?php

namespace Tests\Resources;

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use Bonfire\Fields\Field as CompiledField;
use Bonfire\Fields\Section as CompiledSection;
use Bonfire\Resources\Column;
use Bonfire\Resources\Fields\BelongsTo;
use Bonfire\Resources\Fields\Date;
use Bonfire\Resources\Fields\Datetime;
use Bonfire\Resources\Fields\Decimal;
use Bonfire\Resources\Fields\Field;
use Bonfire\Resources\Fields\Integer;
use Bonfire\Resources\Fields\Markdown;
use Bonfire\Resources\Fields\Section;
use Bonfire\Resources\Fields\Select;
use Bonfire\Resources\Fields\Text;
use Bonfire\Resources\Fields\Textarea;
use Bonfire\Resources\Fields\Toggle;
use Bonfire\Resources\Resource;
use Bonfire\Resources\ResourceException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Models\CategoryModel;
use Tests\Support\TestCase;

/**
 * The PHP way to write the fields of a resource, which compiles to the shared Field.
 *
 * @internal
 */
final class FieldBuilderTest extends TestCase
{
    protected $migrate = false;

    /**
     * @param class-string<Field> $builder
     */
    #[DataProvider('provideEachBuilderCompilesToItsType')]
    public function testEachBuilderCompilesToItsType(string $builder, string $type): void
    {
        $field = $builder::make('thing')->compile();

        $this->assertInstanceOf(CompiledField::class, $field);
        $this->assertSame($type, $field->type);
        $this->assertSame('thing', $field->handle);
    }

    /**
     * @return iterable<string, array{class-string<Field>, string}>
     */
    public static function provideEachBuilderCompilesToItsType(): iterable
    {
        yield 'text' => [Text::class, 'text'];

        yield 'textarea' => [Textarea::class, 'textarea'];

        yield 'markdown' => [Markdown::class, 'markdown'];

        yield 'toggle' => [Toggle::class, 'toggle'];

        yield 'select' => [Select::class, 'select'];

        yield 'integer' => [Integer::class, 'integer'];

        yield 'date' => [Date::class, 'date'];

        yield 'datetime' => [Datetime::class, 'datetime'];

        yield 'decimal' => [Decimal::class, 'decimal'];

        yield 'belongsTo' => [BelongsTo::class, 'belongsTo'];
    }

    public function testTheGenericBuilderTakesAnyType(): void
    {
        $field = Field::make('cost', type: 'decimal')->compile();

        $this->assertSame('decimal', $field->type);
        $this->assertSame('text', Field::make('plain')->compile()->type);
    }

    public function testAnUnknownTypeIsRefusedWhenItCompiles(): void
    {
        $this->expectException(ResourceException::class);
        $this->expectExceptionMessage('`nonsense`');

        Field::make('x', type: 'nonsense')->compile();
    }

    public function testTheMethodsAreTheBlueprintKeys(): void
    {
        $field = Text::make('sku')
            ->label('SKU')
            ->validate('required|alpha_dash')
            ->instructions('Unique')
            ->width('full')
            ->compile();

        $this->assertSame('SKU', $field->label);
        $this->assertSame('required|alpha_dash', $field->validate);
        $this->assertSame('Unique', $field->instructions);
        $this->assertTrue($field->full);

        $this->assertSame(['required', 'max_length[5]'], Text::make('x')->validate(['required', 'max_length[5]'])->compile()->validate);
    }

    public function testTheLabelDefaultsToTheHandleAsWords(): void
    {
        $field = Text::make('release_date')->compile();

        $this->assertSame('Release date', $field->label);
        $this->assertSame('', $field->validate);
        $this->assertNull($field->instructions);
        $this->assertFalse($field->full);
    }

    public function testFullIsTheOnlyWidth(): void
    {
        $this->expectException(ResourceException::class);

        Text::make('x')->width('half');
    }

    public function testTypesHaveTheirOwnKeys(): void
    {
        $this->assertSame(['rows' => 3], Textarea::make('x')->rows(3)->compile()->options);
        $this->assertSame(['rows' => 20], Markdown::make('x')->rows(20)->compile()->options);
        $this->assertSame(['options' => ['a' => 'A'], 'multiple' => true], Select::make('x')->options(['a' => 'A'])->multiple()->compile()->options);
        $this->assertSame(['min' => 1, 'max' => 5], Integer::make('x')->min(1)->max(5)->compile()->options);
        $this->assertSame(['time' => true], Date::make('x')->time()->compile()->options);
        $this->assertSame(['places' => 3, 'min' => '0', 'step' => '0.5'], Decimal::make('x')->places(3)->min('0')->step('0.5')->compile()->options);
        $this->assertSame(['model' => CategoryModel::class, 'title' => 'name'], BelongsTo::make('x')->model(CategoryModel::class)->title('name')->compile()->options);
        $this->assertSame(['custom' => 1], Text::make('x')->with(['custom' => 1])->compile()->options);
    }

    public function testASectionCompilesWithItsFields(): void
    {
        $section = Section::make('Pricing', [Text::make('a'), Text::make('b')])->instructions('Money')->columns(2)->compile();

        $this->assertInstanceOf(CompiledSection::class, $section);
        $this->assertSame('Pricing', $section->title);
        $this->assertSame('Money', $section->instructions);
        $this->assertSame(2, $section->columns);
        $this->assertSame(['a', 'b'], array_map(static fn (CompiledField $field): string => $field->handle, $section->fields));
        $this->assertSame(CompiledSection::DEFAULT_COLUMNS, Section::make('Plain', [])->compile()->columns);
    }

    #[DoesNotPerformAssertions]
    public function testASectionTakesOneToThreeColumns(): void
    {
        foreach ([0, 4] as $columns) {
            try {
                Section::make('Bad', [])->columns($columns);
                $this->fail("{$columns} columns should be refused.");
            } catch (ResourceException) {
            }
        }
    }

    public function testTheResourceFlattensTheFieldsOfItsSectionsAndGroupsTheOnesBesideThem(): void
    {
        $resource = new class () extends Resource {
            public function fields(): array
            {
                return [
                    Text::make('first'),
                    Section::make('Group', [Text::make('second')]),
                    new CompiledField('third', 'text', 'Third', '', null, []),
                ];
            }
        };

        $this->assertSame(['first', 'second', 'third'], array_map(static fn (CompiledField $field): string => $field->handle, $resource->formFields()));
        $this->assertSame(['first', 'second', 'third'], array_map(static fn (array $pair): string => $pair[0]->handle, $resource->form()->fields()));

        $sections = $resource->form()->sections([], []);

        $this->assertSame([null, 'Group', null], array_column($sections, 'title'));
    }

    public function testTheIdFillsThePlaceholderInEveryRuleOfAField(): void
    {
        $resource = new class () extends Resource {
            public function fields(): array
            {
                return [
                    Text::make('as_string')->validate('is_unique[t.c,id,{id}]|required'),
                    Text::make('as_list')->validate(['is_unique[t.c,id,{id}]']),
                ];
            }
        };

        $edit = $resource->form('7')->fields();

        $this->assertSame('is_unique[t.c,id,7]|required', $edit[0][0]->validate);
        $this->assertSame(['is_unique[t.c,id,7]'], $edit[1][0]->validate);

        $this->assertSame('is_unique[t.c,id,]|required', $resource->form()->fields()[0][0]->validate);
    }

    public function testAColumnReadsItsHandleFromAnEntityOrAnArray(): void
    {
        $this->assertSame('x', Column::make('name')->valueOf(['name' => 'x']));
        $this->assertSame('x', Column::make('name')->valueOf((object) ['name' => 'x']));
        $this->assertSame('', Column::make('name')->valueOf(['other' => 'x']));
    }

    public function testADottedHandleReadsARelationOrTheJoinedColumn(): void
    {
        $column = Column::make('category.name');

        $this->assertSame('Loaded', $column->valueOf((object) ['category' => (object) ['name' => 'Loaded']]));
        $this->assertSame('Nested', $column->valueOf(['category' => ['name' => 'Nested']]));
        $this->assertSame('Joined', $column->valueOf(['category_name' => 'Joined']));
        $this->assertSame('', $column->valueOf(['category' => null]));
    }

    public function testAComputedColumnIsCalculatedFromTheRecord(): void
    {
        $column = Column::make('total', 'Total')->using(static fn (array $record): int => $record['a'] + $record['b']);

        $this->assertSame('Total', $column->label);
        $this->assertSame('5', $column->valueOf(['a' => 2, 'b' => 3]));
        $this->assertSame('Category name', Column::make('category.name')->label);
    }
}
