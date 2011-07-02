<?php

	require_once(TOOLKIT . '/class.entrymanager.php');
	require_once(TOOLKIT . '/class.sectionmanager.php');

	Class extension_static_section extends Extension{

		private $_callback;
		private $_static;
		private $_section;

		public function __construct($args){
			$this->_Parent =& $args['parent'];

			$this->_callback = Administration::instance()->getPageCallback();
			$this->_section = $this->getSection();
			$this->_static = $this->isStaticSection();
		}

		public function about(){
			return array(
				'name' => 'Static Section',
				'version' => '1.6.1',
				'release-date' => '2011-05-11',
				'author' => array(
					array(
						'name' => 'Nathan Martin',
						'website' => 'http://knupska.com',
						'email' => 'nathan@knupska.com'
					),
					array(
						'name' => 'Rainer Borene',
						'website' => 'http://rainerborene.com',
						'email' => 'me@rainerborene.com'
					),
					array(
						'name' => 'Vlad Ghita',
						'email' => 'vlad_micutul@yahoo.com'
					)
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
					'callback' => 'addSectionSettings'
				),
				array(
					'page'		=> '/blueprints/sections/',
					'delegate'	=> 'SectionPreCreate',
					'callback'	=> 'saveSectionSettings'
				),
				array(
					'page'		=> '/blueprints/sections/',
					'delegate'	=> 'SectionPreEdit',
					'callback'	=> 'saveSectionSettings'
				),
				array(
					'page'		=> '/backend/',
					'delegate'	=> 'AppendElementBelowView',
					'callback'	=> 'appendElementBelowView'
				)
			);
		}

		
	/*-------------------------------------------------------------------------
		Delegates
	-------------------------------------------------------------------------*/
		
		public function redirectRules($context){
			if ($this->_static){
				$section_handle = $this->_section->get('handle');
				$entry = $this->getLastPosition();
				$params = $this->getConcatenatedParams();

				if ($this->_callback['context']['entry_id'] != $entry || $this->_callback['context']['page'] == 'index'){
					redirect(URL . "/symphony/publish/{$section_handle}/edit/{$entry}/{$params}");
				}

				if (!$entry && $this->_callback['context']['page'] != 'new'){
					redirect(URL . "/symphony/publish/{$section_handle}/new/{$params}");
				}
			}
		}

		public function addSectionSettings($context) {
			
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
		
		public function saveSectionSettings($context) {
			if(!$context['meta']['static']) {
				$context['meta']['static'] = 'no';
			}
		}
		
		public function appendElementBelowView($context){
			
			// if static section, replace __FIRST__ <h2> title with section name
			if ( $this->_static ) {
				
				foreach ( $context['parent']->Page->Contents->getChildren() as $child ) {
				
					if ($child->getName() == 'h2') {
						$child->setValue($this->_section->get('name'));
						break;
					}
				}
			}
		}
		
		
	/*-------------------------------------------------------------------------
		Helpers
	-------------------------------------------------------------------------*/

		private function getSection(){
			$sm = new SectionManager($this->_Parent);
			$section_id = $sm->fetchIDFromHandle($this->_callback['context']['section_handle']);
			
			return $sm->fetch($section_id);
		}
		
		public function isStaticSection(){
			if ($this->_callback['driver'] == 'publish' && is_array($this->_callback['context'])){
				return ($this->_section->get('static') == 'yes');
			}
			
			return false;
		}
		
		private function getLastPosition(){
			$em = new EntryManager($this->_Parent);
			
			$em->setFetchSortingDirection('DESC');
			$entry = $em->fetch(NULL, $this->_section->get('id'), 1);

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
