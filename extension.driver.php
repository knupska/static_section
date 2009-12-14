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
				'version' => '1.5',
				'release-date' => '2009-13-11',
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
					'page' => '/administration/',
					'delegate' => 'AdminPagePostGenerate',
					'callback' => 'manipulateOutput'
				)
			);
		}

		public function redirectRules($context){
			$this->fixPostValue();

			if ($this->_static){
				$section_handle = $this->_callback['context']['section_handle'];
				$entry = $this->getLastPosition($section_handle);

				if ($this->_callback['context']['entry_id'] != $entry || $this->_callback['context']['page'] == 'index'){
					redirect(URL . "/symphony/publish/{$section_handle}/edit/{$entry}/");
				}

				if (!$entry && $this->_callback['context']['page'] != 'new'){
					redirect(URL . "/symphony/publish/{$section_handle}/new/");
				}
			}
		}

		public function manipulateOutput($context){
			$this->appendPreferences($context);
			$this->applyStaticSection($context);
		}

		private function fixPostValue(){
			if ($this->_callback['driver'] == 'blueprintssections' && in_array($this->_callback['context'][0], array('edit', 'new'))){
				if ($_POST['action']['save']){
					if (!$_POST['meta']['static']) $_POST['meta'] += array('static' => 'no');
				}
			}
		}

		private function appendPreferences($context){
			if ($this->_callback['driver'] == 'blueprintssections' && in_array($this->_callback['context'][0], array('edit', 'new'))){
				$dom = DOMDocument::loadHTML($context['output']);
				$xpath = new DOMXPath($dom);

				$meta = $xpath->query("//input[@name='meta[hidden]']")->item(0);

				$label = $dom->createElement('label');

				$checkbox = $dom->createElement('input');
				$checkbox->setAttribute('type', 'checkbox');
				$checkbox->setAttribute('name', 'meta[static]');
				$checkbox->setAttribute('value', 'yes');

				$section = $this->_sectionManager->fetch($this->_callback['context'][1]);

				$errors = Administration::instance()->Page->_errors;

				if (is_object($section) && $section->get('static') == 'yes' || is_array($errors) && !empty($errors) && $_POST['meta']['static'] == 'yes'){
					$checkbox->setAttribute('checked', 'checked');
				}

				$label->appendChild($checkbox);
				$label->appendChild(new DOMText(__('Make this section static (i.e a single entry section)')));

				$meta->parentNode->parentNode->appendChild($label);

				$context['output'] = $dom->saveHTML();
			}
		}

		private function applyStaticSection(&$context){
			if ($this->_static){
				$section_id = $this->_sectionManager->fetchIDFromHandle($this->_callback['context']['section_handle']);
				$section = $this->_sectionManager->fetch($section_id);

				$dom = DOMDocument::loadHTML($context['output']);
				$xpath = new DOMXPath($dom);

				$title = $xpath->query("/html/head/title")->item(0);
				$title->nodeValue = __('%1$s &ndash; %2$s', array(__('Symphony'), $section->get('name')));

				$h2 = $xpath->query("/html/body/form/h2")->item(0);
				$h2->nodeValue = $section->get('name');

				if ($this->_callback['context']['page'] == 'edit'){
					$delete = $xpath->query("//div[@class='actions']/button[@name='action[delete]']")->item(0);
					$delete->parentNode->removeChild($delete);
				}

				$context['output'] = $dom->saveHTML();
			}
		}

		private function isStaticSection(){
			if ($this->_callback['driver'] == 'publish' && is_array($this->_callback['context'])){
				$section_id = $this->_sectionManager->fetchIDFromHandle($this->_callback['context']['section_handle']);
				$section = $this->_sectionManager->fetch($section_id);

				if ($section->get('static') == 'yes') return true;
			}
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

		public function install(){
			return Administration::instance()->Database->query("ALTER TABLE `sym_sections` ADD `static` enum('yes','no') NOT NULL DEFAULT 'no' AFTER `hidden`");
		}

		public function uninstall(){
			return Administration::instance()->Database->query("ALTER TABLE `tbl_sections` DROP `static`");
		}

	}
