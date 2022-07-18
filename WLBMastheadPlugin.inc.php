<?php

/**
 * This plugin allows for extra fields in the masthead tab of each journal.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class WLBMastheadPlugin extends GenericPlugin {

	/**
	 * Provide a name for this plugin
	 *
	 * The name will appear in the plugins list where editors can
	 * enable and disable plugins.
	 */
	public function getDisplayName() {
		return __('plugins.generic.wlb.masthead.title');
	}

	/**
	 * Provide a description for this plugin
	 *
	 * The description will appear in the plugins list where editors can
	 * enable and disable plugins.
	 */
	public function getDescription() {
		return __('plugins.generic.wlb.masthead.desc');
	}

	/**
	 * Register the plugin and hook into functions to change.
	 */
	public function register($category, $path, $mainContextId = null) {

		$success = parent::register($category, $path);

		// Only hook into functions if the plugin is registered and enabled.
		if ($success && $this->getEnabled()) {

			// Insert additional ZDB ID field, save user-entred values, save in DB.
			HookRegistry::register('Schema::get::context', array($this, 'addToSchema'));
			HookRegistry::register('Form::config::before', array($this, 'addToForm'));
			HookRegistry::register('JournalSettingsDAO::getAdditionalFieldNames', array($this, 'addZDBIdToDao'));
		}

		return $success;
	}

	/**
	 * Add ZDb ID to the journal_settings DB schema.
	 * @param $hookName string
	 * @param $params array
	 */
	public function addToSchema($hookName, $args) {

		$schema = $args[0];
		$schema->properties->zdbId = (object) [
			'type' => 'string',
			'multilingual' => false,
			'apiSummary' => true,
			'validation' => ['nullable']
		];
		return false;
	}

	/**
	 * Extend the masthead form in the journal settings
	 * adding a ZDB ID field.
	 * 
	 * @param $hookName string
	 * @param $form FormComponent
	 */
	public function addtoForm($hookName, $form) {

		// Only modify the masthead form.
		if (!defined('FORM_MASTHEAD') || $form->id !== FORM_MASTHEAD) {
			return;
		}

		// Don't do anything at the site-wide level, only dependent context/journal.
		$context = Application::get()->getRequest()->getContext();
		if (!$context) {
			return;
		}

		// Add a field to the form.
		$form->addField(new \PKP\components\forms\FieldText('zdbId', [
			'label' => __('plugins.generic.zdb.field.title'),
			'description' => __('plugins.generic.zdb.field.desc'),
			'groupId' => 'publishing',
			'value' => $context->getData('zdbId'),
		]));

		return false;
	}


	/**
	 * For storage: Get a list of additional field names 
	 * to store in journal_settings.
	 * 
	 * @param $hookName
	 * @param $params
	 */
	function addZDBIdToDao($hookName, $params) {
		$fields = &$params[1];
		$fields[] = 'zdbId';
		return false;
	}
}
