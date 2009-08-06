<?php
	
	Class fieldStatic_Section extends Field {
		
		function __construct(&$parent){
			parent::__construct($parent);
			$this->_name = 'Static Section';
			$this->_required = false;
		}
		
		public function buildSummaryBlock($errors=NULL){
			$div = new XMLElement('div');
			
			// required label field (hidden)
			$this->set('label', 'Static Section');
			$label_input = Widget::Input('fields['.$this->get('sortorder').'][label]', $this->get('label'), 'hidden');
			if(isset($errors['label'])) $div->appendChild(Widget::wrapFormElementWithError($label_input, $errors['label']));
			else $div->appendChild($label_input);
			
			// help comments
			$diva = new XMLElement('div');
			$diva->setAttribute('class', 'group');
			$diva->appendChild(Widget::Label(__('Adding this field will convert this section into a Static Section.<br/>Static Sections are used to simplify the process of editing field collections that should only exist once in Symphony.<br />Please ensure that this section does not already contain more than one entry when this field is added.<br /><br />To disable Static Section functionality for this section, simply remove this field.')));
			$div->appendChild($diva);
			return $div;
		}
		
		public function commit() {
			// base fields commit
			if(!parent::commit()) return false;	
			
			// static section commit
			$id = $this->get('id');
			if ($id === false) return false;
			$parent_id = $this->get('parent_section');
			if ($parent_id === false) return false;
			$fields = array();
			$fields['field_id'] = $id;
			$fields['section_id'] = $parent_id;
			$this->_engine->Database->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");
			return $this->_engine->Database->insert($fields, 'tbl_fields_' . $this->handle());					
		}
		
		public function createTable() {
			// override to stop the creation
			// of an unnecessary table
			// no setting value needs to be
			// stored by this extension field
			return true;
		}		
	}

?>
