<?php namespace ProcessWire;

class FieldsetHelper extends WireData implements Module {

	/**
	 * Ready
	 */
	public function ready() {
		$this->addHookAfter('InputfieldFieldsetOpen::getConfigInputfields', $this, 'afterConfigInputfields');
		$this->addHookBefore('InputfieldRepeater::renderReadyHook', $this, 'beforeFieldsetPageRender');
		$this->addHookMethod('FieldtypeFieldsetOpen::getFieldsetFields', $this, 'getFieldsetFields');
	}

	/**
	 * After InputfieldFieldsetOpen::getConfigInputfields
	 *
	 * @param HookEvent $event
	 */
	protected function afterConfigInputfields(HookEvent $event) {
		$inputfield = $event->object;
		$wrapper = $event->return;
		// Not for InputfieldFieldsetTabOpen
		if($inputfield instanceof InputfieldFieldsetTabOpen) return;
		$f = $wrapper->getChildByName('collapsed');
		if(!$f) return;

		// Add back the blank/populated options that were removed by InputfieldWrapper::getConfigInputfields
		// This seems to be all that's needed to get the blank/populated features working for InputfieldFieldsetOpen
		$f->addOption(Inputfield::collapsedBlank, $this->_('Open when populated + Closed when blank'));
		if($inputfield->hasFieldtype !== false) {
			$f->addOption(Inputfield::collapsedBlankAjax, $this->_('Open when populated + Closed when blank + Load only when opened (AJAX)') . " †");
		}
		$f->addOption(Inputfield::collapsedBlankLocked, $this->_('Open when populated + Closed when blank + Locked (not editable)'));
		$f->addOption(Inputfield::collapsedPopulated, $this->_('Open when blank + Closed when populated'));
	}

	/**
	 * Before InputfieldRepeater::renderReadyHook
	 *
	 * @param HookEvent $event
	 */
	protected function beforeFieldsetPageRender(HookEvent $event) {
		/** @var InputfieldRepeater $inputfield */
		$inputfield = $event->object;

		// Only for FieldtypeFieldsetPage
		if($inputfield->hasFieldtype != 'FieldtypeFieldsetPage') return;

		// Only if the inputfield has a relevant collapsed setting
		$collapsed = $inputfield->collapsed;
		$for_collapsed = [Inputfield::collapsedBlank, Inputfield::collapsedBlankLocked, Inputfield::collapsedBlankAjax, Inputfield::collapsedPopulated];
		if(!in_array($collapsed, $for_collapsed)) return;

		// Must have a corresponding Field and Page
		$field = $inputfield->hasField;
		if(!$field) return;
		$page = $inputfield->hasPage;
		if(!$page || !$page->id) return;

		// Get the FieldsetPage (i.e. Repeater page)
		$fs_page = $field->type->getFieldsetPage($page, $field, false);
		if(!$fs_page || !$fs_page->id) return;

		// Loop over the field values to check if they are all empty
		$template = $field->type->getRepeaterTemplate($field);
		$all_empty = true;
		foreach($template->fields as $field) {
			$value = $fs_page->get($field->name);
			if(!$field->type->isEmptyValue($field, $value)) {
				$all_empty = false;
				break;
			}
		}

		// Set the collapsed state accordingly
		if($all_empty) {
			switch($collapsed) {
				case Inputfield::collapsedBlank:
					$inputfield->collapsed = Inputfield::collapsedYes;
					break;
				case Inputfield::collapsedBlankLocked:
					$inputfield->collapsed = Inputfield::collapsedLocked;
					break;
				case Inputfield::collapsedBlankAjax:
					$inputfield->collapsed = Inputfield::collapsedYesAjax;
					break;
				case Inputfield::collapsedPopulated:
					$inputfield->collapsed = Inputfield::collapsedNo;
					break;
			}
		}
	}

	/**
	 * Get the fields contained within a fieldset
	 *
	 * @param HookEvent $event
	 */
	public function getFieldsetFields(HookEvent $event) {
		$field = $event->arguments(0);
		$template = $event->arguments(1);

		// For all fieldsets in the page template, work out the child fields
		$data = [];
		$fieldset_names = [];
		foreach($template->fields as $f) {
			if($f->type instanceof FieldtypeFieldsetClose) {
				array_pop($fieldset_names);
			}
			else {
				$fieldset_name = end($fieldset_names);
				if($fieldset_name) $data[$fieldset_name][] = $f;
				if($f->type instanceof FieldtypeFieldsetOpen) {
					$fieldset_names[] = $f->name;
				}
			}
		}

		// Return a FieldsArray of the child fields within the given fieldset
		$fieldset_fields = new FieldsArray();
		if(!empty($data[$field->name])) {
			foreach($data[$field->name] as $f) {
				$fieldset_fields->add($f);
			}
		}
		$event->return = $fieldset_fields;
	}

}
