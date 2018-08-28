<?php
/**
 * Created by PhpStorm.
 * User: erikfox
 * Date: 5/22/18
 * Time: 11:15 PM
 */

class block_leaderboard extends block_base {

    public function init() {
        $this->title = get_string('leaderboard', 'block_leaderboard');
    }
    // The PHP tag and the curly bracket for the class definition
    // will only be closed after there is another function added in the next section.

    function has_config() {
        return true;
    }

    public function get_content() {

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text   = '';
        $this->content->footer = '';

        $renderer = $this->page->get_renderer('block_leaderboard');
        $this->content->text = $renderer->leaderboard_block($this->page->course);
        
        return $this->content;
    }

    public function hide_header() {
        return true;
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_leaderboard'; // Append our class to class attribute
        return $attributes;
    }
}