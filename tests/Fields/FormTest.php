<?php

namespace Tests\Fields;

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;
use Bonfire\Fields\Form;
use Bonfire\Fields\Types\IntegerField;
use Bonfire\Fields\Types\TextField;
use Bonfire\Fields\Types\ToggleField;
use Tests\Support\Content\ShoutField;
use Tests\Support\TestCase;

/**
 * The form object on its own: no Content, no blueprint, just fields.
 *
 * @internal
 */
final class FormTest extends TestCase
{
    protected $migrate = false;

    public function testItPairsEachFieldWithItsType(): void
    {
        $pairs = $this->form()->fields();

        $this->assertCount(4, $pairs);
        $this->assertSame('name', $pairs[0][0]->handle);
        $this->assertInstanceOf(TextField::class, $pairs[0][1]);
        $this->assertInstanceOf(IntegerField::class, $pairs[1][1]);
        $this->assertInstanceOf(ToggleField::class, $pairs[2][1]);
        $this->assertContainsOnlyInstancesOf(FieldType::class, array_column($pairs, 1));
    }

    public function testRowsHoldTheValueTheErrorAndTheViewOfEachField(): void
    {
        $rows = $this->form()->rows(['name' => 'Ada', 'age' => '36'], ['age' => 'Too young']);

        $this->assertSame(['name', 'age', 'active', 'mood'], array_map(static fn (array $row): string => $row['field']->handle, $rows));
        $this->assertSame('Ada', $rows[0]['value']);
        $this->assertNull($rows[0]['error']);
        $this->assertSame('Bonfire\Fields\Views\fields\text', $rows[0]['view']);
        $this->assertSame('Too young', $rows[1]['error']);
        $this->assertSame('', $rows[2]['value'], 'a field without a value shows empty');
        $this->assertSame('Bonfire\Fields\Views\fields\toggle', $rows[2]['view']);
        $this->assertContainsOnlyBool(array_column($rows, 'disabled'));
        $this->assertNotContains(true, array_column($rows, 'disabled'));
    }

    public function testAReadOnlyFieldIsShownDisabledAndNotTakenFromAPost(): void
    {
        $form = $this->form(['name']);

        $this->assertTrue($form->rows([], [])[0]['disabled']);
        $this->assertFalse($form->rows([], [])[1]['disabled']);
        $this->assertFalse($form->isEditable($form->fields()[0][0]));
        $this->assertArrayNotHasKey('name', $form->prepare(['fields' => ['name' => 'Sneaky', 'age' => '3']]));
    }

    public function testPrepareTidiesWhatWasPostedByTheTypeOfEachField(): void
    {
        $values = $this->form()->prepare(['fields' => ['name' => 'Ada', 'age' => ' +5 ', 'mood' => ['x']]]);

        $this->assertSame(['name' => 'Ada', 'age' => '5', 'active' => '0', 'mood' => ''], $values);
    }

    public function testPrepareIgnoresAPostWithoutFields(): void
    {
        $this->assertSame(['name' => '', 'age' => '', 'active' => '0', 'mood' => ''], $this->form()->prepare(['fields' => 'nope']));
        $this->assertSame(['name' => '', 'age' => '', 'active' => '0', 'mood' => ''], $this->form()->prepare([]));
    }

    public function testValidateReturnsTheFirstErrorOfEachFieldThatHasOne(): void
    {
        $form   = $this->form();
        $errors = $form->validate($form->prepare(['fields' => ['name' => '', 'age' => '200', 'mood' => 'nope']]));

        $this->assertSame(['name', 'age', 'mood'], array_keys($errors));
        $this->assertSame(lang('Fields.integerMax', [120]), $errors['age']);
        $this->assertSame(lang('Fields.selectInvalid'), $errors['mood']);
    }

    public function testAValidPostHasNoErrors(): void
    {
        $form = $this->form();

        $this->assertSame([], $form->validate($form->prepare(['fields' => ['name' => 'Ada', 'age' => '36', 'mood' => 'happy']])));
    }

    public function testValidateSkipsFieldsWithoutAValue(): void
    {
        // The required name was never prepared, as a read-only field's wouldn't be.
        $this->assertSame([], $this->form(['name'])->validate($this->form(['name'])->prepare(['fields' => ['age' => '3']])));
    }

    public function testAFormWithoutRulesIsAlwaysValid(): void
    {
        $form = new Form([new Field('note', 'text', 'Note', '', null, [])]);

        $this->assertSame([], $form->validate($form->prepare(['fields' => ['note' => 'anything']])));
    }

    public function testACustomTypeRegisteredInConfigWorksInAForm(): void
    {
        config('Fields')->types = ['shout' => ShoutField::class];

        $form   = new Form([new Field('loud', 'shout', 'Loud', 'required', null, [])]);
        $values = $form->prepare(['fields' => ['loud' => 'hey']]);

        $this->assertSame(['loud' => 'HEY'], $values);
        $this->assertSame([], $form->validate($values));
        $this->assertArrayHasKey('loud', $form->validate($form->prepare(['fields' => ['loud' => 'far too long for this']])));
        $this->assertSame('Bonfire\Fields\Views\fields\text', $form->rows($values, [])[0]['view'], 'the type borrows the bundled text input');
    }

    /**
     * @param list<string> $readOnly
     */
    private function form(array $readOnly = []): Form
    {
        return new Form([
            new Field('name', 'text', 'Name', 'required', null, []),
            new Field('age', 'integer', 'Age', '', null, ['min' => 0, 'max' => 120]),
            new Field('active', 'toggle', 'Active', '', null, []),
            new Field('mood', 'select', 'Mood', '', null, ['options' => ['happy', 'sad']]),
        ], $readOnly);
    }
}
