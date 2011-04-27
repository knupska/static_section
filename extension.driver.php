<?php

	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');

	Class extension_static_section extends Extension{

		private $_sectionManager;
		private $_entryManager;
		private $_callback;
		private $_static;

		public function __construct($args){
			$this->_Parent =& $args['parent'];

			$this->_sectionManager = new SectionManager($this->_Parent);
			$this->_entryManager = new EntryManager($this->_Parent);
			$this->_callback = Administration::instance()->getPageCallback();
			$this->_static = $this->isStaticSection();
		}

		public function about(){
			return array(
				'name' => 'Static Section',
				'version' => '1.6',
				'release-date' => '2011-04-27',
				'author' => array(
					'name' => 'Nathan Martin',
					'website' => 'http://knupska.com',
					'email' => 'nathan@knupska.com'
				)
			);
		}
	
		public function getSubscribedDelegates(){
			return array(
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'redirectRules'
				),
				array(
					'page' => '/blueprints/sections/',
					'delegate' => 'AddSectionElements',
					'callback' => 'add_section_settings'
				),
				array(
					'page'		=> '/blueprints/sections/',
					'delegate'	=> 'SectionPreCreate',
					'callback'	=> 'save_section_settings'
				),
				array(
					'page'		=> '/blueprints/sections/',
					'delegate'	=> 'SectionPreEdit',
					'callback'	=> 'save_section_settings'
				)
			);
		}

	/*-------------------------------------------------------------------------
		Delegates
	-------------------------------------------------------------------------*/
		
		public function redirectRules($context){
			if ($this->_static){
				$section_handle = $this->_callback['context']['section_handle'];
				$entry = $this->getLastPosition($section_handle);
				$params = $this->getConcatenatedParams();

				if ($this->_callback['context']['entry_id'] != $entry || $this->_callback['context']['page'] == 'index'){
					redirect(URL . "/symphony/publish/{$section_handle}/edit/{$entry}/{$params}");
				}

				if (!$entry && $this->_callback['context']['page'] != 'new'){
					redirect(URL . "/symphony/publish/{$section_handle}/new/{$params}");
				}
			}
		}

		public function add_section_settings($context) {
			
			// Get current setting
			$setting = array();
			if($context['meta']['static'] == 'yes') {
				$setting = array('checked' => 'checked');
			}
			
			// Prepare setting
			$label = new XMLElement('label');
			$checkbox = new XMLElement('input', ' ' . __('Make this section static (i.e. a single entry section)'), array_merge($setting, array('name' => 'meta[static]', 'type' => 'checkbox', 'value' => 'yes')));
			$label->appendChild($checkbox);
			
			// Find context
			$fieldset = $context['form']->getChildren();
			$group = $fieldset[0]->getChildren();
			$column = $group[1]->getChildren();
			
			// Append setting
			$column[0]->appendChild($label);
		}
		
		public function save_section_settings($context) {
			if(!$context['meta']['static']) {
				$context['meta']['static'] = 'no';
			}
		}
		
	/*-------------------------------------------------------------------------
		Helpers
	-------------------------------------------------------------------------*/
		
		private function isStaticSection(){
			if ($this->_callback['driver'] == 'publish' && is_array($this->_callback['context'])){
				$section_id = $this->_sectionManager->fetchIDFromHandle($this->_callback['context']['section_handle']);

				if ($section_id){
					$section = $this->_sectionManager->fetch($section_id);
					return ($section->get('static') == 'yes');
				}
			}
			
			return false;
		}
		
		
		private function getLastPosition($section_handle){
			$this->_entryManager->setFetchSortingDirection('DESC');
			$section_id = $this->_sectionManager->fetchIDFromHandle($section_handle);
			$entry = $this->_entryManager->fetch(NULL, $section_id, 1);

			if (is_array($entry) && !empty($entry)){
				$entry = end($entry);
				return $entry->get('id');
			}
		}
		
		private function getConcatenatedParams(){
			if (count($_GET) > 2) {
				$params = "?";
			}

			foreach($_GET as $key => $value){
				if (in_array($key, array('symphony-page', 'mode'))) continue;
				
				$params .= "{$key}={$value}";
				if (next($_GET)) {
					$params .= '&';
				}
			}
			
			return $params;
		}
		
	/*-------------------------------------------------------------------------
		Installation
	-------------------------------------------------------------------------*/
		
		public function install(){
			return Administration::instance()->Database->query("ALTER TABLE `tbl_sections` ADD `static` enum('yes','no') NOT NULL DEFAULT 'no' AFTER `hidden`");
		}

		public function uninstall(){
			return Administration::instance()->Database->query("ALTER TABLE `tbl_sections` DROP `static`");
		}

	}
