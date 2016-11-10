<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

/**
 * @package   block_user_memo
 * @category  blocks
 * @author    Valery Fremaux (valery.fremaux@edunao.com)
 * @copyright 2015 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/blog/locallib.php');
require_once($CFG->dirroot.'/tag/lib.php');

class block_user_memo extends block_base {

    var $controllermessage = '';

    function init() {
        $this->title = get_string('pluginname', 'block_user_memo');
    }

    function applicable_formats() {
        return array('all' => true);   // Needs work to make it work on tags MDL-11960
    }

    function specialization() {
        global $CFG, $DB, $USER, $COURSE;

        require_once($CFG->dirroot.'/blocks/user_memo/block_user_memo.controller.php');
        $controller = new block_user_memo_controller($this);
        $action = optional_param('what', '', PARAM_TEXT);
        $params = $controller->get_params($action);
        $controller->handle($action, $params);
    }

    function instance_allow_multiple() {
        return false;
    }

    function has_config() {
        return true;
    }

    function instance_allow_config() {
        return true;
    }

    function get_content() {
        global $PAGE;

        $this->content = new StdClass();

        $renderer = $PAGE->get_renderer('block_user_memo');

        $this->content->text = '';

        if (!empty($this->controllermessage)) {
            $this->content->text .= '<div class="user-memo-message">'.$this->controllermessage.'</div>';
            $this->controllermessage = '';
        }

        $this->content->text .= $renderer->render_content($this);

        return $this->content;
    }

    /**
     * Creates a new blog entry in user blog, transfers the notes and cleanup
     * @param int $userid the blog's owner
     * @param int $blockid the usermemo block instance
     */
    static function export_to_blog($userid, $blockid) {
        global $COURSE, $DB, $USER;

        $memos = $DB->get_records('block_user_memo', array('blockid' => $blockid, 'userid' => $userid), 'sortorder');
        if (!empty($memos)) {
            foreach ($memos as $m) {
                $memotext[] = '<p>'.$m->memo.'</p>';
            }
        } else {
            return;
        }

        $params = array(
            'userid' => $userid,
            'subject' => get_string('mynotes', 'block_user_memo', $COURSE->fullname),
            'summary' => implode('', $memotext),
            'publishstate' => 0,
            'courseid' => $COURSE->id,
        );

        $blogentry = new blog_entry(null, $params);
        $blogentry->add();

        // purge old memo.
        $DB->delete_records('block_user_memo', array('blockid' => $blockid, 'userid' => $USER->id));
    }
}