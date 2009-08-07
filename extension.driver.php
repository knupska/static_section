<?php

	require_once(TOOLKIT . '/class.entrymanager.php');
	
	
	Class extension_static_section extends Extension{
	
		protected $section_data;
		protected $_page;
		protected $static_section_name;
		
		public function about(){
			return array('name' => 'Static Section',
						 'version' => '1.2',
						 'release-date' => '2009-08-07',
						 'author' => array('name' => 'Nathan Martin',
										   'website' => 'http://knupska.com',
										   'email' => 'nathan@knupska.com')
				 		);
		}
		
		public function getSubscribedDelegates(){
			return array(
						array(
							'page' => '/backend/',
							'delegate' => 'InitaliseAdminPageHead',
							'callback' => 'appendScriptToHead'
						),
						array(
							'page' => '/administration/',
							'delegate' => 'NavigationPreRender',
							'callback' => 'applyStaticSections'
						),
						array(
							'page' => '/administration/',
							'delegate' => 'AdminPagePostGenerate',
							'callback' => 'modifyPageElements'
						)
			);
		}
		
		public function appendScriptToHead($context) {
			$this->static_section_name = '';
			$entryManager = new EntryManager($this->_Parent);
			$sections = $this->_Parent->Database->fetch("SELECT section_id AS id, handle FROM tbl_fields_static_section LEFT JOIN tbl_sections ON tbl_fields_static_section.section_id = tbl_sections.id");
			$this->section_data = array(
								'handles' => array(),
								'entries' => array()
								);
			foreach($sections as $key => $value) {
				$this->section_data['handles'][] = $value['handle'];
				$result = $entryManager->fetch(NULL, $value['id'], NULL, NULL, NULL, NULL, false, false);
				if (count($result) > 0) $this->section_data['entries'][] = $result[0]['id'];
				else $this->section_data['entries'][] = NULL;
			}
			
			$this->_page = Administration::instance()->Page;
			$section_handle = $this->_page->_context['section_handle'];
			$context = $this->_page->_context['page'];
			$url_entry = $this->_page->_context['entry_id'];
			$flag = $this->_page->_context['flag'];
			
			if (isset($section_handle)) {
				$section = $this->_Parent->Database->fetchRow(0, "SELECT id, name FROM tbl_sections WHERE handle='$section_handle'");
				$field = $this->_Parent->Database->fetchRow(0, "SELECT id FROM tbl_fields_static_section WHERE section_id=" . $section['id']);
				
				if($field) {
					$entry = $this->getSectionEntry($section_handle);
					
					if ($context == 'new' && $entry) {
						redirect(URL . '/symphony/publish/' . $section_handle . '/edit/' . $entry . '/');
					}
					
					if($context == 'index') {
						if ($entry) redirect(URL . '/symphony/publish/' . $section_handle . '/edit/' . $entry . '/');
						else redirect(URL . '/symphony/publish/' . $section_handle . '/new/');
					}
					
					if ($context == 'edit') {
						if(!$entry) redirect(URL . '/symphony/publish/' . $section_handle . '/new/');
						if($url_entry != $entry) redirect(URL . '/symphony/publish/' . $section_handle . '/edit/' . $entry . '/');
					}
					
					if ($flag == 'saved' || $flag == 'created') {
						$flag_msg = 'Entry updated at %1$s.';
						if ($flag == 'created') $flag_msg = 'Entry created at %1$s.';
						$this->_page->pageAlert(
							__(
								$flag_msg, 
								array(DateTimeObj::getTimeAgo(__SYM_TIME_FORMAT__))
							), 
							Alert::SUCCESS);
					}
					
					$this->static_section_name = $section['name'];
				}
			}
		}
		
		private function getSectionEntry($handle) {
			$array_location = $this->getSectionEntryPosition($handle);
			if ($array_location !== FALSE) return $this->section_data['entries'][$array_location];
			return NULL;
		}
		
		private function getSectionEntryPosition($handle) {
			return array_search($handle, $this->section_data['handles']);
		}
		
		public function applyStaticSections($nav) {
			foreach( $nav['navigation'] as $pkey => $pvalue) {
				if ($pkey < 100) {
					foreach($pvalue['children'] as $key => $value) {
						if ($value['visible'] == 'yes') {
							$is_static = $this->getSectionEntryPosition($value['section']['handle']);
							if($is_static !== FALSE) {
								$entry = $this->getSectionEntry($value['section']['handle']);
								if ($entry) $nav['navigation'][$pkey]['children'][$key]['link'] = '/publish/' . $value['section']['handle'] . '/edit/' . $entry . '/';
								else $nav['navigation'][$pkey]['children'][$key]['link'] = '/publish/' . $value['section']['handle'] . '/new/';
							}
						}
					}
				}
			}
		}
		
		public function modifyPageElements($output) {
			if ($this->static_section_name != '') {
				// force static section title
				$startpos = strpos($output['output'], '<h2>');
				$endpos = strpos($output['output'], '</h2>', $startpos);
				if ($startpos !== FALSE && $endpos !== FALSE) {
					$output['output'] = substr_replace($output['output'], '<h2>' . $this->static_section_name . '</h2>', $startpos, $endpos - $startpos);
				}
				
				// hide delete button
				$startpos = strpos($output['output'], '<button name="action[delete]"');
				$endpos = strpos($output['output'], '</button>', $startpos);
				if ($startpos !== FALSE && $endpos !== FALSE) {
					$output['output'] = substr_replace($output['output'], '', $startpos, $endpos - $startpos);
				}
			}
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_static_section`");
		}
		
		public function update($previousVersion) {
			// for future use
			return true;
		}
		
		public function install() {
			return $this->_Parent->Database->query("CREATE TABLE `tbl_fields_static_section` (
			  `id` int(11) unsigned NOT NULL auto_increment,
			  `field_id` int(11) unsigned NOT NULL,
			  `section_id` int(11) unsigned NOT NULL,
			  PRIMARY KEY  (`id`),
			  UNIQUE KEY `field_id` (`field_id`),
			  UNIQUE KEY `section_id` (`section_id`)
			) TYPE=MyISAM");
			return true;
		}
		
	}

?>
