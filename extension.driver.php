<?php

	require_once(TOOLKIT . '/class.entrymanager.php');
	
	
	Class extension_static_section extends Extension{
	
		protected $section_data;
		protected $_page;
		
		public function about(){
			return array('name' => 'Static Section',
						 'version' => '1.0',
						 'release-date' => '2009-08-06',
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
						)
			);
		}
		
		public function appendScriptToHead($context) { 
			$entryManager = new EntryManager($this->_Parent);
			$sections = $this->_Parent->Database-> fetch("SELECT section_id AS id, handle FROM tbl_fields_static_section LEFT JOIN tbl_sections ON tbl_fields_static_section.section_id = tbl_sections.id");
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
				$section = $this->_Parent->Database->fetchRow(0, "SELECT id FROM tbl_sections WHERE handle='$section_handle'");
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
					
					Administration::instance()->Page->addScriptToHead(URL . '/extensions/static_section/assets/static_section.js', 90);
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
