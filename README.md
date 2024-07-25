# Fieldset Helper

Adds some visibility features to fields of type FieldtypeFieldsetOpen and FieldsetPage, and adds a helper method to get the child fields of a FieldtypeFieldsetOpen field.

## Visibility

The module adds support for the following visibility settings to Fieldset (Open) and FieldsetPage:

* Open when populated + Closed when blank
* Open when populated + Closed when blank + Load only when opened (AJAX) *
* Open when populated + Closed when blank + Locked (not editable)
* Open when blank + Closed when populated

"Blank" in the context of a fieldset means that all the child fields within the fieldset are blank.  
\* The AJAX part of this option doesn't currently work for FieldsetPage.

## Get the child fields of a fieldset

If you've ever wanted to get the child fields that are within a fieldset you will have noticed that the ProcessWire API doesn't provide a direct way of doing this. Although they appear indented in Edit Template, the child fields are actually all on the same "level" as the other fields in the template.

The module adds a `FieldtypeFieldsetOpen::getFieldsetFields()` method that you can use to get the child fields of a fieldset for a given page.

Example:

```php
$field = $fields->get('my_fieldset');
$template = $templates->get('my_template');
$fieldset_fields = $field->type->getFieldsetFields($field, $template);
```
* First argument: the fieldset Field, i.e. a Field whose type is an instance of FieldtypeFieldsetOpen (this includes FieldtypeFieldsetTabOpen).
* Second argument: the Template that the fieldset field is part of.
* Return value: a FieldsArray of child fields.

If there is a nested fieldset inside the fieldset then the child fields of the nested fieldset are not included in the return value, but of course you can use the method again to get the child fields of that fieldset.
