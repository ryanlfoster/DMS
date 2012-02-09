<?php
/*
	Section: Full Width Sidebar
	Author: PageLines
	Author URI: http://www.pagelines.com
	Description: Shows full width widgetized sidebar.
	Class Name: FullWidthSidebar
	Edition: pro
	Workswith: templates, footer, morefoot
	Persistant: true
*/

/**
 * Full Width Sidebar Section
 *
 * @package PageLines Framework
 * @author PageLines
 **/
class FullWidthSidebar extends PageLinesSection {

   function section_persistent() { 
		$setup = pagelines_standard_sidebar($this->name, $this->settings['description']);
		pagelines_register_sidebar($setup, 5);
	
	}

   function section_template() { 
		 pagelines_draw_sidebar($this->id, $this->name);
	}
}