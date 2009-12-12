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
				'version' => '1.4',
				'release-date' => '2009-12-11',
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
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),
				array(
					'page' => '/backend/',
					'delegate' => 'InitaliseAdminPageHead',
					'callback' => 'applyStaticSection'
				),
				array(
					'page' => '/administration/',
					'delegate' => 'AdminPagePostGenerate',
					'callback' => 'manipulateOutput'
				)
			);
		}

		public function appendPreferences($context){
			$sections = Administration::instance()->Configuration->get('sections', 'static_section');

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', 'Static Section'));

			$label = Widget::Label();
			$label->appendChild(Widget::Input('settings[static_section][sections]', $sections));
			$group->appendChild($label);

			$availableSections = $this->getAvailableSections();

			$tags = new XMLElement('ul');
			$tags->setAttribute('class', 'tags');

			foreach($availableSections as $section){
				$tags->appendChild(new XMLElement('li', $section));
			}

			$group->appendChild($tags);

			$context['wrapper']->appendChild($group);
		}

		public function applyStaticSection($context){
			if ($this->_callback['driver'] == 'publish' && $this->_static){
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
			if ($this->_static){
				$section_id = $this->_sectionManager->fetchIDFromHandle($this->_callback['context']['section_handle']);
				$section = !is_null($section_id) ? $this->_sectionManager->fetch($section_id) : null;

				$dom = DOMDocument::loadHTML($context['output']);
				$xpath = new DOMXPath($dom);

				$title = $xpath->query("/html/head/title")->item(0);
				$title->nodeValue = __('%1$s &ndash; %2$s', array(__('Symphony'), $section->get('name')));

				if ($this->_callback['context']['page'] == 'edit'){
					$delete = $xpath->query("//div[@class='actions']/button[@name='action[delete]']")->item(0);
					$delete->parentNode->removeChild($delete);
				}

				$context['output'] = $dom->saveHTML();
			}
		}

		private function isStaticSection(){
			$sections = explode(',', Administration::instance()->Configuration->get('sections', 'static_section'));

			foreach($sections as $i => $s)
				$sections[$i] = trim($s);

			if (is_array($this->_callback['context']) && in_array($this->_callback['context']['section_handle'], $sections)) return true;

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

		private function getAvailableSections(){
			$sections = $this->_sectionManager->fetch();
			$result = array();

			if (is_array($sections) && !empty($sections)){
				foreach($sections as $s){
					$result[] = $s->get('handle');
				}
			}

			return $result;
		}

		public function uninstall(){
			Administration::instance()->Configuration->remove('sections', 'static_section');
			return Administration::instance()->saveConfig();
		}
	}
